<?php
if (strlen($_SERVER['REQUEST_URI']) > strlen('/plugin/') && substr($_SERVER['REQUEST_URI'], 0, strlen('/plugin/')) == '/plugin/') {
    print('<aside class="bs-sidebar" role="complementary">');
    print('<ul class="nav nav-stacked bs-sidenav">');
    foreach(array('/plugin/?name=%s' => __('Usage summary'), '/plugin/phpdoc/?name=%s' => __('PHPDoc'), '/plugin/hooks/?name=%s' => __('Hooks')) as $uri => $label) {
        printf('<li><a href="%s">%s</a></li>', sprintf($uri, $_REQUEST['name']), $label);
    }
    print('</ul>');
    print('</aside>');
}
?>
