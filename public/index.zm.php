<?php 
zm_register('com.sergiosgc.page.component', function($handled, $component) {
    if ($component != 'toc') return $handled;
    if ($handled) return $handled;
    $session = \com\sergiosgc\Facility::get('session');
    $knownAppDirs = $session->get('knownAppDirs', false, array());
    $knownAppDirs = array_flip($knownAppDirs);
    foreach ($knownAppDirs as $dir => $dummy) {
        if (strlen($dir) > 50) $knownAppDirs[$dir] = sprintf('...%s', substr($dir, -47)); else $knownAppDirs[$dir] = $dir;
    }
    print('<aside class="bs-sidebar" role="complementary">');
    print('<ul class="nav nav-stacked bs-sidenav">');
    foreach($knownAppDirs as $dir => $label) {
        printf('<li><a href="/actions/addappdir/?appdir=%s">%s</a></li>', urlencode($dir), $label);
    }
    print('</ul>');
    print('</aside>');
    return true;
});
zm_fire('com.sergiosgc.contentType', 'text/html');
?>
<h1>Select ZeroMass based project</h1>
<p>Either pick a known project from the list on the left, or type in the path to the application directory of your ZeroMass based project.</p><p>The application directory is the directory containing <code>public</code> and <code>private</code> subdirectories.
<?php
$form = new com\sergiosgc\form\Form('/actions/addappdir/', 'POST');
$form->addField('appdir');
$form->getField('appdir')->setLabel('Directory');
$form->getField('appdir')->setHelpText('ZeroMass Application Directory');
$form->getField('appdir')->setPlaceholderText('/srv/www/...');
$form->addSubmitTarget('submit', __('Read App Docs'));
$bootstrap = new com\sergiosgc\form\Serializer_Bootstrap();
$bootstrap->setLayout('inline');
$bootstrap->serialize($form);
?>
