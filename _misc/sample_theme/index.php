<?php
if(!defined('WPINC')) exit('error');

$html = '';
$tpl_path = TEMPLATEPATH . '/tpl/';

if(is_front_page())     $html = file_get_contents($tpl_path.'front.tpl');
elseif(is_page())       $html = file_get_contents($tpl_path.'single.tpl');
elseif(is_404())        $html = file_get_contents($tpl_path.'404.tpl');
else                    wp_safe_redirect(get_option('siteurl'));

if(is_404()) header('HTTP/1.0 404 Not Found');
if($html) echo $html;
return;
