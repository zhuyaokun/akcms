<?php
if(!defined('CORE_ROOT')) exit;
callruntime('before');
$foreflag = 1;
@extract($settings, EXTR_PREFIX_ALL, 'setting');
header('Content-Type:text/html;charset='.$header_charset);
$db = db();
require(CORE_ROOT.'include/smarty/libs/Smarty.class.php');

function fore404() {
	if(!empty($GLOBALS['iflog'])) aklog($get_filename, AK_ROOT."logs/rounter-error".date('Ymd', $GLOBALS['thetime']).".txt");
	header("HTTP/1.0 404 Not Found");
	exit;
}

function captcha($sid) {
	global $db, $tablepre, $thetime;
	$expire = 300;
	$captcha = random(4, 1);
	if($db->get_by('*', 'captchas', "sid='$sid'")) {
		$db->update('captchas', array('captcha' => $captcha, 'dateline' => $thetime), "sid='$sid'");
	} else {
		$db->insert('captchas', array('captcha' => $captcha, 'dateline' => $thetime, 'sid' => $sid));
	}
	$db->delete('captchas', "dateline < ($thetime - $expire)");
	require_once(CORE_ROOT.'include/image.func.php');
	corecaptcha($captcha);
}

function foredisplay($id, $type = 'item', $template = '', $params = array()) {
	if(strpos($template, ',') !== false) {
		$templates = explode(',', $template);
		$_key = array_rand($templates);
		$template = $templates[$_key];
	}
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
		$html = render_template($variables, $template);
	} elseif($type == 'category') {
		$variables = get_category_data($id, $template);
		if(empty($variables)) fore404();
		$html = render_template($variables, $template);
	} elseif($type == 'section') {
		$variables = get_section_data($id);
		if(empty($variables)) fore404();
		$html = render_template($variables, $template);
	} else {
		if('' == $template) fore404();
		$html = render_template(array(), $template);
	}
	return $html;
}

function callruntime($flag = 'before') {
	if(!in_array($flag, array('before', 'after'))) return;
	$files = readpathtoarray(AK_ROOT.'plugins/runtime', 1);
	asort($files);
	$before = array();
	$after = array();
	foreach($files as $file) {
		if(!is_file(AK_ROOT.'plugins/runtime/'.$file)) continue;
		if(substr($file, -11) == '_before.php') {
			$before[] = $file;
		} elseif(substr($file, -10) == '_after.php') {
			$after[] = $file;
		}
	}
	foreach($$flag as $program) {
		require_once(AK_ROOT.'plugins/runtime/'.$program);
	}
}

function verifycaptcha() {
	global $post_captcha, $post_sid, $db;
	if(empty($post_captcha) || empty($post_sid)) aexit('101');
	if(strlen($post_sid) > 6) aexit('101');
	$captcha = $post_captcha;
	$sid = $post_sid;
	$captchakey = $db->get_by('captcha', 'captchas', "sid='$sid'");
	$db->delete('captchas', "sid='$sid'");
	if($captcha != $captchakey) aexit('102');
}
?>