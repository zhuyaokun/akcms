<?php
if(!defined('CORE_ROOT')) exit;

function downloadpackage($app, $cdkey = '') {
}

function downloadapp($app, $cdkey = '') {
	global $charset;
	$appfile = $app.'.akp';
	$apppath = CORE_ROOT.'configs/apps/';
	$downloadurl = "http://www.akhtm.com/akcms_soft.php?action=download&charset=$charset&key=".$app;
	if(isset($cdkey)) $downloadurl.='&cdkey='.$cdkey;
	$result = readfromurl($downloadurl);
	if($result == '') return false;
	if(!is_dir($apppath)) ak_mkdir($apppath);
	writetofile($result, $apppath.$appfile);
	if(!is_dir($apppath.$app)) ak_mkdir($apppath.$app);
	unzip($apppath.$appfile, $apppath.$app);
	akunlink($apppath.$appfile);
}

function scanapps() {
	global $template_path, $db, $tablepre, $sysedition;

	ak_rmdir(AK_ROOT.'configs/apps/_dependhook/');
	ak_rmdir(AK_ROOT.'configs/hooks');
	akunlink(AK_ROOT.'configs/cphook.php');
	akunlink(AK_ROOT.'configs/forehook.php');
	akunlink(AK_ROOT.'configs/appmenu.php');
	
	$apps = readpathtoarray(AK_ROOT.'configs/apps/', 1);
	$forehook = $cphook = $templateapp = $menu = '';
	$phphooks = array();
	$dependhooksbefore = $dependhooksafter = $messageservices = array();
	$apppath = AK_ROOT.'configs/apps/';
	foreach($apps as $k => $app) {
		if(substr($app, 0, 1) == '_' || !is_dir($apppath.$app)) {
			unset($apps[$k]);
			continue;
		}
		if(file_exists($apppath.$app.'/info.xml')) {
			$xml = readfromfile($apppath.$app.'/info.xml');
			$info = xml2array($xml);
			if($info['dependence'] > $sysedition) {
				unset($apps[$k]);
				continue;
			}
			insertapp($app, $info);

			if(isset($info['messageservice'])) $messageservices[$app] = $info['messageservice'];
		}
		if(!file_exists($apppath.$app.'/install.lock')) {
			if(file_exists($apppath.$app.'/install.php')) include($apppath.$app.'/install.php');
			touch($apppath.$app.'/install.lock');
		}
		
		if(file_exists(AK_ROOT.'configs/apps/'.$app.'/cli')) {
			ak_copy(AK_ROOT.'configs/apps/'.$app.'/cli', AK_ROOT.'cli');
		}
		
		$apps[$k] = "'$app'";
		if(file_exists(AK_ROOT.'configs/apps/'.$app.'/cphook.php')) {
			$php = readfromfile(AK_ROOT.'configs/apps/'.$app.'/cphook.php');
			$cphook .= "<?php //$app?>".$php;
		}
		if(file_exists(AK_ROOT.'configs/apps/'.$app.'/forehook.php')) {
			$php = readfromfile(AK_ROOT.'configs/apps/'.$app.'/forehook.php');
			$forehook .= "<?php //$app?>".$php;
		}
		if(is_dir(AK_ROOT.'configs/apps/'.$app.'/templateplugin')) {
			$tps = readpathtoarray(AK_ROOT.'configs/apps/'.$app.'/templateplugin');
			foreach($tps as $tp) {
				if(strlen($tp) < 5) continue;
				if(substr($tp, -4) !== '.php') continue;
				$php = readfromfile($tp);
				$forehook .= $php;
				$cphook .= $php;
			}
		}
		if(is_dir(AK_ROOT.'configs/apps/'.$app.'/hook')) {
			$hooks = readpathtoarray(AK_ROOT.'configs/apps/'.$app.'/hook', 1);
			foreach($hooks as $hook) {
				if(strlen($hook) < 4 || substr($hook, -4) != '.php') continue;
				$php = readfromfile(AK_ROOT.'configs/apps/'.$app.'/hook/'.$hook);
				if(isset($phphooks[$hook])) {
					$phphooks[$hook] .= $php."\n";
				} else {
					$phphooks[$hook] = $php."\n";
				}
			}
		}
		
		if(is_dir(AK_ROOT.'configs/apps/'.$app.'/foreprogram')) {
			$programs = readpathtoarray(AK_ROOT.'configs/apps/'.$app.'/foreprogram', 1);
			foreach($programs as $program) {
				if($program == 'index.php') continue;
				ak_copy(AK_ROOT.'configs/apps/'.$app.'/foreprogram/'.$program, FORE_ROOT.$program);
			}
		}
		if(is_dir(AK_ROOT.'configs/apps/'.$app.'/foretemplate')) {
			ak_copy(AK_ROOT.'configs/apps/'.$app.'/foretemplate', AK_ROOT.'templates/fore');
		}
		if(is_dir(AK_ROOT.'configs/apps/'.$app.'/template')) {
			ak_copy(AK_ROOT.'configs/apps/'.$app.'/template', AK_ROOT.'configs/templates/');
		}
		if(file_exists(AK_ROOT.'configs/apps/'.$app.'/menu.php')) {
			$menu .= readfromfile(AK_ROOT.'configs/apps/'.$app.'/menu.php')."\n";
		}
		
		if(is_dir(AK_ROOT.'configs/apps/'.$app.'/dependhook')) {
			$programs = readpathtoarray(AK_ROOT.'configs/apps/'.$app.'/dependhook', 1);
			foreach($programs as $program) {
				if(strlen($program) < 6 || substr($program, -4) != '.php') continue;
				$pos = substr($program, 0, 1);
				$_program = substr($program, 1, -4);
				$_php = readfromfile(AK_ROOT.'configs/apps/'.$app.'/dependhook/'.$program)."\n";
				if(!isset($dependhooksafter[$_program])) $dependhooksafter[$_program] = '';
				if($pos == '-') {
					$dependhooksbefore[$_program] .= $_php;
				} elseif($pos == '+') {
					$dependhooksafter[$_program] .= $_php;
				}
			}
		}
	}
	setcache('messageservices', $messageservices);
	foreach($dependhooksbefore as $key => $php) {
		if(strlen($php) <= 5) continue;
		writetofile($php, AK_ROOT.'configs/apps/_dependhook/-'.$key.'.php');
	}
	foreach($dependhooksafter as $key => $php) {
		if(strlen($php) <= 5) continue;
		writetofile($php, AK_ROOT.'configs/apps/_dependhook/+'.$key.'.php');
	}
	if(!empty($apps)) {
		$apps = implode(',', $apps);
		$db->query("DELETE FROM {$tablepre}_apps WHERE `key` NOT IN ($apps)");
	} else {
		$db->query("DELETE FROM {$tablepre}_apps");
	}
	writetofile($cphook, AK_ROOT.'configs/cphook.php');
	writetofile($forehook, AK_ROOT.'configs/forehook.php');
	writetofile($menu, AK_ROOT.'configs/appmenu.php');
	
	foreach($phphooks as $hook => $php) {
		writetofile($php, AK_ROOT.'configs/hooks/'.$hook);
	}
}

function insertapp($app, $info) {
	global $db, $thetime;
	if(!$db->get_by('*', 'apps', "`key`='{$info['key']}'")) {
		$value = array(
			'app' => $info['name'],
			'key' => $info['key'],
			'ver' => $info['version'],
			'updatetime' => $thetime,
			'producer' => $info['producer'],
			'picture' => $info['icon']
		);
		$db->insert('apps', $value);
	}
}
?>