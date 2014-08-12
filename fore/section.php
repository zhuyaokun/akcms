<?php
require_once CORE_ROOT.'include/common.inc.php';
require_once CORE_ROOT.'include/forecache.func.php';
$forecache = getforecache($currenturl);
require_once(CORE_ROOT.'include/fore.inc.php');
$sections = getcache('sections');
if(isset($get_id) && a_is_int($get_id)) {
	$id = $get_id;
} elseif(isset($get_alias)) {
	$alias = trim($get_alias);
	foreach($sections as $section) {
		if($section['alias'] == $alias) {
			$id = $section['id'];
			break;
		}
	}
} elseif(isset($get_section)) {
	$name = trim($get_section);
	foreach($sections as $section) {
		if($section['section'] == $name) {
			$id = $section['id'];
			break;
		}
	}
}
if(empty($id)) fore404();
if(empty($template)) {
	if(isset($get_page)) {
		$template = get_section_template($id, 'list');
	} else {
		$template = get_section_template($id, 'default');
	}
}
$html = foredisplay($id, 'section', $template);
if($forecache === false) setforecache($currenturl, $html);
if(substr($html, 0, 5) == '<?xml') header('Content-Type:text/xml;charset='.$header_charset);
echo $html;
require_once(CORE_ROOT.'include/exit.php');
?>