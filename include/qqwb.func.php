<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/oauth.func.php');
$qqconfig = array(
	'key' => 'cdf61d296bc54bfab34ca4ac3a0e2ff5',
	'secret' => '7bf156c822a99252aea767b5a338f2d6',
	'callback' => $homepage.'akcms_user.php?action=qqwbcallback'
);
$oauth = qqoauth();

function qqoauth() {
	global $qqconfig;
	if(!empty($_SESSION['qqrequesttoken'])) {
		$qqconfig['requesttoken'] = $_SESSION['qqrequesttoken'];
		$qqconfig['requestsecret'] = $_SESSION['qqrequestsecret'];
	} else {
		$qqconfig['requesttoken'] = $qqconfig['requestsecret'] = '';
	}
	if(!empty($_SESSION['qqaccesstoken'])) {
		$qqconfig['accesstoken'] = $_SESSION['qqaccesstoken'];
		$qqconfig['accesssecret'] = $_SESSION['qqaccesssecret'];
	} else {
		$qqconfig['accesstoken'] = $qqconfig['accesssecret'] = '';
	}
	return new oauth($qqconfig);
}

function getrequesttoken() {
	global $oauth, $qqconfig;
	$_SESSION = array();
	$qqconfig['requesttoken'] = $qqconfig['requestsecret'] = $qqconfig['accesstoken'] = $qqconfig['accesssecret'] = '';
	$oauth = new oauth($qqconfig);
	$params = array(
		'url' => 'https://open.t.qq.com/cgi-bin/request_token',
		'oauth_callback' => $qqconfig['callback'],
		'method' => 'GET'
	);
	$params = $oauth->iniparams($params);
	unset($params['oauth_token']);
	$url = $oauth->calurl($params);
	$html = readfromurl($url);
	return htmltoparams($html);
}

function htmltoparams($html) {
	$pairs = explode('&', $html);
	$params = array();
	foreach($pairs as $pair) {
		$split = explode('=', $pair, 2);
		$k = urldecode($split[0]);
		$v = isset($split[1]) ? urldecode($split[1]) : '';
		if(isset($params[$k])) {
			$params[$k] = array($params[$k]);
			$params[$k][] = $v;
		} else {
			$params[$k] = $v;
		}
	}
	return $params;
}

function getaccesstoken($verifier) {
	global $oauth, $qqconfig;
	$params = array(
		'url' => 'https://open.t.qq.com/cgi-bin/access_token',
		'oauth_verifier' => $verifier,
		'oauth_token' => $oauth->requesttoken,
		'method' => 'GET'
	);
	$params = $oauth->iniparams($params);
	$url = $oauth->calurl($params);
	$html = readfromurl($url);
	return htmltoparams($html);
}

function getuserinfo() {
	global $oauth, $qqconfig;
	$params = array(
		'url' => 'http://open.t.qq.com/api/user/info',
		'format' => 'xml',
		'method' => 'GET'
	);
	$params = $oauth->iniparams($params);
	$url = $oauth->calurl($params);
	$xml = readfromurl($url);
	$array = xml2array($xml);
	return fromutf8($array['root']['data']);
}

function postweibo($content) {
	global $oauth, $qqconfig, $onlineip;
	$content = gbktoutf8($content);
	$apiurl = 'http://open.t.qq.com/api/t/add';
	$params = array(
		'url' => $apiurl,
		'format' => 'json',
		'content' => $content,
		'clientip' => $onlineip,
		'jing' => '',
		'wei' => '',
		'method' => 'POST'
	);
	$params = $oauth->iniparams($params);
	unset($params['url']);
	unset($params['method']);
	uksort($params, 'strcmp');
	$str = '';
	foreach($params as $k => $v) {
		$str .= '&'.$k.'='.urlencode_rfc3986($v);
	}
	$str = substr($str, 1);
	$html = post_request($apiurl, null, $str);
}
?>