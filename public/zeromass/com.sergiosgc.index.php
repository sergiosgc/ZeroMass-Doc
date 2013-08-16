<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class Index {
    protected static $singleton = null;
    /**
     * Singleton pattern instance getter
     *
     * @return Index The singleton
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage', array($this, 'handleRequest'), 20);
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.page.component', array($this, 'handleComponent'), 20);
    }/*}}}*/
    public function handleRequest($handled) {/*{{{*/
        if ($handled) return $handled;

        $candidate = realpath(\ZeroMass::getInstance()->publicDir) . preg_replace('_[?].*_', '', $_SERVER['REQUEST_URI']);
        if ('/' === $candidate[strlen($candidate) - 1]) {
            $candidate .= 'index.zm.php';
        } else {
            $candidate .= '.zm.php';
        }

        if (file_exists($candidate) && is_file($candidate)) {
            include($candidate);
            return true;
        }

        return false;
    }/*}}}*/
    public function handleComponent($handled, $componentName) {/*{{{*/
        if ($handled) return true;

        $candidate = realpath(\ZeroMass::getInstance()->privateDir) . '/pageComponent/' . $componentName;

        if (is_dir($candidate)) $candidate = realpath($candidate) . '/index';
        $candidate .= '.zm.php';

        if (file_exists($candidate)) {
            include($candidate);
            return true;
        }
        return false;
    }/*}}}*/
}

Index::getInstance();

/*#
 * This plugin implements index pages
 *
 * When handling a request, this plugin looks for a file with extension .zm.php and
 * uses it to serve the page. When coupled with com.sergiosgc.page, it can also
 * serve page components much the same way, by looking for a .zm.php component under
 * the components directory.
 *
 * # Usage summary 
 *
 * Drop this plugin on your plugin directory. Then, create files to answer 
 * requests. For URLs that end in a forward slash (http://example.com/dir/)
 * create an index.zm.php file, for all other URLs (http://example.com/dir/someFileName)
 * append .php.zm to the file name. These files should be placed on your site
 * public directory (usually public/ relative to the site root directory).
 *
 * If you use this plugin alongside `com.sergiosgc.page`, you may also 
 * drop page components under the private/pageComponent directory. This
 * plugin will use those files to serve the page component. For a component
 * named `foobar` the file may be `private/pageComponent/foobar.zm.php` or
 * `private/pageComponent/foobar/index.zm.php`.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2013, Sérgio Carvalho
 * @version 1.0
 */
?>
