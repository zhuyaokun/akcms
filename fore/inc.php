<?php
require_once CORE_ROOT.'include/common.inc.php';
require_once CORE_ROOT.'include/fore.inc.php';
$config_timeout = 10;
$statcachefilename = AK_ROOT.'cache/visit.txt';

dealwithstatcache();
if(isset($get_i) && a_is_int($get_i)) {
	if(!empty($get_stat)) {
		$request = $get_i;
		if($setting_statcachesize > 0) {
			addtostatcache($request, $thetime, $onlineip);
		} else {
			updatestat($request);
		}
	} else {
		aexit("document.write(\"<script src='{$homepage}akcms_inc.php?i=$get_i&stat=1'></script>\")");
	}
}

aexit('0');
function addtostatcache($id, $thetime, $ip) {
	global $statcachefilename;
	$log = $id."\t".$thetime."\t".$ip;
	aklog($log, $statcachefilename);
}

function dealwithstatcache() {
	global $statcachefilename, $db, $tablepre, $setting_statcachesize, $timedifference;
	if(!file_exists($statcachefilename)) return;
	$lastmodified = filemtime($statcachefilename) + $timedifference * 3600;
	if(filesize($statcachefilename) > $setting_statcachesize) {
		rename($statcachefilename, $statcachefilename.'.tmp');
		$cache = readfromfile($statcachefilename.'.tmp');
		akunlink($statcachefilename.'.tmp');
		$array_cache = explode("\n", $cache);
		$array_cache_operated = array();
		foreach($array_cache as $cache) {
			$array_field = explode("\t", $cache);
			if(count($array_field) >= 3) {
				$array_cache_operated[] = $array_field[0];
				if(substr($array_field[0], 0, 1) == 'c') {
					$type = 'category';
					$itemid = substr($array_field[0], 1);
				} else {
					$type = 'item';
					$itemid = $array_field[0];
				}
			}
		}
		$visit = array_count_values($array_cache_operated);
		foreach($visit as $id => $count) {
			updatestat($id, $count);
		}
	}
}

function updatestat($id, $count = 1) {
	global $db;
	if(empty($id)) return false;
	if(substr($id, 0, 1) == 'c') {
		$id = substr($id, 1);
		$db->update('categories', array('pv' => "+{$count}"), "id='$id'");
	} else {
		$db->update('items', array('pageview' => "+{$count}", 'pv1' => "+{$count}", 'pv2' => "+{$count}", 'pv3' => "+{$count}", 'pv4' => "+{$count}", ), "id='$id'");
	}
}
?>