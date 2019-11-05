<?php
/*
Plugin Name: XPERSER
Author: Yamamoto
Version: 0.2
License: GPL2
Author URI: http://kyms.jp
*/

if(is_admin()) {
    return;
}
function xparser() {
    include_once('includes/xparser.class.inc.php');
    return new XPARSE();
}

add_action(
    'plugins_loaded'
    , function() {
        ob_start();
    }
);
add_action(
    'shutdown'
    , function() {
        echo xparser()->parse(ob_get_clean());
    }
    , -1
);
