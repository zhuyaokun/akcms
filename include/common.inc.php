<?php
if(!defined('CORE_ROOT')) exit;
if(function_exists('date_default_timezone_set')) date_default_timezone_set('UTC');
$start_time =  microtime(1);
$register_globals = @ini_get('register_globals');
if($register_globals) {
	if(is_array($_POST)) {
		foreach($_POST as $key => $value) {
			unset($$key);
		}
	}
	if(is_array($_GET)) {
		foreach($_GET as $key => $value) {
			unset($$key);
		}
	}
	if(is_array($_COOKIE)) {
		foreach($_COOKIE as $key => $value) {
			unset($$key);
		}
	}
}
if(substr(PHP_OS, 0, 3) == 'WIN' || (isset($_ENV['OS']) && strstr($_ENV['OS'], 'indow'))) {
	$os = 'windows';
	$separator = '\\';
	$lr = "\r\n";
} else {
	$os = 'linux';
	$separator = '/';
	$lr = "\n";
}
if(isset($_SERVER['HTTP_HOST'])) {
	$__callmode = 'web';
	$host = $_SERVER['SERVER_NAME'];
	if(isset($_SERVER['REQUEST_URI'])) $currenturl = $_SERVER['REQUEST_URI'];
	if(!isset($currenturl) && isset($_SERVER['HTTP_X_REWRITE_URL'])) $currenturl = $_SERVER['HTTP_X_REWRITE_URL'];
	if(!isset($currenturl) || 1) {
		$currenturl = $_SERVER['SCRIPT_NAME'];
		if(!empty($_SERVER['QUERY_STRING'])) $currenturl .= '?'.$_SERVER['QUERY_STRING'];
	}
	ob_start();
	$pid = 0;
} else {
	$__callmode = 'command';
	$pid = getmypid();
}
require_once CORE_ROOT.'include/common.func.php';
if(file_exists(CORE_ROOT.'configs/config.inc.php')) require CORE_ROOT.'configs/config.inc.php';
if(file_exists(AK_ROOT.'configs/config.inc.php')) {
	require AK_ROOT.'configs/config.inc.php';
} else {
	$template_path = 'ak';
	$timedifference = 0;
	$charset = 'utf8';
	$ifdebug = 0;
	$codekey = 'akcms';
	$cookiepre = 'akcms';
}
$mtime = explode(' ', microtime());
!isset($timedifference) && $timedifference = 0;
$thetime = time() + $timedifference * 3600;

require_once CORE_ROOT.'include/cache.func.php';
require_once CORE_ROOT.'include/global.func.php';
require_once CORE_ROOT.'include/service.func.php';
require_once CORE_ROOT.'include/task.file.func.php';
if(empty($ifdebug)) set_error_handler("akerror");
if(file_exists(AK_ROOT.'configs/hook.php')) require_once AK_ROOT.'configs/hook.php';
foreach($GLOBALS as $_key => $_value) {
	if(isobscure($_key)) unset($$_key);
}
unset($_key, $_value);
$header_charset = calheadercharset($charset);
$db_setname = caldbsetname($charset);
if(empty($ifdebug) && ifinstalled()) {
	error_reporting(0);
} else {
	if(defined('E_DEPRECATED')) {
		error_reporting(E_ALL ^ E_DEPRECATED);
	} else {
		error_reporting(E_ALL);
	}
}
if(!isset($fore_root)) $fore_root = AK_ROOT.'../';
define('FORE_ROOT', $fore_root);
if(isset($ak_url)) define('AK_URL', $ak_url);
if($__callmode == 'web') {
	$_p1 = strrpos(substr(AK_ROOT, 0, -1), $separator);
	$system_root = substr(AK_ROOT, $_p1 + 1, -1);
	$_p2 = strripos($currenturl, "/{$system_root}/") + 1;
	if($_p2 == 1 && strripos($currenturl, "/{$system_root}/") === false) {
		$_p3 = strpos($currenturl, '?');
		if($_p3 === false) {
			$_u1 = $currenturl;
		} else {
			$_u1 = substr($currenturl, 0, $_p3);
		}
		$_p2 = ak_strrpos($_u1, '/') + 1;
	}
	if(!isset($fore_url)) $fore_url = substr($currenturl, 0, $_p2);
	define('AK_URL', $fore_url.$system_root.'/');
	if(!isset($core_url)) {
		define('CORE_URL', AK_URL);
	} else {
		define('CORE_URL', $core_url);
	}
	unset($_p1, $_p2, $_u1);
}
require_once CORE_ROOT.'include/render.inc.bin';
if(PHP_VERSION < '4.1.0') {
	$_GET = $HTTP_GET_VARS;
	$_POST = $HTTP_POST_VARS;
	$_COOKIE = $HTTP_COOKIE_VARS;
	$_SERVER = $HTTP_SERVER_VARS;
	$_ENV = $HTTP_ENV_VARS;
	$_FILES = $HTTP_POST_FILES;
}
$magic_quotes_gpc = @get_magic_quotes_gpc();
if($magic_quotes_gpc) {
	$_POST = unaddslashes($_POST);
	$_GET = unaddslashes($_GET);
	$_COOKIE = unaddslashes($_COOKIE);
}
@extract($_POST, EXTR_PREFIX_ALL, 'post');
@extract($_GET, EXTR_PREFIX_ALL, 'get');
@extract($_FILES, EXTR_PREFIX_ALL, 'file');
@extract($_COOKIE, EXTR_PREFIX_ALL, 'cookie');

if(ifinstalled()) {
	$settings = getcache('settings');
	@extract($settings, EXTR_PREFIX_ALL, 'setting');
}
if($__callmode == 'web') header('Content-Type:text/html;charset='.$header_charset);
$homepage = '';
if(!empty($setting_homepage)) {
	if(substr($setting_homepage, -1) != '/') $setting_homepage .= '/';
	$homepage = $setting_homepage;
} else {
	if('web' == $__callmode) $homepage = 'http://'.$_SERVER['HTTP_HOST'].$fore_url;
}
if(empty($attachurl)) $attachurl = $homepage;
if(!empty($setting_systemurl)) {
	if(substr($setting_systemurl, -1) != '/') $setting_systemurl .= '/';
	$systemurl = $setting_systemurl;
} else {
	if('web' == $__callmode) $systemurl = 'http://'.$_SERVER['HTTP_HOST'].AK_URL;
}
$language = isset($setting_language) ? $setting_language : 'chinese';
if('web' == $__callmode) {
	$admin_id = adminid();
	$onlineip = $_SERVER['REMOTE_ADDR'];
}
unset($HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_ENV_VARS, $HTTP_POST_FILES, $_REQUEST);

function adminid() {
	global $dbpw, $dbname, $codekey;
	if(!isset($dbpw)) $dbpw = '';
	$auth = akgetcookie('auth');
	if(empty($auth)) return false;
	if(substr_count($auth, '|') != 1) return false;
	list($adminid, $verify) = explode('|', $auth);
	$md5 = md5("$adminid|$dbpw|$dbname|$codekey");
	if($md5 != $verify) return false;
	return $adminid;
}

function setlogin($adminid, $expire = 0) {
	global $dbpw, $dbname, $codekey;
	if(!isset($dbpw)) $dbpw = '';
	$verify = md5("$adminid|$dbpw|$dbname|$codekey");
	aksetcookie('auth', "$adminid|$verify", $expire);
}

function authcode($string, $operation, $key = '') {
	$key = md5($key ? $key : $GLOBALS['codekey']);
	$key_length = strlen($key);

	$string = $operation == 'DECODE' ? base64_decode($string) : substr(md5($string.$key), 0, 8).$string;
	$string_length = strlen($string);

	$rndkey = $box = array();
	$result = '';

	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($key[$i % $key_length]);
		$box[$i] = $i;
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if(substr($result, 0, 8) == substr(md5(substr($result, 8).$key), 0, 8)) {
			return substr($result, 8);
		} else {
			return '';
		}
	} else {
		return str_replace('=', '', base64_encode($result));
	}
}
?>
