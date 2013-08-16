<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\zeromass;
class Plugin {
    protected $name;
    protected $fileList;
    protected $mainFile;
    protected $shortDescription;
    protected $humanName;
    protected $usageSummary;
    protected $knownDocblockTags = array('author', 'copyright', 'example', 'link', 'version', 'depends');
    protected $docBlockTagValues = array();
    protected $parsedDocBlock = false;

    public function __construct($name, $fileList = null) {/*{{{*/
        if (is_null($fileList)) {
            $pluginDir = \Zeromass::getInstance()->pluginDir;
            if (is_file($pluginDir . '/' . $name . '.php')) {
                $fileList = array($pluginDir . '/' . $name);
            } elseif (is_dir($pluginDir . '/' . $name)) {
                $fileList = self::findFilesInDir($pluginDir . '/' . $name);
            } else throw new Exception('Plugin ' . $name . ' not found');
        }
        $this->name = $name;
        $this->fileList = $fileList;
        foreach ($this->fileList as $file) {
            if (strlen(basename($file)) > strlen($name) && substr(basename($file), 0, -4) == $name) $this->mainFile = $file;
        }
    }/*}}}*/
    public static function getAllPluginFiles($pluginDir = false) {/*{{{*/
        if ($pluginDir === false) {
            $pluginDir = \Zeromass::getInstance()->pluginDir;
        }
        $result = array();
        $dh = opendir($pluginDir);
        while ($file = readdir($dh)) {
            if ($file == '..' || $file == '.') continue;
            if (is_dir($pluginDir . '/' . $file)) $result[$file] = self::findFilesInDir($pluginDir . '/' . $file);
            if (strlen($file) < 4 || substr($file, -4) != '.php') continue;
            if (is_file($pluginDir . '/' . $file)) $result[substr($file, 0, -4)] = array($pluginDir . '/' . $file);
        }
        return $result;
    }/*}}}*/
    protected static function findFilesInDir($dir) {/*{{{*/
        $dh = opendir($dir);
        $result = array();
        while ($file = readdir($dh)) {
            if ($file == '..' || $file == '.') continue;
            if (is_dir($dir . '/' . $file)) $result = array_merge($result, self::findFilesInDir($dir . '/' . $file));
            if (is_file($dir . '/' . $file)) $result[] = $dir . '/' . $file;
        }
        return $result;
    }/*}}}*/
    public function getShortDescription() {/*{{{*/
        $this->parseSource();
        if (is_null($this->shortDescription)) $this->shortDescription = 'No plugin description defined in plugin source';
        return $this->shortDescription;
    }/*}}}*/
    public function getName() {/*{{{*/
        return $this->name;
    }/*}}}*/
    public function getHumanName() {/*{{{*/
        $this->parseSource();
        if (is_null($this->humanName)) $this->name = 'No plugin name defined in plugin source';
        return $this->humanName;
    }/*}}}*/
    public function getUsageSummary() {/*{{{*/
        $this->parseSource();
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/markdown.php');
        return \Markdown(strtr(
            $this->usageSummary, 
            array('*\\/' => '*/'))
        );
    }/*}}}*/
    protected function parseSource() {/*{{{*/
        $tokens = token_get_all(file_get_contents($this->mainFile));
        foreach ($tokens as $i => $token) if (is_array($token)) $tokens[$i][3] = token_name($token[0]);
        $this->parseMainDocBlock($tokens);
    }/*}}}*/
    protected function parseMainDocBlock($tokens) {/*{{{*/
        if ($this->parsedDocBlock) return;
        $parsedDocBlock = true;
 
        $mainDocBlock = null;
        $i=0;
        while (is_null($mainDocBlock) && $i < count($tokens)) {
            if (is_string($tokens[$i]) && $tokens[$i] == "{") {
                $mainDocBlock = false;
            } else if (is_array($tokens[$i]) && $tokens[$i][0] == T_COMMENT && substr($tokens[$i][1], 0, 3) == '/*#') {
                $mainDocBlock = $tokens[$i][1];
            }
            $i++;
        }
        if ($mainDocBlock === false || is_null($mainDocBlock)) {
            $mainDocBlock = null;
            $i=count($tokens)-1;
            while (is_null($mainDocBlock) && $i >= 0) {
                if (is_string($tokens[$i]) && $tokens[$i] == "}") {
                    $mainDocBlock = false;
                } else if (is_array($tokens[$i]) && $tokens[$i][0] == T_COMMENT && substr($tokens[$i][1], 0, 3) == '/*#') {
                    $mainDocBlock = $tokens[$i][1];
                }
                $i--;
            }
        }
        if ($mainDocBlock === false || is_null($mainDocBlock)) return;
        $mainDocBlock = explode("\n", $mainDocBlock);
        foreach ($mainDocBlock as $no => $line) {
            $line = preg_replace('_^/\*#|\*/_', '', $line);
            $line = preg_replace('_^\s*\*\s?_', '', $line);
            $line = preg_replace('_\s+$_', '', $line);

            $mainDocBlock[$no] = $line;
        }
        while (count($mainDocBlock) && $mainDocBlock[0] == "") { 
            unset($mainDocBlock[0]);
            $mainDocBlock = array_values($mainDocBlock);
        }
        while (count($mainDocBlock) && $mainDocBlock[count($mainDocBlock) - 1] == "") { 
            unset($mainDocBlock[count($mainDocBlock) - 1]);
        }

        /* Consume known @ lines */
        for ($line = count($mainDocBlock) - 1; $line >= 0; $line--) {
            foreach ($this->knownDocblockTags as $tag) {
                if (preg_match('_^\s*@' . $tag . '\s*(.*)$_', $mainDocBlock[$line], $matches)) {
                    if (isset($this->docBlockTagValues[$tag]) && !is_array($this->docBlockTagValues[$tag])) $this->docBlockTagValues[$tag] = array($this->docBlockTagValues[$tag]);
                    if (isset($this->docBlockTagValues[$tag])) {
                        $this->docBlockTagValues[$tag][] = $matches[1];
                    } else {
                        $this->docBlockTagValues[$tag] = $matches[1];
                    }
                    unset($mainDocBlock[$line]);
                    continue 2;
                }
            }
        }

        $start = min(0, count($mainDocBlock) - 1);
        while (count($mainDocBlock) > $start && $mainDocBlock[$start] == "") $start++;
        $this->humanName = $mainDocBlock[$start];
        $start++;
        while (count($mainDocBlock) > $start && $mainDocBlock[$start] == "") $start++;
        $end = $start;
        while (count($mainDocBlock) > $end + 1 && $mainDocBlock[$end + 1] != "") $end++;
        $this->shortDescription = '';
        for ($i=$start; $i<=$end && isset($mainDocBlock[$i]); $i++) $this->shortDescription .= $mainDocBlock[$i] . "\n";

        $start = $end+1;
        $end = count($mainDocBlock) - 2;
        if ($start < $end) {
            $this->usageSummary = '';
            for ($i=$start; $i<=$end && isset($mainDocBlock[$i]); $i++) $this->usageSummary .= $mainDocBlock[$i] . "\n";
        }

        if (count($mainDocBlock) == 0) return;
        $this->name = $mainDocBlock[0];
    }/*}}}*/
    public function getPHPDoc() {/*{{{*/
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/markdown.php');
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/phpdoc.php');
        $phpDocParser = new \com\sergiosgc\phpdoc\PhpParser();

        $pluginFiles = $this->fileList;
        /*#
         * Allow for the list of plugin files to be documented to be mangled
         */
        $pluginFiles = \ZeroMass::getInstance()->do_callback('com.sergiosgc.zeromass.plugin.phpdoc.getallSourceFiles', $pluginFiles);
        $phpDoc = array();
        foreach ($pluginFiles as $file) {
            $phpDoc = $phpDocParser->parseFile($file, $phpDoc);
        }
        if (!isset($phpDoc['classes'])) $phpDoc['classes'] = array();
        if (!isset($phpDoc['namespaces'])) $phpDoc['namespaces'] = array();
        ksort($phpDoc['classes']);
        ksort($phpDoc['namespaces']);
        ob_start();
        printf('<h1>%s</h1>', __('%s PHPDoc', $this->name));
        print('<ul class="nav nav-pills nav-stacked" id="phpdoc-toc">');
        ksort($phpDoc['classes']);
        ksort($phpDoc['namespaces']);
        foreach ($phpDoc['namespaces'] as $namespaceName => $namespaceDoc) {
            printf('<li><a href="#namespace-%s">%s</a>', $namespaceName, $namespaceName);
            print('<ul class="nav nav-pills nav-stacked">');
            foreach ($namespaceDoc['functions'] as $functionName => $functionDoc) {
                printf('<li><a href="#function-%s">%s<br><span class="function-summary">%s</span></a></li>', $namespaceName . $functionName, $functionName, $functionDoc['summary']);
            }
            print('</ul>');
        }
        foreach ($phpDoc['classes'] as $className => $classDoc) {
            printf('<li><a href="#class-%s">%s<br><span class="class-summary">%s</span></a>', $className, $className, $classDoc['summary']);
            print('<ul class="nav nav-pills nav-stacked">');
            foreach ($classDoc['methods'] as $methodName => $methodDoc) {
                printf('<li><a href="#method-%s.%s">%s<br><span class="method-summary">%s</span></a></li>', $className, $methodName, $methodName, $methodDoc['summary']);
            }
            print('</ul>');
        }
        print('</ul>');
        foreach ($phpDoc['namespaces'] as $namespaceName => $namespaceDoc) {
            printf('<a name="namespace-%s">', $namespaceName);
            foreach ($namespaceDoc['functions'] as $functionName => $functionDoc) {
                printf('<a name="function-%s"></a>', $namespaceName . $functionName);
                printf('<h2>%s</h2>', $functionName);
                printf('<div class="function-description">%s</div>', \Markdown(strtr($functionDoc['description'], array('*\\/' => '*/'))) );
                printf('<h3>%s</h3>', __('Arguments'));
                print('<ul class="list-group">');
                foreach($functionDoc['arguments'] as $argName => $argDoc) {
                    printf('<li class="list-group-item">%s%s</li>', $argName, isset($argDoc['phpdoc']) ? (': ' . $argDoc['phpdoc']) : '');
                }
                print('</ul>');
                if (isset($functionDoc['return'])) {
                    printf('<h3>%s</h3>', __('Returns'));
                    printf('<div class="function-return">%s</div>', $functionDoc['return']);
                }
            }
        }
        foreach ($phpDoc['classes'] as $className => $classDoc) {
            printf('<a name="class-%s">', $className);
            foreach ($classDoc['methods'] as $methodName => $methodDoc) {
                printf('<a name="method-%s.%s"></a>', $className, $methodName);
                printf('<h2>%s::%s</h2>',$className, $methodName);
                printf('<div class="method-description">%s</div>',  \Markdown(strtr($methodDoc['description'], array('*\\/' => '*/'))) );
                printf('<h3>%s</h3>', __('Arguments'));
                print('<ul class="list-group">');
                foreach($methodDoc['arguments'] as $argName => $argDoc) {
                    printf('<li class="list-group-item">%s%s</li>', $argName, isset($argDoc['phpdoc']) ? (': ' . $argDoc['phpdoc']) : '');
                }
                print('</ul>');
                if (isset($methodDoc['return'])) {
                    printf('<h3>%s</h3>', __('Returns'));
                    printf('<div class="method-return">%s</div>', $methodDoc['return']);
                }
            }
        }
        return ob_get_clean();
    }/*}}}*/
    public function getHooksDoc() {/*{{{*/
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/phpdoc.php');
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/markdown.php');
        $phpDocParser = new \com\sergiosgc\phpdoc\PhpParser();

        $pluginFiles = $this->fileList;
        $pluginFiles = \ZeroMass::getInstance()->do_callback('com.sergiosgc.zeromass.plugin.phpdoc.getallSourceFiles', $pluginFiles);
        $phpDoc = array();
        foreach ($pluginFiles as $file) {
            $phpDoc = $phpDocParser->parseFile($file, $phpDoc);
        }
        if (!isset($phpDoc['hooks'])) $phpDoc['hooks'] = array();
        if (!isset($phpDoc['classes'])) $phpDoc['classes'] = array();
        if (!isset($phpDoc['namespaces'])) $phpDoc['namespaces'] = array();
        ob_start();
        printf('<h1>%s</h1>', __('%s hooks', $this->name));
        print('<div class="list-group" id="hookdoc-toc">');
        ksort($phpDoc['hooks']);
        foreach($phpDoc['hooks'] as $name => $properties) {
            printf('<a href="#hook-%s" class="list-group-item">%s<br><span class="hook-summary">%s</span></a>', $name, $name, $properties['phpdoc']['summary']);
        }
        print('</div>');
        foreach($phpDoc['hooks'] as $name => $properties) {
            printf('<a name="hook-%s"></a>', $name);
            printf('<h2>%s</h2>', $name);
            if ($properties['phpdoc']['description']) printf('<h3>%s</h3><div class="hook-description">%s</div>', __('Description'), \Markdown(strtr($properties['phpdoc']['description'], array('*\\/' => '*/'))));
            if ($properties['phpdoc']['params']) {
                printf('<h3>%s</h3>', __('Parameters'));
                printf('<div class="hook-params">');
                print('<ul class="list-group">');
                foreach($properties['phpdoc']['params'] as $param) printf('<li class="list-group-item">%s</li>', $param);
                print('</ul>');
                printf('</div>');
            }
            if ($properties['phpdoc']['return']) {
                printf('<h3>%s</h3><div class="hook-returns">%s</div>', __('Returns'), $properties['phpdoc']['return']);
            }
            printf('<h3>%s</h3>', __('Capture code'));
            print('<div class="hook-capture">');
            if ($properties['phpdoc']['params']) {
                $signature = '';
                $separator = '';
                foreach ($properties['phpdoc']['params'] as $param) {
                    $signature .= $separator . '$' . $param[0];
                    $separator = ', ';
                }
                printf("<pre><code>    public function %s(%s) {\n        ...\n        return $%s;\n    }</code></pre>", preg_replace('_.*\.([a-zA-Z0-9]*).*_', '\1', $name), $signature, $properties['phpdoc']['params'][0][0]);
            }


            printf("<pre><code>zm_register(\n    '%s',\n    array(\$this, '%s')\n);</code></pre>", $name, preg_replace('_.*\.([a-zA-Z0-9]*).*_', '\1', $name));
            print('</div>');

        }

        return ob_get_clean();
    }/*}}}*/

}
class Exception extends \Exception { }
