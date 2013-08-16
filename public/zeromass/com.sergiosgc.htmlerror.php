<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc;

class HtmlError {
    protected static $singleton = null;
    /**
     * Singleton pattern instance getter
     * @return Config The singleton Config
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.zeromass.answerPage.exception', array($this, 'handleException'));
    }/*}}}*/
    protected function outputPage($buffer) {/*{{{*/
        header('Content-type: text/html; charset=utf-8');
        printf(<<<EOS
<!DOCTYPE html>
<html>
 <head>
 <title>%s</title>
  <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
  <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
  <style type="text/css">
  </style>
 </head>
 <body>
  <div class="container-fluid">
   <div class="row-fluid">
    <div class="span12">
%s
    </div>
   </div>
  </div>
  <footer class="footer" style="background-color: #F5F5F5; border-top: 1px solid #E5E5E5; margin-top: 70px; padding: 70px 0">
   <div class="container">
    <p class="pull-right" style="color: #777777"><a href="#">Back to top</a></p>
   </div>
  </footer>
 </body>
</html>
EOS
        , $this->title, $buffer);
    }/*}}}*/
    /**
     * Handler for com.sergiosgc.zeromass.answerPage.exception that outputs the error in HTML
     *
     * @param exception The thrown exception
     * @return boolean false, stating that the exception has been handled
     */
    public function handleException($e) {/*{{{*/
        if ($e instanceof \ZeroMassNotFoundException) {
            header('HTTP/1.0 404 Not Found');
        } else {
            header('HTTP/1.0 500 Internal Server Error. Uncaught exception while answering request');
        }
        $this->title = 'Exception while handling page';
        ob_start();
?>
    <div class="alert alert-error">
    <strong>Error!</strong> An exception was thrown when handling the request:
    </div>
<h1>Message</h1>
<?php print is_callable(array($e, 'getHtmlMessage')) ? $e->getHtmlMessage() : ('<pre><tt>' . $e->getMessage() . '</tt></pre>'); 
$previous = $e;
while ($previous = $previous->getPrevious()) {
    print('<blockquote>');
    print is_callable(array($previous, 'getHtmlMessage')) ? $previous->getHtmlMessage() : ('<pre><tt>' . $previous->getMessage() . '</tt></pre>'); 
}
$previous = $e;
while ($previous = $previous->getPrevious()) {
    print('</blockquote>');
}
?>

<h1>Stack trace</h1>
<table class="table">
 <tr>
  <th rowspan="2">Depth</th>
  <th colspan="2">File</th>
 </tr>
 <tr>
  <th>Function</th>
  <th>Line</th>
 </tr>
<?php foreach($e->getTrace() as $depth => $line) { ?>
 <tr <?php if ($depth % 2) echo 'style="background-color: #eeeeff"';?>><td rowspan="2"><?php echo $depth ?></td><td colspan="2"><?php echo isset($line['file']) ? $line['file'] : ''; ?></td></tr>
 <tr <?php if ($depth % 2) echo 'style="background-color: #eeeeff"';?>><td>
<?php if (isset($line['class'])) { ?>
<?php echo $line['class']; echo $line['type']; ?>
<?php } ?>
<?php echo $line['function']; ?>(
<?php 
        $separator = '';
        foreach ($line['args'] as $arg) {
            print $separator;
            $separator = ',';
            if (is_string($arg)) echo "'" . $arg . "'"; elseif (is_array($arg)) { echo 'Array'; } else echo ((string) $arg);
        }
?>)
</td><td><?php echo isset($line['line']) ? $line['line'] : ''; ?></td></tr>
<?php } ?>
</table>
<?php
        $this->outputPage(ob_get_clean());
        return false;
    }/*}}}*/
}

HtmlError::getInstance();

/*#
 * Nicer looking exception handler for ZeroMass
 *
 * Handler for exceptions thrown in ZeroMass::answerPage that produces a nicer-looking page than the default PHP
 *
 * # Usage summary 
 *
 * Just drop the plugin into your plugin directory. If page execution produces an exception, this plugin will produce
 * a nicer looking page.
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
