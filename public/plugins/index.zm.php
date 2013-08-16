<?php 
zm_register('com.sergiosgc.page.component', function($handled, $component) {
    if ($component != 'toc') return $handled;
    if ($handled) return $handled;
    $appdir = \com\sergiosgc\Facility::get('session')->get('currentAppDir');
    $pluginDir = dirname(find_file($appdir, 'com.sergiosgc.zeromass.php', 4));
    $plugins = \com\sergiosgc\zeromass\Plugin::getAllPluginFiles($pluginDir);
    print('<aside class="bs-sidebar" role="complementary">');
    print('<ul class="nav nav-stacked bs-sidenav">');
    foreach($plugins as $name => $files) {
    printf('<li><a href="/plugin/?name=%s">%s</a></li>', $name, $name);
}
print('</ul>');
print('</aside>');
    return true;
});
zm_fire('com.sergiosgc.contentType', 'text/html');

$appdir = \com\sergiosgc\Facility::get('session')->get('currentAppDir');
$pluginDir = dirname(find_file($appdir, 'com.sergiosgc.zeromass.php', 4));
$plugins = \com\sergiosgc\zeromass\Plugin::getAllPluginFiles($pluginDir);

$plugin = new \com\sergiosgc\zeromass\Plugin(array_keys($plugins)[0], $plugins[array_keys($plugins)[0]]);

?>
<h1>Installed plugins</h1>
Select a plugin to read its documentation
<table class="table table-striped table-bordered table-hover">
 <thead>
  <tr>
   <th>Id</th>
   <th>Name</th>
   <th>Description</th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($plugins as $id => $files) {
    $plugin = new \com\sergiosgc\zeromass\Plugin($id, $files);
?>
  <tr>
   <td><?php printf('<a href="/plugin/?name=%s">%s</a>', $plugin->getName(), $plugin->getName()) ?></td>
   <td><?php echo $plugin->getHumanName() ?></td>
   <td><?php echo $plugin->getShortDescription() ?></td>
  <tr>
<?php } ?>
 </tbody>
</table>

