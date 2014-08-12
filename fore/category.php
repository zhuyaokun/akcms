<?php
require_once CORE_ROOT.'include/common.inc.php';
require_once CORE_ROOT.'include/forecache.func.php';
$forecache = getforecache($currenturl);
require_once(CORE_ROOT.'include/fore.inc.php');
if(isset($get_id) && a_is_int($get_id)) {
	$id = $get_id;
} elseif(isset($get_path)) {
	$fullpath = trim($get_path);
	if(substr($fullpath, -1) == '/') $fullpath = substr($fullpath, 0, -1);
	$id = $db->get_by('id', 'filenames', "filename='".$db->addslashes($fullpath)."'");
} elseif(isset($get_alias)) {
	$alias = trim($get_alias);
	$id = $db->get_by('id', 'categories', "alias='".$db->addslashes($alias)."'", 'id');
} elseif(isset($get_category)) {
	$name = trim($get_category);
	$id = $db->get_by('id', 'categories', "category='".$db->addslashes($name)."'", 'id');
} elseif(isset($get_originalpath)) {
	$originalpath = trim($get_originalpath);
	$id = $db->get_by('id', 'categories', "path='".$db->addslashes($originalpath)."'", 'id');
}
if(empty($id)) fore404();
$global_category = $id;
$categorycache = getcategorycache($id);
if(!isset($template)) {
	if(isset($get_page)) {
		$template = $categorycache['listtemplate'];
	} else {
		$template = $categorycache['defaulttemplate'];
	}
}
$html = foredisplay($id, 'category', $template);
if($forecache === false) setforecache($currenturl, $html);
if(substr($html, 0, 5) == '<?xml') header('Content-Type:text/xml;charset='.$header_charset);
echo $html;
require_once(CORE_ROOT.'include/exit.php');
?>