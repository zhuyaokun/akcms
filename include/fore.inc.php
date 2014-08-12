<?php
if(!defined('CORE_ROOT')) exit;
if(!securitycheck()) {
	$log = $_SERVER["REQUEST_URI"];
	eventlog($log, 'crack');
	$lan = lan($charset, $language);
	aexit($lan['crackwarning']."<script>setTimeout(\"location='http://www.akhtm.com/manual/crack-warning.htm?source=crack'\", 2000);</script>");
}
$foreflag = 1;
$db = db();
if(is_null($db)) exit('DB config error');
$tplvars = array();
include(AK_ROOT.'configs/forehook.php');
extract($tplvars);
if(!isset($template)) $template = '';
if(strpos($template, ',') !== false) {
	$templates = explode(',', $template);
	$_key = array_rand($templates);
	$template = $templates[$_key];
}

function securitycheck() {
	global $skipsecuritycheck;
	foreach($_GET as $key => $value) {
		if(trim($value) == '') continue;
		if(isset($skipsecuritycheck['get'.$key])) continue;
		if($key == 'sid') {
			if(!preg_match('/^[a-zA-Z0-9]+$/', $value)) return false;
		} elseif($key == 'id') {
			if(!preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) return false;
		} elseif(strlen($key) > 2 && substr($key, -2) == 'id') {
			if(!preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) return false;
		}
		if(preg_match('/0x7e/i', $value)) return false;
		if(preg_match('/0x27/i', $value)) return false;
		if(preg_match('/select\s+/i', $value)) return false;
		if(preg_match('/update\s+/i', $value)) return false;
		if(preg_match('/from\s+/i', $value)) return false;
		if(preg_match('/delete\s+/i', $value)) return false;
		if(strpos($value, ')') !== false) return false;
		if(strpos($value, '(') !== false) return false;
		if(strpos($value, '\'') !== false) return false;
		if(strpos($value, '"') !== false) return false;
	}
	foreach($_POST as $key => $value) {
		if(trim($value) == '') continue;
		if(isset($skipsecuritycheck['post'.$key])) continue;
		if($key == 'sid') {
			if(!preg_match('/^[a-zA-Z0-9]+$/', $value)) return false;
		} elseif(strlen($key) > 2 && substr($key, -2) == 'id') {
			if(!preg_match('/^[a-zA-Z0-9_\-]+$/', $value)) return false;
		}
	}
	return true;
}

function foretemplate($template, $variables = array()) {
	global $tplvars, $settings, $currenturl, $homepage;
	if(!empty($tplvars) && is_array($tplvars)) {
		foreach($tplvars as $key => $value) {
			$variables[$key] = $value;
		}
	}
	$a = parse_url($homepage);
	$variables['currenturl'] = str_replace($a['path'], '', $_SERVER['REQUEST_URI']);
	$html = render_template($template, $variables);
	return $html;
}

function foredisplay($id, $type = 'item', $template = '', $params = array()) {
	if(!a_is_int($id)) fore404();
	if($type == 'item') {
		$variables = get_item_data($id, $template, $params);
		if(empty($variables)) fore404();
		if(!empty($variables['category'])) {
			$category = getcategorycache($variables['category']);
			if($category === false) fore404();
			$modules = getcache('modules');
			$module = $modules[$category['module']];
			if($module['data']['page'] == '-1') fore404();
		}
		$html = foretemplate($template, $variables);
	} elseif($type == 'category') {
		$variables = get_category_data($id, $template);
		if(empty($variables)) fore404();
		$html = foretemplate($template, $variables);
	} elseif($type == 'section') {
		$variables = get_section_data($id);
		if(empty($variables)) fore404();
		$html = foretemplate($template, $variables);
	} else {
		if('' == $template) fore404();
		$html = foretemplate($template);
	}
	return $html;
}

function responseajax($error, $result = '') {
	$return['error'] = "$error";
	$return['result'] = "$result";
	$response = json_encode($return);
	aexit($response);
}
?>