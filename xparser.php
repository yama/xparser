<?php
/*
Plugin Name: XPERSER
Author: Yamamoto
Version: 0.1
License: GPL2
Author URI: http://kyms.jp
*/

if(is_admin()) return;
include_once('includes/xparser.class.inc.php');

add_action('plugins_loaded',function(){ob_start();});
add_action('shutdown',
	function(){
        $xperser = new XPARSE();
    	$output = ob_get_clean();
    	echo $xperser->parse($output);
    },
-1);
