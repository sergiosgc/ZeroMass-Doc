<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class Page {
    protected static $singleton = null;
    protected $templatePath = null;
    protected $pageType = 'default';
    protected $primaryOutputHasStarted = false;
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.pluginInit', array($this, 'init'), 5);
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.replaced_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.contentType', array($this, 'contentTypeHandler'));
    }/*}}}*/
    /**
     * Plugin initializer responder to com.sergiosgc.zeromass.pluginInit hook
     */
    public function init() {/*{{{*/
        $this->templatePath = \ZeroMass::getInstance()->privateDir . '/pageTemplates';
        \com\sergiosgc\Facility::register('page', $this);
    }/*}}}*/
    public function config() {/*{{{*/
        $this->templatePath = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.page.templatepath', false, $this->templatePath);
        /*#
         * Filter the templatePath. 
         *
         * @param string The full path to the template directory
         * @return string The full path to the template directory
         */
        $this->templatePath = \ZeroMass::getInstance()->do_callback('com.sergiosgc.page.templatepath', $this->templatePath);
    }/*}}}*/
    public function contentTypeHandler($mime, $encoding = 'utf-8') {/*{{{*/
        static $headersSent = false;
        if ($mime == 'text/html') $this->primaryOutputIsStarting();
        if (!$headersSent) {
            if ($encoding) {
                header('Content-type: ' . $mime . '; charset=' . $encoding);
            } else {
                header('Content-type: ' . $mime);
            }
            $headersSent = true;
        }
        return $mime;
    }/*}}}*/
    public function primaryOutputIsStarting() {/*{{{*/
        if ($this->primaryOutputHasStarted) return;
        $this->primaryOutputHasStarted = true;
        register_shutdown_function(array($this, 'output'));
        ob_start(array($this, 'primaryOutputEnded'));
    }/*}}}*/
    public function primaryOutputEnded($buffer) {/*{{{*/
        // We do not output here, as buffer handlers are finnicky 
        // regarding output during processing, namely error reporting and debugging
        $this->primaryOutput = $buffer;
        return '';
    }/*}}}*/
    public function output() {/*{{{*/
        ob_end_flush();
        $this->parseTemplate();
        foreach ($this->template as $part) {
            if ($part['type'] == 'string') {
                print $part['content'];
                continue;
            }
            if ($part['type'] == 'component') {
                if ($part['name'] == 'default') {
                    print $this->primaryOutput;
                    continue;
                }
                /*#
                 * Request a component that is present in the page template
                 *
                 * In response to a component included in the page template, 
                 * this callback will be called, allowing for plugins to 
                 * provide the component.
                 *
                 * A component is present in the template file if a line 
                 * with this format occurs
                 *
                 *      __component-name__
                 *
                 * Where `component-name` is replaced by the actual component
                 * name. Example:
                 *
                 *      __primaryMenu__
                 *
                 * The expected behaviour is for the component markup to be 
                 * output by callback handlers, not returned.
                 *
                 * @param boolean True if the component has already been handled
                 * @param string Component name
                 * @return boolean True if the component has already been handled
                 */
                \ZeroMass::getInstance()->do_callback('com.sergiosgc.page.component', false, $part['name']);
            }
        }
    }/*}}}*/
    public function parseTemplate() {/*{{{*/
        $templateFile = $this->getTemplateFile();
        $contents = file_get_contents($templateFile);
        if ($contents === false) throw new PageException('Unable to read template file ' . $templateFile);
        $contents = explode("\n", $contents);
        $accumulator = '';
        $this->template = array();
        foreach($contents as $line) {
            if (strlen($line) > 5 && $line[0] == ' ' && $line[1] == '_' && $line[2] == '_' && preg_match('/^ __([^_]+)__/', $line, $matches)) {
                if ($accumulator != '') {
                    $accumulator = substr($accumulator, 0, strlen($accumulator) - 1); // Remove trailing \n
                    $this->template[] = array(
                        'type' => 'string', 
                        'content' => $accumulator
                    );
                    $accumulator = '';
                }
                $this->template[] = array(
                    'type' => 'component',
                    'name' => $matches[1]
                );
            } else {
                $accumulator .= $line . "\n";
            }
        }
        if ($accumulator != '') {
            $accumulator = substr($accumulator, 0, strlen($accumulator) - 1); // Remove trailing \n
            $this->template[] = array(
                'type' => 'string', 
                'content' => $accumulator
            );
            $accumulator = '';
        }
    }/*}}}*/
    protected function getTemplateFile() {/*{{{*/
        if (is_string($this->pageType)) $this->pageType = array($this->pageType);
        foreach ($this->pageType as $pageType) {
            $result = sprintf('%s/%s.template', $this->templatePath, $pageType);
            if (is_file($result)) return $result;
        }
        $message = 'Unable to find template in ' . $this->templatePath . ' for any of the types: ';
        $separator = '';
        foreach ($this->pageType as $pageType) {
            $message .= $separator . $pageType;
            $separator = ',';
        }
        throw new PageException($message);
    }/*}}}*/
}

class PageException extends \Exception { }

Page::getInstance();

/*#
 * Simplistic page template
 *
 * Very simple templating system for creating an HTML page out of the core 
 * content (supposedly produced by other plugins)
 *
 * # Usage summary 
 *
 * Drop this plugin on your plugin directory and create a directory named pageTemplates under
 * the webapp private directory (usually, [document_root]/private)
 *
 * Page templates are named after the page type, with extension .template. For example:
 *
 *     default.template
 *
 * Page templates are just regular HTML, with placeholders for components. A placeholder is
 * a line, with a single space, two underscores, the component name and two underscores. Example:
 *
 *      __menu__
 *
 * You should have one placeholder named `default`, which will receive the primary content of the
 * page. 
 *
 * A very basic HTML template for HTML5 would be:
 *
 *     <!DOCTYPE html>
 *     <html>
 *      <head>
 *      __head__
 *      </head>
 *      <body>
 *      __default__
 *      </body>
 *     </html>
 *
 * ## Primary content
 *
 * Primary content is content produced by plugins in response to `com.sergiosgc.zeromass.answerPage`.
 *
 * Page will only produce output if the main content producer fires a `com.sergiosgc.contentType`
 * hook with an argument of `text/html`.  This is the only collaboration required from the primary
 * content producer. Otherwise, output is unnaffected by Page (which is useful for unimpeded output
 * of `application/json` or `image/png` or any type other than HTML). 
 *
 * Naturally, it may happen that some of your plugins do not collaborate by firing 
 * `com.sergiosgc.contentType`. If this is the case, either capture some hook of 
 * the plugin that occurs before output, or capture `com.sergiosgc.zeromass.answerPage` at a higher
 * priority than the plugin (so you capture it before any output). At your handler, fire
 * `com.sergiosgc.contentType` with an argument of `text/html`.
 *
 * ## Secondary content
 *
 * For secondary content, i.e. content that is not produced in response to `com.sergiosgc.zeromass.answerPage`
 * capture `com.sergiosgc.page.component`, check the requested component type and output the 
 * proper HTML. 
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
