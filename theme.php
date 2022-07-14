<?php
require CORE_ROOT.'include/admin.inc.php';
if(!file_exists(AK_ROOT.'install/custom.config.php')) {
	header('location:index.php?new=1');
	aexit();
}
require_once(AK_ROOT.'install/custom.config.php');
if(empty($_GET['action'])) {
	createconfig(array('template_path' => $themekey));
	$template_path = $themekey;
	ak_copy(AK_ROOT.'install/trunk', FORE_ROOT);
	ak_copy(AK_ROOT.'install/templates', AK_ROOT.'configs/templates/'.$themekey);
	ak_copy(AK_ROOT.'install/systemplates', AK_ROOT.'configs/templates');
	ak_copy(AK_ROOT.'install/configs', AK_ROOT.'configs');
	if(file_exists(AK_ROOT.'install/plugins')) ak_copy(AK_ROOT.'install/plugins', AK_ROOT.'plugins');
	$dbsize = filesize(AK_ROOT.'install/db/db.ak');
	setcache('_themedbsize', $dbsize);
	setcache('_themedboffset', 0);
	setcache('_themedbbatch', 0);
	header('location:index.php?file=theme&action=frame');
} elseif($get_action == 'frame') {
	showprocess($lan['importing'], 'index.php?file=theme&action=importdb', 'index.php?file=theme&action=finish', 100, array(''));
} elseif($get_action == 'importdb') {
	$dbsize = getcache('_themedbsize');
	$dboffset = getcache('_themedboffset');
	$dbbatch = getcache('_themedbbatch');
	$fp = fopen(AK_ROOT.'install/db/db.ak', 'r');
	fseek($fp, $dboffset);
	while(!feof($fp)) {
		$row = fgets($fp, 1024000);
		$dboffset = ftell($fp);
		setcache('_themedboffset', $dboffset);
		if($row == '') continue;
		$value = unserialize(base64_decode($row));
		if(empty($value)) continue;
		if($value['table'] == 'categories' || $value['table'] == 'modules') {
			$db->replaceinto($value['table'], $value['value'], 'id');
		} elseif($value['table'] == 'settings') {
			$db->replaceinto('settings', $value['value'], 'variable');
		} else {
			$db->insert($value['table'], $value['value']);
		}
		
		if($dboffset >= $dbbatch) {
			if($dboffset >= $dbsize) break;
			setcache('_themedbbatch', $dbbatch + 10000);
			$percent = number_format($dboffset * 100/ $dbsize, 2);
			fclose($fp);
			aexit($percent."\t0\t".number_format($dboffset / 1024, 2).'KB');
		}
	}
	fclose($fp);
	setsetting('theme', $template_path);
	deletecache('_themedbbatch');
	deletecache('_themedboffset');
	updatecache();
	aexit('100');
} elseif($get_action == 'finish') {
	updatecache();
	if(file_exists(AK_ROOT.'install/install.php')) require_once(AK_ROOT.'install/install.php');
	finishtheme();
	adminmsg($lan['importsuccess'], 'index.php?file=admincp&action=custom&new=1&theme='.$themekey);
}
?>