<?php
zm_fire('com.sergiosgc.contentType', 'text/html');
$appdir = \com\sergiosgc\Facility::get('session')->get('currentAppDir');
$pluginDir = dirname(find_file($appdir, 'com.sergiosgc.zeromass.php', 4));
$plugins = \com\sergiosgc\zeromass\Plugin::getAllPluginFiles($pluginDir);

$plugin = new \com\sergiosgc\zeromass\Plugin($_REQUEST['name'], $plugins[$_REQUEST['name']]);
$summary = $plugin->getHooksDoc();
print($summary);
?>
