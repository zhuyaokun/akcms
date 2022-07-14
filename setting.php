<?php
if(!defined('CORE_ROOT')) @include 'include/directaccess.php';
require CORE_ROOT.'include/admin.inc.php';
checkcreator();
if(!isset($post_setting_submit)) {
	empty($get_action) && $get_action = '';
	$settings = array();
	$query = $db->query_by('*', 'settings');
	while($setting = $db->fetch_array($query)) {
		$settings[$setting['variable']] = $setting;
	}
	$str_settings = '';
	if($get_action == 'generally') {
		$str_settings .= table_start($lan['generallysetting']);
		$str_settings .= inputshow($settings, array('sitename', 'htmlexpand', 'statcachesize', 'menuwidth', 'defaultfilename', 'homepage', 'systemurl', 'storemethod', 'template2', 'storemethod2', 'template3', 'storemethod3', 'template4', 'storemethod4', 'pagetemplate', 'pagestoremethod', 'categoryhomemethod', 'categorypagemethod', 'sectionhomemethod', 'sectionpagemethod', 'attachmethod', 'previewmethod', 'imagemethod', 'thumbmethod'));
		$str_settings .= table_end();
	}
	if($get_action == 'functions') {
		$str_settings .= table_start($lan['functionssetting']);
		$str_settings .= inputshow($settings, array('ifhtml', 'usefilename', 'forbidstat', 'ifdraft'));
		$str_settings .= table_end();
	}
	if($get_action == 'front') {
		$str_settings .= table_start($lan['frontsetting']);
		$str_settings .= inputshow($settings, array('keywordslink', 'globalkeywordstemplate'));
		$str_settings .= table_end();
	}
	if($get_action == 'user') {
		$str_settings .= table_start($lan['usersetting']);
		$str_settings .= inputshow($settings, array('ifuser', 'ifcomment', 'ifguestcomment', 'commentneedcaptcha', 'ifcommentrehtml'));
		$str_settings .= table_end();
	}
	if($get_action == 'attach') {
		$str_settings .= table_start($lan['attachsetting']);
		$str_settings .= inputshow($settings, array('attachimagequality', 'attachwatermarkposition', 'maxattachsize', 'cdn', 'cdnid', 'cdnsecret', 'cdnpath'));
		$str_settings .= table_end();
	}
	if($get_action == 'service') {
		$str_settings .= table_start($lan['servicesetting']);
		$str_settings .= inputshow($settings, array('serviceusername', 'servicepassword'));
		$str_settings .= table_end();
	}
	if($get_action == 'tk') {
		$str_settings .= table_start($lan['tksetting']);
		$str_settings .= inputshow($settings, array('tksecrets', 'tknick'));
		$str_settings .= table_end();
	}
	if($get_action == 'alipay') {
		$str_settings .= table_start($lan['alipaysetting']);
		$str_settings .= inputshow($settings, array('alipaypartner', 'alipaykey', 'alipayemail'));
		$str_settings .= table_end();
	}
	$smarty->assign('action', $get_action);
	$smarty->assign('str_settings', $str_settings);
	displaytemplate('admincp_setting.htm');
} else {
	$query = $db->query_by('variable,value', 'settings');
	$update = array();
	while($row = $db->fetch_array($query)) {
		$variable = $row['variable'];
		$setting = $row['value'];
		$post_variable = 'post_'.$variable;
		
		if(isset($$post_variable)) {
			$value = $$post_variable;
			if(is_array($value)) $value = implode(',', $value);
			if($setting != $value) $update[$variable] = array('value' => $value);
		}
		
	}
	foreach($update as $k => $v) {
		$db->update('settings', $v, "variable='$k'");
	}
	updatecache('settings');
	adminmsg($lan['operatesuccess'], 'index.php?file=setting&action='.$post_action);
}
runinfo();
aexit();
?>