<?php
function find_file($dir, $name, $maxdepth) {
    if ($maxdepth == -1) return false;
    if (!is_dir($dir)) return false;
    $dh = opendir($dir);
    while (($file = readdir($dh)) !== false) {
        if ($file == '.' || $file == '..') continue;
        if ($file == $name) return $dir . '/' . $name;
        $match = find_file($dir . '/' . $file, $name, $maxdepth - 1);
        if ($match) return $match;
    }
    return false;
}


