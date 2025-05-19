<?php
if(!defined('CORE_ROOT')) exit;
$sections = getcache('sections');
function get_section_data($id) {
	global $template_path, $smarty, $db, $tablepre, $lan, $thetime, $system_root, $lr, $header_charset, $setting_homepage, $setting_defaultfilename, $setting_ifhtml, $sections, $setting_storemethod, $html_smarty;
	$variables = array();
	$variables['_pagetype'] = 'section';
	$variables['_pageid'] = $id;
	if(!isset($sections[$id])) return array();
	$variables['section'] = $id;
	$section = $sections[$id];
	$variables['sectionname'] = $section['section'];
	$variables['alias'] = $section['alias'];
	$variables['orderby'] = $section['orderby'];
	$variables['keywords'] = $section['keywords'];
	$variables['description'] = $section['description'];
	$variables['items'] = $section['items'];
	if($section['html'] == 0) {
		$variables['html'] = $setting_ifhtml;
	} else {
		$variables['html'] = $section['html'];
	}
	foreach($_GET as $key => $value) {
		$variables[$key] = $value;
	}
	$variables['hometemplate'] = get_section_template($id);
	$variables['pagetemplate'] = get_section_template($id, 'list');
	$variables['sectionhomemethod'] = get_section_homemethod($id);
	$variables['sectionpagemethod'] = get_section_pagemethod($id);
	return $variables;
}

function batchsectionhtml($ids) {
	if(!is_array($ids)) $ids = array($ids);
	foreach($ids as $id) {
		$variables = get_section_data($id);
		$filename = $variables['sectionhomemethod'];
		$filename = str_replace('[sectionalias]', $variables['alias'], $filename);
		$filename = str_replace('[sectionname]', $variables['sectionname'], $filename);
		$filename = str_replace('[sectionid]', $variables['section'], $filename);
		$variables['htmlfilename'] = FORE_ROOT.$filename;
		$GLOBALS['index_work'] = "section\n".$id."\n".$variables['htmlfilename'];
		render_template($variables, $variables['hometemplate'], 1);
	}
}

function batchsectionpagehtml($ids) {
	global $db;
}

function operatecreatesectionprocess() {
	require_once(CORE_ROOT.'include/task.file.func.php');
	unset($GLOBALS['index_work']);
	$tasks = gettask('indextask', 50);
	if(empty($tasks)) return true;
	foreach($tasks as $task) {
		list($type, $id, $filename, $page) = explode("\n", $task);
		if($type != 'section') continue;
		if(empty($sections[$id])) $sections[$id] = get_section_data($id);
		$variables = $sections[$id];
		$variables['page'] = $page;
		$variables['htmlfilename'] = $filename;
		$variables['template'] = $variables['pagetemplate'];
		render_template($variables, '', 1);
	}
}

function get_section_template($id, $type = '') {
	global $sections;
	$default_template = 'section_home.htm';
	$list_template = 'section_list.htm';
	if($type == '' || $type == 'default') {
		$template = empty($sections[$id]['defaulttemplate']) ? $default_template : $sections[$id]['defaulttemplate'];
	} elseif($type == 'list') {
		$template = empty($sections[$id]['listtemplate']) ? $list_template : $sections[$id]['listtemplate'];
	}
	return $template;
}

function get_section_homemethod($id) {
	global $setting_sectionhomemethod, $sections;
	$sectionhomemethod = $sections[$id]['sectionhomemethod'];
	if($sectionhomemethod == '') {
		$sectionhomemethod = $setting_sectionhomemethod;
	}
	return $sectionhomemethod;
}

function get_section_pagemethod($id) {
	global $setting_sectionpagemethod, $sections;
	$sectionpagemethod = $sections[$id]['sectionpagemethod'];
	if($sectionpagemethod == '') {
		$sectionpagemethod = $setting_sectionpagemethod;
	}
	return $sectionpagemethod;
}
?>