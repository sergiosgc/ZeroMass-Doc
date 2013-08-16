<?php
function subpage_of($uri) {
    return strlen($_SERVER['REQUEST_URI']) >= $uri && substr($_SERVER['REQUEST_URI'], 0, strlen($uri)) == $uri;
}
print('<ol class="breadcrumb">');
if ($_SERVER['REQUEST_URI'] == '/') {
    printf('<li class="active">%s</li>', __('Home'));
} else {
    if (subpage_of('/plugins/')) {
        printf('<li><a href="/">%s</a></li>', __('Home'));
        $appdir = \com\sergiosgc\Facility::get('session')->get('currentAppDir');
        if (strlen($appdir) > 20) $appdir = sprintf('...%s', substr($appdir, -17));
        printf('<li class="active">%s</li>', $appdir);
    } elseif (subpage_of('/plugin/')) {
        printf('<li><a href="/">%s</a></li>', __('Home'));
        $appdir = \com\sergiosgc\Facility::get('session')->get('currentAppDir');
        printf('<li><a href="/actions/addappdir/?appdir=%s">%s</li>', urlencode($appdir), strlen($appdir) > 20 ? sprintf('...%s', substr($appdir, -17)) : $appdir );
        printf('<li><a href="/plugin/?name=%s">%s</a>', urlencode($_REQUEST['name']), $_REQUEST['name']);
        if (subpage_of('/plugin/phpdoc')) {
            printf('<li class="active">%s</li>', __('PHPDoc'));
        } elseif (subpage_of('/plugin/hooks/')) {
            printf('<li class="active">%s</li>', __('Hooks'));
        } else {
            printf('<li class="active">%s</li>', __('Usage summary'));
        }

    }
}

print('</ol>');
?>
 
