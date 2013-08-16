<?php
if (isset($_REQUEST['appdir'])) {
    $session = \com\sergiosgc\Facility::get('session');
    $appDir = $_REQUEST['appdir'];
    $match = find_file($appDir, 'com.sergiosgc.zeromass.php', 3);
    if (!$match) {
        $session->set('form-errors', array('appdir' => __('The directory is not an application directory')));
        $session->set('form-values', $_REQUEST);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    $knownAppDirs = $session->get('knownAppDirs', false, array());
    $knownAppDirs[] = $appDir;
    $knownAppDirs = array_values(array_flip(array_flip($knownAppDirs)));
    $session->set('knownAppDirs', $knownAppDirs, 3600 * 24 * 30);
    $session->set('currentAppDir', $appDir);
    header(sprintf('Location: /plugins/'));
}
?>
