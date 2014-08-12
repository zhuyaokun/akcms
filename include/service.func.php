<?php
if(!defined('CORE_ROOT')) exit;

function cloudkeywords($content, $num = 10) {
	global $charset, $settings;
	if(empty($settings['serviceusername']) || empty($settings['servicepassword'])) return false;
	$content = strip_tags($content);
	$cloudurl = 'http://api.akhtm.com/parsekeywords.php';
	$params = array('username' => $settings['serviceusername'], 'password' => $settings['servicepassword'], 'text' => $content, 'charset' => $charset);
	$result = post_request($cloudurl, $params);
	if($result['errno'] != 0) return false;
	$html = unserialize($result['result']);
	if($html['errno'] != 0) return false;
	return $html['result'];
}

function cuturl($url) {
	if(substr($url, 0, 7) != 'http://' && substr($url, 0, 8) != 'https://') return $url;
	$apiurl = 'http://www.akhtm.com/akcms_ddz.php?action=cuturl&url='.urlencode($url);
	$cuturl = readfromurl($apiurl);
	if(substr($cuturl, 0, 5) == 'error') return false;
	return $cuturl;
}
?>