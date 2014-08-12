<?php
require_once CORE_ROOT.'include/common.inc.php';
require_once CORE_ROOT.'include/forecache.func.php';
$forecache = getforecache($currenturl);
require_once CORE_ROOT.'include/fore.inc.php';
if(empty($get_filename)) fore404();
$filename = $get_filename;
if($html = $db->get_by('*', 'filenames', "filename='".$db->addslashes($filename)."'")) {
	$id = $html['id'];
} else {
	fore404();
}
$html = foredisplay($id, 'item');
if($forecache === false) setforecache($currenturl, $html);
if(substr($html, 0, 5) == '<?xml') header('Content-Type:text/xml;charset='.$header_charset);
echo $html;
require_once CORE_ROOT.'include/exit.php';
?>