<?php
if(empty($template)) exit();
require_once CORE_ROOT.'include/common.inc.php';
require_once CORE_ROOT.'include/forecache.func.php';
$forecache = getforecache($currenturl);
require_once CORE_ROOT.'include/fore.inc.php';
if(empty($template) || substr($template, -4) != '.htm') fore404();
$html = foredisplay(0, 'page', $template);
if($forecache === false) setforecache($currenturl, $html);
if(substr($html, 0, 5) == '<?xml') header('Content-Type:text/xml;charset='.$header_charset);
echo $html;
require_once CORE_ROOT.'include/exit.php';
?>