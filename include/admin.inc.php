<?php
if(!defined('CORE_ROOT')) exit;
require_once CORE_ROOT.'include/admin.func.php';
if(file_exists(AK_ROOT.'configs/cp.config.php')) require_once(AK_ROOT.'configs/cp.config.php');
$templatedir = AK_ROOT.'configs/templates/'.$template_path.'/';
require_once CORE_ROOT.'include/smarty/libs/Smarty.class.php';
$smarty = new Smarty;
$vc = $_vc;
if(empty($jquery)) $jquery = CORE_URL.'include/jquery.js';
$smarty->assign('vc', $vc);
$smarty->assign('jquery', $jquery);

if(file_exists('./resetpassword.php')) aexit('please remove resetpassword.php first.');
if($__callmode == 'web') {
	if(ifinstalled() && empty($admin_id) && !in_array($file, array('login', 'upgrade', 'install', 'update', 'theme'))) {
		go($systemurl."index.php?file=login");
	}
}
if(!isset($language)) $language = isset($setting_language) ? $setting_language : 'chinese';
$lan = lan($charset, $language);
$customlan = loadlan(AK_ROOT.'configs/language/custom.lan');
$lan = array_merge($lan, $customlan);
if(empty($nodb)) $db = db();
if(ifinstalled() && empty($nolog)) eventlog("$admin_id\t$currenturl", 'admin');
?>