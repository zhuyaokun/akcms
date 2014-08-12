<?php
if(!defined('CORE_ROOT')) exit;
require_once CORE_ROOT.'include/admin.func.php';
if(file_exists(AK_ROOT.'configs/cp.config.php')) require_once(AK_ROOT.'configs/cp.config.php');
$templatedir = AK_ROOT.'configs/templates/'.$template_path.'/';
require_once CORE_ROOT.'include/template.class.php';
header("Cache-Control: no-cache, must-revalidate");
$vc = $_vc;
if(file_exists('./resetpassword.php')) aexit('please remove resetpassword.php first.');
if(file_exists(AK_ROOT.'configs/cphook.php')) require_once(AK_ROOT.'configs/cphook.php');
if($__callmode == 'web') {
	if(ifinstalled() && empty($admin_id) && !in_array($file, array('login', 'upgrade', 'install', 'update', 'theme'))) {
		go($systemurl."index.php?file=login");
	}
}
if(!isset($language)) $language = isset($setting_language) ? $setting_language : 'chinese';
$lan = lan($charset, $language);
$customlan = loadlan(AK_ROOT.'configs/language/custom.lan');
$lan = array_merge($lan, $customlan);
if(empty($nodb)) {
	$db = db();
	if(is_null($db)) exit('DB config error');
}
if(ifinstalled() && empty($nolog)) eventlog("$admin_id\t$currenturl", 'admin');
?>