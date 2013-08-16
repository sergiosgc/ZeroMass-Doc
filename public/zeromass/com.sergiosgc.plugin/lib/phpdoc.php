<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\phpdoc;
/**
 * PhpParser is a simplistic/fast PHPDoc parser for use in ZeroMass Plugin documentation
 */
class PhpParser {
    private $current_hook = null;
    private $current_class = null;
    private $current_docblock = null;
    private $current_namespace = null;
    private $super_token = null;
    private $in_php = false;
    private $block_depth = 0;
    private $current_visibility = 'public';

    /**
     * Parse a string as PHP source and return an array of extracted PHPDoc documentation
     *
     * @param string PHP source 
     * @param array Result accumulator
     * @return array PHPDoc documentation
     */
    public function parseSource($source, $result = null) {/*{{{*/
        if (is_null($result)) $result = array(
            'namespaces' => array(),
            'classes' => array(),
            'hooks' => array(),
        );
        $tokens = token_get_all($source);
        $this->resetParserState();
        foreach ($tokens as $token) $result = $this->consumeToken($token, $result);
        if (isset($result['classes'])) foreach (array_keys($result['classes']) as $class) {
            if ((!isset($result['classes'][$class]['variables']) || count($result['classes'][$class]['variables']) == 0) && 
                (!isset($result['classes'][$class]['methods']) || count($result['classes'][$class]['methods']) == 0)) {
                unset($result['classes'][$class]);
            }
        }
        if (isset($result['namespaces']) && is_array($result['namespaces'])) {
            ksort($result['namespaces']);
            foreach(array_keys($result['namespaces']) as $namespace) {
                if (isset($result['namespaces'][$namespace]['functions'])) ksort($result['namespaces'][$namespace]['functions']);
            }
        }
        if (isset($result['classes']) && is_array($result['classes'])) {
            ksort($result['classes']);
            foreach(array_keys($result['classes']) as $class) {
                if (isset($result['classes'][$class]['variables'])) ksort($result['classes'][$class]['variables']);
                if (isset($result['classes'][$class]['methods'])) ksort($result['classes'][$class]['methods']);
            }
        }
        if (isset($result['namespaces']) && is_array($result['namespaces'])) ksort($result['namespaces']);
        return $result;
    }/*}}}*/
    public function parseFile($file, $resultAccumulator = null) {/*{{{*/
        /*#
         * Allow mangling of the file to be parsed
         */
        $file = \ZeroMass::getInstance()->do_callback("com.sergiosgc.phpdoc.parseFile.preFile", $file);
        return $this->parseSource(file_get_contents($file), $resultAccumulator);
    }/*}}}*/
    public function resetParserState() {/*{{{*/
        $this->current_hook = $this->current_class = $this->current_docblock = $this->current_namespace = $this->super_token = null;
        $this->in_php = false;
        $this->block_depth = 0;
        $this->current_visibility = 'public';
    }/*}}}*/
    public function consumeToken($token, $result) {/*{{{*/
        if (!$this->in_php && is_array($token) && token_name($token[0]) == 'T_OPEN_TAG') {
            $this->in_php = true;
            return $result;
        }
        if (!$this->in_php) return $result;

        // Strings (block open and close)
        if (is_string($token) && $token == '{') {
            $this->block_depth++;
            $this->current_docblock = null;
        }
        if (is_string($token) && $token == '}') {
            $this->block_depth--;
            $this->current_docblock = null;
            if ($this->current_class && $this->current_class_block_depth == $this->block_depth) $this->current_class = null;
        }
        if (is_string($token) && $token == '.' && $this->current_hook && $this->current_hook['hook_name']) {
            if (substr($this->current_hook['hook_name'], -1) != '*') $this->current_hook['hook_name'] .= '*';
            return $result;
        }
        if (is_string($token) && ($token == ',' || $token == ')') && $this->current_hook && $this->current_hook['hook_name']) {
            if (!isset($result['hooks'][$this->current_hook['hook_name']]) || 
                 (is_array($result['hooks'][$this->current_hook['hook_name']]['phpdoc']) && 0 == count($result['hooks'][$this->current_hook['hook_name']]['phpdoc'])) ||
                 (!is_array($result['hooks'][$this->current_hook['hook_name']]['phpdoc']) && ('' . $result['hooks'][$this->current_hook['hook_name']]['phpdoc']) == '')) {
                $result['hooks'][$this->current_hook['hook_name']] = array(
                    'phpdoc' => $this->current_hook['phpdoc']
                );
                 }
            $this->current_hook = null;
            $this->current_docblock = null;

            return $result;
        }
        if (is_string($token) && $token == ';' && $this->super_token && $this->super_token[0] == 'T_NAMESPACE') {
            $this->current_namespace = $this->super_token[1];
            $this->super_token = null;
            $this->current_visibility = 'public';
            return $result;
        }
        if (is_string($token) && $token == ')' && $this->super_token && $this->super_token[0] == 'T_FUNCTION') {
            if ($this->current_visibility != 'public') {
                $this->super_token = null;
                return $result;
            }
            if ($this->current_docblock) {
                $docBlockToMerge = array(
                    'summary' => $this->current_docblock['summary'],
                    'description' => $this->current_docblock['description'],
                    'return' => $this->current_docblock['return']
                );
                if (isset($this->current_docblock['params']) && is_array($this->current_docblock['params'])) {
                    $argumentsKeys = array_keys($this->super_token['args']);
                    foreach($this->current_docblock['params'] as $i => $description) if (isset($argumentsKeys[$i])) {
                        $this->super_token['args'][$argumentsKeys[$i]]['phpdoc'] = $description;
                    }
                }
            } else {
                $docBlockToMerge = array(
                    'summary' => null,
                    'description' => null,
                    'return' => null
                );
            }
            if ($this->current_class) {
                $result['classes'][$this->current_class]['methods'][$this->super_token[1]] = array(
                    'arguments' => $this->super_token['args']
                );
                $result['classes'][$this->current_class]['methods'][$this->super_token[1]] = array_merge(
                    $result['classes'][$this->current_class]['methods'][$this->super_token[1]], 
                    $docBlockToMerge
                );
            } else {
                if ($this->current_namespace) {
                    $result['namespaces'][$this->current_namespace]['functions'][$this->super_token[1]] = array(
                        'arguments' => $this->super_token['args']
                    );
                    $result['namespaces'][$this->current_namespace]['functions'][$this->super_token[1]] = array_merge(
                        $result['namespaces'][$this->current_namespace]['functions'][$this->super_token[1]] ,
                        $docBlockToMerge
                    );
                } else {
                    $result['namespaces']['\\']['functions'][$this->super_token[1]] = array(
                        'arguments' => $this->super_token['args']
                    );
                    $result['namespaces']['\\']['functions'][$this->super_token[1]] = array_merge(
                        $result['namespaces']['\\']['functions'][$this->super_token[1]],
                        $docBlockToMerge
                    );
                } 
            }
            $this->super_token = null;
            return $result;
        }
        if (is_string($token)) return $result;
        
        // Real tokens
        if (token_name($token[0]) == 'T_CLOSE_TAG') {
            $this->in_php = false;
            return $result;
        }
        if ((token_name($token[0]) == 'T_COMMENT' && $this->isPHPDocComment($token[1])) || token_name($token[0]) == 'T_DOC_COMMENT') {
            $this->current_docblock = $this->parsePHPDoc($token[1]);
            return $result;
        }
        if ((token_name($token[0]) == 'T_COMMENT' && $this->isZeroMassDocComment($token[1]))) {
            $this->current_docblock = $this->parseZeroMassDoc($token[1]);
            return $result;
        }
        if (token_name($token[0]) == 'T_COMMENT') {
            return $result;
        }
        if (token_name($token[0]) == 'T_WHITESPACE') {
            return $result;
        }

        // Namespace parsing
        if (token_name($token[0]) == 'T_NAMESPACE') {
            $this->super_token = array('T_NAMESPACE', '\\');
            return $result;
        }
        if ($this->super_token && $this->super_token[0] == 'T_NAMESPACE' && token_name($token[0]) == 'T_NS_SEPARATOR') {
            $this->super_token[1] .= '\\';
            return $result;
        }
        if ($this->super_token && $this->super_token[0] == 'T_NAMESPACE' && token_name($token[0]) == 'T_STRING') {
            $this->super_token[1] .= $token[1];
            return $result;
        }

        // Class parsing
        if (token_name($token[0]) == 'T_CLASS') {
            $this->super_token = array('T_CLASS', '\\');
            return $result;
        }
        if ($this->super_token && $this->super_token[0] == 'T_CLASS' && token_name($token[0]) == 'T_STRING') {
            $fullClassname = ($this->current_namespace ? $this->current_namespace . '\\' : '') . $token[1];
            $this->current_class = $fullClassname;
            $this->current_class_block_depth = $this->block_depth;
            $result['classes'][$fullClassname] = array(
                'variables' => array(),
                'methods' => array(),
                'phpdoc' => $this->current_docblock ? $this->current_docblock : false
            );
            if ($this->current_docblock) {
                $docBlockToMerge = array(
                    'summary' => $this->current_docblock['summary'],
                    'description' => $this->current_docblock['description'],
                );
            } else {
                $docBlockToMerge = array(
                    'summary' => null,
                    'description' => null,
                );
            }
            $result['classes'][$fullClassname] = array_merge(
                $result['classes'][$fullClassname],
                $docBlockToMerge
            );
            $this->super_token = null;
            return $result;
        }

        // Visibility
        if (token_name($token[0]) == 'T_PRIVATE') {
            $this->current_visibility = 'private';
            return $result;
        }
        if (token_name($token[0]) == 'T_PUBLIC') {
            $this->current_visibility = 'public';
            return $result;
        }
        if (token_name($token[0]) == 'T_PROTECTED') {
            $this->current_visibility = 'protected';
            return $result;
        }

        // Functions/methods
        if (token_name($token[0]) == 'T_FUNCTION') {
            $this->super_token = array('T_FUNCTION', '', 'args' => array());
            return $result;
        }
        if ($this->super_token && $this->super_token[0] == 'T_FUNCTION' && token_name($token[0]) == 'T_STRING' && $this->super_token[1] == '') {
            $this->super_token[1] = $token[1];
            return $result;
        }
        if ($this->super_token && $this->super_token[0] == 'T_FUNCTION' && token_name($token[0]) == 'T_VARIABLE') {
            $this->super_token['args'][$token[1]] = array();
            return $result;
        }

        // Class fields
        if (token_name($token[0]) == 'T_VARIABLE' && $this->current_class && $this->block_depth == 1 && $this->current_visibility == 'public') {
            $result['classes'][$this->current_class]['variables'][$token[1]] = array(
                'phpdoc' => $this->current_docblock ? $this->current_docblock : false
            );
            return $result;
        }

        // Hook calls
        // \ZeroMass::getInstance()->do_callback
        // ^
        if (token_name($token[0]) == 'T_NS_SEPARATOR' && is_null($this->current_hook)) {
            $this->current_hook = array(
                'last_token' => $token,
                'hook_name' => null,
                'phpdoc' => $this->current_docblock ? $this->current_docblock : false
            );
            return $result;
        }
        if ($this->current_hook) {
            // \ZeroMass::getInstance()->do_callback
            //  ^^^^^^^^
            if (token_name($this->current_hook['last_token'][0]) == 'T_NS_SEPARATOR') {
                if (token_name($token[0]) == 'T_STRING' && $token[1] == 'ZeroMass') {
                    $this->current_hook['last_token'] = $token;
                } else {
                    $this->current_hook = null;
                }
                return $result;
            }
            // \ZeroMass::getInstance()->do_callback
            //          ^^
            if (token_name($this->current_hook['last_token'][0]) == 'T_STRING' && $this->current_hook['last_token'][1] == 'ZeroMass') {
                if (token_name($token[0]) == 'T_DOUBLE_COLON') {
                    $this->current_hook['last_token'] = $token;
                } else {
                    $this->current_hook = null;
                }
                return $result;
            }
            // \ZeroMass::getInstance()->do_callback
            //            ^^^^^^^^^^^
            if (token_name($this->current_hook['last_token'][0]) == 'T_DOUBLE_COLON') {
                if (token_name($token[0]) == 'T_STRING' && $token[1] == 'getInstance') {
                    $this->current_hook['last_token'] = $token;
                } else {
                    $this->current_hook = null;
                }
                return $result;
            }
            // \ZeroMass::getInstance()->do_callback
            //                         ^^
            if (token_name($this->current_hook['last_token'][0]) == 'T_STRING' && $this->current_hook['last_token'][1] == 'getInstance') {
                if (token_name($token[0]) == 'T_OBJECT_OPERATOR') {
                    $this->current_hook['last_token'] = $token;
                } else {
                    $this->current_hook = null;
                }
                return $result;
            }
            // \ZeroMass::getInstance()->do_callback
            //                           ^^^^^^^^^^^
            if (token_name($this->current_hook['last_token'][0]) == 'T_OBJECT_OPERATOR') {
                if (token_name($token[0]) == 'T_STRING' && ($token[1] == 'do_callback' || $token[1] == 'do_callback_array')) {
                    $this->current_hook['last_token'] = $token;
                } else {
                    $this->current_hook = null;
                }
                return $result;
            }
            // \ZeroMass::getInstance()->do_callback('name'
            //                                       ^^^^^^
            if (token_name($this->current_hook['last_token'][0]) == 'T_STRING' && $this->current_hook['last_token'][1] == 'do_callback') {
                if (token_name($token[0]) == 'T_CONSTANT_ENCAPSED_STRING') {
                    $this->current_hook['last_token'] = $token;
                    $this->current_hook['hook_name'] = preg_replace("(^['\"]|['\"]$)", "", $token[1]);
                } else {
                    $this->current_hook = null;
                }
                return $result;
            }
        }
        return $result;
    }/*}}}*/
    public function isPHPDocComment($comment) {/*{{{*/
        return strlen($comment) > 3 && substr($comment, 0, 3) == ('/' . '**'); // The idiotic string append is here because of vim's idiotic parser
    }/*}}}*/
    public function parsePHPDoc($comment) {/*{{{*/
        return $this->parseZeroMassDoc($comment);
    }/*}}}*/
    public function isZeroMassDocComment($comment) {/*{{{*/
        return strlen($comment) > 3 && substr($comment, 0, 3) == ('/' . '*#'); // The idiotic string append is here because of vim's idiotic parser
    }/*}}}*/
    public function parseZeroMassDoc($comment) {/*{{{*/
        $comment = explode("\n", $comment);
        foreach($comment as $index => $line) {
            $comment[$index] = preg_replace('_^\s*[/][*][#]\s?|\s*[/][*][*]\s?|\s*[*]/\s*|\s*[*] ?_', '', $line);
        }
        $tagText = array();
        foreach($comment as $index => $line) {
            if (substr($line, 0, 1) == '@') $tagText[] = $index;
        }
        $tagText = array_values(array_reverse($tagText));
        foreach (array_reverse($tagText) as $i => $lineIndex) {
            $tagText[$i] = $comment[$lineIndex]; 
            unset($comment[$lineIndex]);
        }
        $comment = array_values($comment);
        while (count($comment) && $comment[0] == "") array_shift($comment);

        if (count($comment)) {
            $firstLine = $comment[0];
            array_shift($comment);
            while (count($comment) && $comment[0] == "") array_shift($comment);
            for ($i=0; $i < count($comment); $i++) {
                while (isset($comment[$i]) && isset($comment[$i+1]) && "" == $comment[$i] && "" == $comment[$i+1]) {
                    unset($comment[$i]);
                    $comment = array_values($comment);
                }
            }
        } else {
            $firstLine = null;
        }

        if (count($comment)) {
            $description = implode("\n", $comment);
        } else {
            $description = false;
        }

        $tags = array();
        foreach ($tagText as $text) {
            preg_match('_@([a-zA-Z]*)\s(.*)_', $text, $matches);
            $tag = $matches[1];
            $value = $matches[2];
            if (!isset($tags[$tag])) {
                $tags[$tag] = $value;
            } else {
                if (!is_array($tags[$tag])) $tags[$tag] = array($tags[$tag]);
                $tags[$tag][] = $value;
            }
        }

        unset($tagText);
        if (isset($tags['param']) && !is_array($tags['param'])) $tags['param'] = array($tags['param']);
        if (isset($tags['return']) && is_array($tags['return'])) $tags['return'] = $tags['return'][0];

        $result = array();
        $result['summary'] = $firstLine ? $firstLine : '';
        $result['description'] = $description ? $description : '';
        $result['return'] = isset($tags['return']) ? $tags['return'] : null;
        unset($tags['return']);
        $result['params'] = isset($tags['param']) ? $tags['param'] : null;
        unset($tags['param']);
        $result['extraTags'] = count($tags) ? $tags : null;

        return $result;
    }/*}}}*/
}
