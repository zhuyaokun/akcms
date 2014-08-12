<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';
require CORE_ROOT.'include/app.func.php';
checkcreator();
if($get_action == 'appshop') {
	$html = '';
	$xml = readfromurl('http://www.akhtm.com/akcms_soft.php?action=xml&r='.random(6));
	if(empty($xml) || strpos($xml, '<?xml') === false) adminmsg($lan['getapperr'], '', 0, 1);
	$apiresult = xml2array($xml);
	if(empty($apiresult) || !is_array($apiresult)) adminmsg($lan['getapperr'], '', 0, 1);
	$apps = $apiresult['apps']['app'];
	$installedapps = $db->querytoarray("SELECT `key` FROM {$tablepre}_apps");
	$tmparray = array();
	foreach($installedapps as $iv) {
		$tmparray[] = $iv['key'];
	}
	foreach($apps as $v) {
		$price = $v['price'].$lan['yuan'];
		if($v['price'] == 0) $price = $lan['free'];
		$icon = $v['icon'];
		$installkey = $v['key'];
		if(!empty($v['dependapp'])) $installkey .= ",{$v['dependapp']}";
		$isinstall = "<a href='index.php?file=app&action=install&key=$installkey&r=".random(6)."' class='install' target='_blank'>{$lan['install']}</a>";
		if(in_array($v['key'], $tmparray)) {
			$isinstall = "<span style='color:#666666;'>{$lan['isinstall']}</span>";
		} elseif($v['dependence'] > $sysedition) {
			$isinstall = "<span style='color:#666666;' title='AKCMS{$v['dependence']}{$lan['appversiontip']}'>{$lan['nosupport']}</span>";
		}
		if(empty($v['icon'])) $icon = 'http://s.akhtm.com/images/app-default-icon.png';
		$html .= "<div class='app'>
		<div class='appicon'><a href='http://www.akhtm.com/app/{$v['key']}.htm' target='_blank'><img src='{$icon}' width=64 height=64 title='{$v['introduce']}' /></a></div>
		<ul class='appdetail'>
			<li class='appname'>{$v['name']}</li>
			<li class='price'>{$price}</li>
			<li class='productor'>{$lan['kernelrequire']}:{$v['dependence']}</li>
		</ul>
		<div class='appbotton'>
		<div class='installbotton'>{$isinstall}</div><div class='detailbotton'><a href='http://www.akhtm.com/app/{$v['key']}.htm' target='_blank'>{$lan['detail']}</a></div>
		</div>
		</div>";
	}
	displaytemplate('admincp_app.htm', array('html' => $html, 'apppagename' => $lan['appshop']));
} elseif($get_action == 'install') {
	$publicip = publicip();
	if(empty($publicip)) adminmsg($lan['connapplierror'], '', 0, 1);
	setcache('appinstalling', $get_key);
	if(isset($_GET['autorun'])) aksetcookie('install_app_autorun', $get_key);
	akheader("location:http://www.akhtm.com/akcms_soft.php?action=install&key=$get_key&referer=".$systemurl."&ip=".$publicip.'&r='.random(6));
} elseif($get_action == 'installed') {
	updatecache('apps');
	$html = '';
	$alreadyinstalled = $db->query_by('*', 'apps');
	while($v = $db->fetch_array($alreadyinstalled)) {
		$installtime = date('y-m-d', $v['updatetime']);
		$html .= "<div class='app'>
			<div class='appicon'><a href='index.php?app={$v['key']}'><img src='{$v['picture']}' /></a></div>
			<ul class='appdetail'>
				<li class='appname'>{$v['app']}</li>
				<li class='productor'>{$lan['version']}:{$v['ver']}</li>
			</ul>
			<div class='appbotton'>
				<div class='installbotton'><a class='uninstall' href='#'>{$lan['appuninstall']}</a></div><div class='detailbotton'><a href='http://www.akhtm.com/app/{$v['key']}.htm' target='_blank'>{$lan['detail']}</a></div>
			</div>
			<div class='ajaxkey' style='display:none;'>{$v['key']}</div>
		</div>";
	}
	if(empty($html)) $html="{$lan['noappinstalled']}";
	displaytemplate('admincp_installedapp.htm', array('html' => $html, 'apppagename' => $lan['alreadyinstalled']));
} elseif($get_action == 'uninstall') {
	$uninstallpath = CORE_ROOT.'configs/apps/'.$get_key;
	ak_rmdir($uninstallpath);
	updatecache('apps');
} elseif($get_action == 'refresh') {
	updatecache('apps');
	header('location:index.php?file=app&action=installed');
	aexit();
} elseif($get_action == 'startinstall') {
	$app = getcache('appinstalling');
	if($app != $get_key) aexit('error');
	if(!isset($get_cdkey)) $get_cdkey = '';
	$result = downloadapp($get_key, $get_cdkey);
	if($result === false) adminmsg($lan['installapperror'], '', 0, 1);
	
	scanapps();
	
	if(!empty($get_cdkey)) $db->update('apps', array('cdkey' => $get_cdkey), "`key`='$get_key'");
	
	updatecache('templateplugins');
	
	if(akgetcookie('install_app_autorun') == $get_key) {
		adminmsg($lan['operatesuccess'], 'index.php?app='.$get_key);
	} else {
		adminmsg($lan['installappsuccessclose']);
	}
}
runinfo();
aexit();
?>