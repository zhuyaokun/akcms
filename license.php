<?php
if(!defined('CORE_ROOT')) exit();
require CORE_ROOT.'include/admin.inc.php';
$action = httpget('action');
if($action == '') {
	$variables = array(
		'domain' => $_SERVER['HTTP_HOST']
	);
	displaytemplate('license.htm', $variables);
} elseif($action == 'checklicense') {
	if($licensesuccess === 'ad') {
		aexit('2');
	}
	$url = 'http://www.akhtm.com/akcms_license.php?action=checklicense&key='.$_SERVER['HTTP_HOST'];
	$licenseonline = readfromurl($url);
	if($licenseonline == '1') {
		$url = 'http://www.akhtm.com/akcms_license.php?action=download&version='.$sysedition.'&key='.$_SERVER['HTTP_HOST'];
		$_tmp = readfromurl($url);
		writetofile($_tmp, AK_ROOT.'configs/license.php');
	}
	aexit($licenseonline);
} elseif($action == 'applyad') {
	writetofile('ad', AK_ROOT.'configs/license.php');
	adminmsg($lan['adlicenseapplymessage'], 'index.php?file=license');
} elseif($action == 'abandonad') {
	@unlink(AK_ROOT.'configs/license.php');
	adminmsg($lan['adlicenseabandonmessage'], 'index.php?file=license');
}
runinfo();
aexit();
?>