<?php
if(!isset($_GET['id'])) exit();
require_once CORE_ROOT.'include/common.inc.php';
if(!a_is_int($_GET['id'])) exit();
require_once CORE_ROOT.'include/forecache.func.php';
$forecache = getforecache($currenturl);
require_once(CORE_ROOT.'include/fore.inc.php');
if(!isset($template)) $template = '';
$ver = 1;
if(!empty($_GET['ver'])) $ver = $_GET['ver'];
if(!a_is_int($ver)) $ver = 1;
$itempage = 0;
if(!empty($_GET['itempage'])) $itempage = $_GET['itempage'];
if(!a_is_int($itempage)) $itempage = 0;
$html = foredisplay($get_id, 'item', $template, array('ver' => $ver, 'itempage' => $itempage));
if($forecache === false) setforecache($currenturl, $html);
if(substr($html, 0, 5) == '<?xml') header('Content-Type:text/xml;charset='.$header_charset);
echo $html;
require_once(CORE_ROOT.'include/exit.php');
?>