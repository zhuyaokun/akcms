<?php
if(!defined('CORE_ROOT')) exit;
require_once(CORE_ROOT.'include/oauth.func.php');
$baiduconfig = array(
	'key' => 'g9U84tIba4qXU8QrutYqIbHY',
	'secret' => '9MPX6LNax7L9yx7C7gTvNGrZ2unG203F',
	'callback' => $homepage.'akcms_user.php?action=baiducallback'
);
$oauth = baiduoauth();

function baiduoauth() {
	global $baiduconfig;
	if(!empty($_SESSION['baidurequesttoken'])) {
		$baiduconfig['requesttoken'] = $_SESSION['baidurequesttoken'];
		$baiduconfig['requestsecret'] = $_SESSION['baidurequestsecret'];
	} else {
		$baiduconfig['requesttoken'] = $baiduconfig['requestsecret'] = '';
	}
	if(!empty($_SESSION['baiduaccesstoken'])) {
		$baiduconfig['accesstoken'] = $_SESSION['baiduaccesstoken'];
		$baiduconfig['accesssecret'] = $_SESSION['baiduaccesssecret'];
	} else {
		$baiduconfig['accesstoken'] = $baiduconfig['accesssecret'] = '';
	}
	return new oauth($baiduconfig);
}

function getrequesttoken() {
	global $oauth, $baiduconfig;
	$_SESSION = array();
	$baiduconfig['requesttoken'] = $baiduconfig['requestsecret'] = $baiduconfig['accesstoken'] = $baiduconfig['accesssecret'] = '';
	$oauth = new oauth($baiduconfig);
	$params = array(
		'url' => 'https://openapi.baidu.com/oauth/1.0/request_token',
		'oauth_callback' => $baiduconfig['callback'],
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
	global $oauth, $baiduconfig;
	$params = array(
		'url' => 'https://openapi.baidu.com/oauth/1.0/access_token',
		'oauth_verifier' => $verifier,
		'oauth_token' => $oauth->requesttoken,
		'method' => 'GET'
	);
	$params = $oauth->iniparams($params);
	$url = $oauth->calurl($params);
	$html = readfromurl($url);
	return htmltoparams($html);
}
?>