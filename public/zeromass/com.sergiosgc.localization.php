<?php 
function _e() {
    print call_user_func_array('__', func_get_args());
}
function __() {
    $args = func_get_args();
    $args[0] = \ZeroMass::getInstance()->do_callback('com.sergiosgc.localization', $args[0]);

    return call_user_func_array('sprintf', $args);
}
