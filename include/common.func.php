<?php
if(!defined('CORE_ROOT')) exit;
function alert($text) {
	return '<font color=red><b>'.$text.'</b></font>';
}
function green($text) {
	return '<font color=green><b>'.$text.'</b></font>';
}
function b($text) {
	return '<b>'.$text.'</b>';
}
function disabled($text) {
	global $lan;
	return '<font color="gray" title="'.$lan['disabled'].'"><b>'.$text.'</b></font>';
}
function available($text) {
	global $lan;
	return '<font color="green" title="'.$lan['available'].'"><b>'.$text.'</b></font>';
}

function obj2array($obj) {
	$var = $obj;
	if(is_object($var)) $var = get_object_vars($var);
	if(is_array($var)) {
		foreach($var as $k => $v) {
			$var[$k] = obj2array($v);
		}
	}
	return $var;
}

function aklog($log, $file) {
	$log = $log;
	error_log($log);
}

function writetofile($text, $filename) {
	$path = pathinfo($filename);
	if(!is_dir($path['dirname'])) {
		ak_mkdir($path['dirname']);
	}
	if(!$fp = @fopen($filename, 'w')) {
		aexit('<a href="http://www.akhtm.com/manual/write-permission.htm" target="_blank">Fatal error: '.$filename.' is not writable!</a>');
		return false;
	} else {
		flock($fp, LOCK_EX);
		fwrite($fp, $text);
		fclose($fp);
		return true;
	}
}
function readfromfile($filename) {
	if(substr($filename, 0, 7) != 'http://' && !is_readable($filename)) return '';
	if(PHP_VERSION < '4.3.0') {
		if(!$fp = fopen($filename, 'r')) {
			return false;
		} else {
			flock($fp, LOCK_EX);
			$return = '';
			while (!feof($fp)) {
				$return .= fgets($fp, 4096);
			}
			fclose($fp);
			return $return;
		}
	} else {
		return file_get_contents($filename);
	}
}

function eventlog($log, $type = '', $weight = 10) {
	global $timedifference, $onlineip, $__callmode;
	$time = time() + $timedifference * 3600 * 24;
	$logfile = AK_ROOT.'logs/'.$type.date('Ymd').'.log';
	$time = date('H:i:s', $time);
	if($__callmode == 'command') {
		$query = 'command';
	} else {
		$query = '-----------';
		if(!empty($_SERVER['QUERY_STRING'])) $query = $_SERVER['QUERY_STRING'];
	}
	if(is_array($log)) $log = serialize($log);
	aklog($time."\t".$onlineip."\t$query\t".$log, $logfile);
}

function ak_mkdir($dirname) {
	$dirname = str_replace('\\', '/', $dirname);
	$a_path = explode('/', $dirname);
	if(count($a_path) == 0) {
		return mkdir($dirname);
	} else {
		array_pop($a_path);
		$path = @implode('/', $a_path);
		if(is_dir($path.'/')) {
			return @mkdir($dirname);
		} else {
			ak_mkdir($path);
			return @mkdir($dirname);
		}
	}
}

function ak_touch($file) {
	$dir = dirname($file);
	ak_mkdir($dir);
	return touch($file);
}

function ak_copy($source, $target) {
	$targetdir = dirname($target);
	if(!file_exists($targetdir)) ak_mkdir(dirname($target));
	if(is_dir($source)) return copydir($source, $target);
	return copy($source, $target);
}

function copydir($dirf, $dirt) {
	if(!is_dir($dirf) || strpos($dirf, '.svn') !== false) return false;
	$mydir = opendir($dirf);
	if(!file_exists($dirt)) mkdir($dirt);
	while($file = readdir($mydir)) {
		if(strpos($file, '.svn') !== false) continue;
		if((is_dir("$dirf/$file")) && ($file!=".") && ($file!="..")) {
			if(!copydir("$dirf/$file","$dirt/$file")) return false;
		} elseif(is_file("$dirf/$file")) {
			if(!copy("$dirf/$file","$dirt/$file")) return false;
		}
	}
	return true;
}

function debug($variable, $exit = 0, $type = 0) {
	global $__callmode, $header_charset;
	if('command' == $__callmode) $type = 3;
	if(is_object($variable)) {
		$objflag = 1;
		$variable = get_object_vars($variable);
	}
	if(is_array($variable) || is_object($variable)) {
		$info = print_r($variable, 1);
	} elseif($variable === false) {
		$info = '(bool)false';
	} else {
		$info = $variable;
	}
	if($type != 3) $info = ak_htmlspecialchars($info);
	if(isset($objflag)) $info = "Object\n".substr($info, 6);
	if($type == 0) {
		$info = str_replace("\n", '<br>', $info);
		$info = str_replace(" ", '&nbsp;', $info);
		echo "<div style=\"border:1px dashed #222222;margin:2px;font: 12px Verdan;line-height: 20px;background-color: #FFFFE0;padding: 10px;text-align:left;\">".$info."</div>";
	} elseif($type == 1) {
		$info = str_replace("\n", '\n', $info);
		echo "<html><head><meta http-equiv='Content-Type' content='text/html; charset={$header_charset}' />";
		echo "<script>alert('".$info."');</script></head><body>";
	} elseif($type == 2) {
		$info = str_replace("\n", '\n', $info);
		echo "alert(\"".$info."\");";
	} elseif($type == 3) {
		echo($info."\n");
	}
	if($exit == 1) {
		if(function_exists('aexit')) aexit('');
		exit();
	}
}

function checkfilename($filename, $noempty = '') {
	global $lan;
	if(empty($filename)) {
		if($noempty == '') {
			return '';
		} else {
			return $lan['noempty'];
		}
	}
	if(strpos($filename, 'php') !== false && !iscreator()) {
		return $lan['nophp'];
	}
	if(!preg_match('/^[\/\._0-9a-zA-Z\-]*$/i', $filename)) {
		return $lan['specialcharacter'];
	}
	if(preg_match('/\.\.\//i', $filename)) {
		return $lan['parentpathforbidden'];
	}
	return '';
}

function a_is_int($number) {
	if(substr($number, 0, 1) == '-') $number = substr($number, 1);
	return preg_match ("/^([0-9]+)$/", $number);
}

function random($length, $numeric = 0) {
	mt_srand((double)microtime() * 1000000);
	if($numeric) {
		$hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
	} else {
		$hash = '';
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $chars[mt_rand(0, $max)];
		}
	}
	return $hash;
}

function fileext($filename) {
	$ext = strtolower(trim(substr(strrchr($filename, '.'), 1)));
	$offset = strpos($ext, '?');
	if($offset !== false) {
		return substr($ext, 0, $offset);
	} else {
		return $ext;
	}
}

function ispicture($filename) {
	$ext = fileext($filename);
	if(in_array($ext, array('gif', 'jpg', 'jpeg', 'png', 'bmp'))) return true;
	return false;
}

function uploadfile($file, $newname) {
	$file = str_replace('\\\\', '\\', $file);
	if(!is_uploaded_file($file)) return false;
	$path = pathinfo($newname);
	if(!is_dir($path['dirname'])) ak_mkdir($path['dirname']);
	return move_uploaded_file($file, $newname);
}

function ak_ftpput($source, $target, $server = '') {
	global $attachserver;
	static $conn_id;
	if(empty($server) && !empty($attachserver)) $server = $attachserver;
	if(empty($server)) return false;
	list($userinfo, $hostinfo) = explode('@', $server);
	list($user, $pass) = explode(':', $userinfo);
	$dir = '';
	if($offset = strpos($hostinfo, '/')) {
		$dir = substr($hostinfo, $offset);
		$hostinfo = substr($hostinfo, 0, $offset);
	}
	if(strpos($hostinfo, ':') === false) {
		$host = $hostinfo;$port = 21;
	} else {
		list($host, $port) = explode(':', $hostinfo);
	}
	list($user, $pass) = explode(':', $userinfo);
	$key = md5($server, 1);
	if(empty($conn_id[$key])) {
		$conn_id[$key] = ftp_connect($host, $port);
		if($conn_id[$key] === false) return false;
		ftp_login($conn_id[$key], $user, $pass);
		if($dir != '') ftp_chdir($conn_id[$key], $dir);
	}
	return ftp_put($conn_id[$key], $target, $source, FTP_BINARY);
}

function refreshself($timeout) {
	$script = "<script language='javascript'>setTimeout(\"document.location.reload()\", [timeout]);</script>";
	$script = str_replace('[timeout]', $timeout, $script);
	aexit($script);
}

function unaddslashes($str) {
	global $charset;
	if(is_array($str)) {
		foreach($str as $key => $val) {
			$str[$key] = unaddslashes($val);
		}
	} else {
		$str = stripslashes($str);
	}
	return $str;
}

function mysql_addslashes($value) {
	global $charset;
	if(is_array($value)) {
		foreach($value as $k => $v) {
			$value[$k] = mysql_addslashes($v);
		}
	} else {
		if($charset == 'gbk') {
			$value = gbk_addslashes($value);
		} else {
			if(function_exists('mysql_real_escape_string')) {
				$value = mysql_real_escape_string($value);
			} else {
				$value = addslashes($value);
			}
		}
	}
	return $value;
}

function sqlite_addslashes($value) {
	if(is_array($value)) {
		foreach($value as $k => $v) {
			$value[$k] = sqlite_addslashes($v);
		}
	} else {
		if(function_exists('sqlite_escape_string')) {
			$value = sqlite_escape_string($value);
		} else {
			$value = str_replace("'", "''", $value);
		}
	}
	return $value;
}

function gbk_addslashes($text) {
	if(!function_exists('mb_strpos')) return addslashes($text);
	if(strpos($text, '\\') === false) return addslashes($text);
	$ok = '';
	while(1) {
		$i = mb_strpos($text, chr(92), 0, 'GBK');
		if($i === false) break;
		$t = mb_substr($text, 0, $i, 'GBK').chr(92).chr(92);
		$text = substr($text, strlen($t) - 1);
		$ok .= $t;
	}
	$text = $ok.$text;
	$text = str_replace(chr(39), chr(92).chr(39), $text);
	$text = str_replace(chr(34), chr(92).chr(34), $text);
	return $text;
}

function gbk_stripslashes($text) {
	$text = str_replace(chr(92).chr(34), chr(34), $text);
	$text = str_replace(chr(92).chr(39), chr(39), $text);
	$ok = '';
	while(1) {
		$i = mb_strpos($text, chr(92).chr(92), 0, 'GBK');
		if($i === false) break;
		$t = mb_substr($text, 0, $i, 'GBK').chr(92);
		$text = substr($text, strlen($t) + 1);
		$ok .= $t;
	}
	$text = $ok.$text;
	return $text;
}

function htmltitle($title, $color = '', $style = '') {
	$output = $title;
	if(!empty($style)) {
		if($style == 'b') $output = "<b>{$output}</b>";
		if($style == 'i') $output = "<i>{$output}</i>";
	}
	if(!empty($color)) $output = "<font color=\"#{$color}\">{$output}</font>";
	return $output;
}

function displaytemplate($template) {
	global $smarty, $lan;
	if(file_exists(AK_ROOT.'configs/templates/'.$template)) $templatefilename = $template = AK_ROOT.'configs/templates/'.$template;
	$smarty->template_dir = CORE_ROOT."templates";
	$smarty->assign('lan', $lan);
	if(!isset($templatefilename)) $templatefilename = $smarty->template_dir[0].'/'.$template;
	$smarty->compile_dir = AK_ROOT."cache/templates";
	$smarty->config_dir = AK_ROOT."configs/";
	$smarty->cache_dir = AK_ROOT."cache/";
	$smarty->left_delimiter = "<{";
	$smarty->right_delimiter = "}>";
	$smarty->error_reporting = true;
	$smarty->assign('sysname', $GLOBALS['sysname']);
	$smarty->assign('sysedition', $GLOBALS['sysedition']);
	$smarty->assign('header_charset', $GLOBALS['header_charset']);
	$smarty->assign('ak_url', AK_URL);
	$smarty->assign('core_url', CORE_URL);
	$smarty->assign('language', $GLOBALS['language']);
	if(!empty($GLOBALS['setting_sitename'])) $smarty->assign('sitename', $GLOBALS['setting_sitename']);
	if(file_exists(AK_ROOT.'configs/customer.css')) $smarty->assign('customcss', 1);
	$smarty->display($template);
}

function ak_utf8_encode($var) {
	if(!function_exists('iconv')) return $var;
	if(is_array($var)) {
		foreach($var as $id => $value) {
			$var[$id] = ak_utf8_encode($value);
		}
		return $var;
	} else {
		return iconv('GBK', 'UTF-8//IGNORE', $var);
	}
}

function utf8togbk($var) {
	if(!function_exists('iconv')) return $var;
	if(is_array($var)) {
		foreach($var as $id => $value) {
			$var[$id] = utf8togbk($value);
		}
		return $var;
	} else {
		return iconv('UTF-8', 'GBK//IGNORE', $var);
	}
}

function gbktoutf8($var) {
	if(!function_exists('iconv')) return $var;
	if(is_array($var)) {
		foreach($var as $id => $value) {
			$var[$id] = gbktoutf8($value);
		}
		return $var;
	} else {
		return iconv('GBK', 'UTF-8//IGNORE', $var);
	}
}

function tidyitemlist($str, $separator = ',', $int = 1) {
	$array = explode($separator, $str);
	$array = array_unique($array);
	$array2 = array();
	foreach($array as $item) {
		$item = trim($item);
		if($item != '' && !in_array($item, $array2) && (!$int || a_is_int($item))) {
			$array2[] = $item;
		}
	}
	return implode($separator, $array2);
}

function ak_substr($str, $start, $len = 0xFFFFFFFF, $strip = '', $charset_force = '') {
	global $charset;
	$old_length = strlen($str);
	$return = '';
	if(!empty($charset_force)) {
		$charset_str = $charset_force;
	} else {
		$charset_str = $charset;
	}
	if($charset_str == 'gbk') {
		$return = gbk_substr($str, $start, $len);
	} elseif($charset_str == 'utf8') {
		$return = utf8_substr($str, $start, $len);
	} else {
		$return = substr($str, $start, $len);
	}
	$new_length = strlen($return);
	if($new_length < $old_length) {
		$return .= $strip;
	}
	return $return;
}

function gbk_substr($str, $start, $len=0xFFFFFFFF) {
	if($start < 0) $start = strlen($str) + $start;
	if($len < 0) $len = strlen($str) - $start + $len;
	$tmp = '';
	$result = '';
	$strlen = strlen($str);
	$begin = 0;
	$subLen = 0;
	for($i = 0; $i < $start + $len && $i < $strlen; $i++) {
		if($i < $start) {
			if(ord($str[$i]) >= 161 && ord($str[$i]) <= 247 && ord($str[$i+1]) >= 161 && ord($str[$i+1]) <= 254) $i++;
		} else {
			$begin = $i;
			for(; $i < $start + $len && $i < $strlen; $i ++) {
				if(ord($str[$i]) >= 161 && ord($str[$i]) <= 247 && isset($str[$i+1]) && ord($str[$i+1]) >= 161 && ord($str[$i+1]) <= 254) $i++;
			}
			return substr($str, $begin, $i - $begin);
		}
	}
}

function utf8_substr($str, $start, $len) {
	if($len == 0) return '';
	for($i = 0; $i < $len; $i++) {
		$temp_str = substr($str, 0, 1);
		if(ord($temp_str) > 127) {
			$i ++;
			if($i < $len) {
				$new_str[] = substr($str, 0, 3);
				$str = substr($str, 3);
			}
		} else {
			$new_str[] = substr($str, 0, 1);
			$str=substr($str,1);
		}
	}
	return join($new_str);
}

function ak_replace($find, $replace, $str, $caseless = 1, $count = -1) {//$caseless是否区分大小写，0为不区分
	if(!is_array($find)) {
		$find = array($find);
	}
	if(!is_array($replace)) {
		$replace = array($replace);
	}
	if(count($find) != count($replace)) return false;
	if($caseless == 1) {
		foreach($find as $id => $f) {
			if($f == '') continue;
			if(strpos($str, $f) === false) continue;
			$str = str_replace_count($f, $replace[$id], $str, $count);
		}
	} else {
		foreach($find as $id => $f) {
			$f = str_replace('/', '\/', $f);
			if(!preg_match("/{$f}/i", $str)) continue;
			$str = preg_replace("/{$f}/i", $replace[$id], $str, $count);
		}
	}
	return $str;
}

function ak_array_replace($finds, $replaces, $str) {
	$_str = $str;
	foreach($finds as $key => $value) {
		$r = '';
		if(isset($replaces[$key])) $r = $replaces[$key];
		$_str = ak_replace($value, $r, $_str);
	}
	return $_str;
}

function str_replace_count($search, $replace, $string, $count) {
	if($count < 0) {
		return str_replace($search, $replace, $string);
	} elseif($count == 0) {
		return $string;
	} else {
		return str_replace_count($search, $replace, str_replace_once($search, $replace, $string), $count - 1);
	}
}

function str_replace_once($search, $replace, $string) {
	$pos = strpos($string, $search);
	if($pos === false) return true;
	$return = '';
	$s1 = substr($string, 0, $pos);
	$s2 = substr($string, $pos + strlen($search));
	return $s1.$replace.$s2;
}

function in_string($string, $findme) {
	if(is_string($findme)) {
		$pos = strpos($string, $findme);
		if($pos === false) return false;
		return true;
	}
	
	if(!is_array($findme)) return false;
	foreach($findme as $find) {
		$find = trim($find);
		if($find == '') continue;
		if(strpos($string, $find) !== false) return true;
	}
	return false;
}

function getfield($start, $end, $content, $repeatsplit = '') {
	if(empty($content)) return false;
	$return = '';
	while(1) {
		$start_position = 0;
		$end_position = strlen($content);
		if($start != '') $start_position = strpos($content, $start);
		if($start_position === false) break;
		$start_position += strlen($start);
		if($end != '') $end_position = strpos($content, $end, $start_position);
		if($end_position === false) break;
		$return .= substr($content, $start_position, $end_position - $start_position);
		if(empty($repeatsplit)) return $return;
		$return .= $repeatsplit;
		$content = substr($content, $end_position + strlen($end));
	}
	if(strlen($return) > strlen($repeatsplit)) $return = substr($return, 0, strlen($return) - strlen($repeatsplit));
	return $return;
}

function ak_htmlspecialchars($array) {
	global $charset;
	if(!is_array($array) && !is_object($array)) {
		$isvariable = 1;
		$array = array($array);
	}
	foreach($array as $key => $value) {
		if(is_array($value)) {
			$array[$key] = ak_htmlspecialchars($value);
		} elseif(is_object($value)) {
			$array[$key] = ak_htmlspecialchars(get_object_vars($value));
		} elseif(is_scalar($value)) {
			/*
			$value = str_replace('&', '&amp;', $value);
			$value = str_replace('"', '&quot;', $value);
			$value = str_replace('\'', '&#039;', $value);
			$value = str_replace('<', '&lt;', $value);
			$value = str_replace('>', '&gt;', $value);
			*/
			if($charset == 'gbk' && PHP_VERSION > '5.4') {
				$value = htmlspecialchars($value, ENT_SUBSTITUTE, 'gb2312');
			} else {
				$value = htmlspecialchars($value);
			}
			$array[$key] = $value;
		} elseif($value === false) {
			$array[$key] = '(bool)false';
		} elseif($value === true) {
			$array[$key] = '(bool)true';
		} elseif(is_null($value)) {
			$array[$key] = '(null)';
		} else {
			$array[$key] = '-';
		}
	}
	if(!isset($isvariable)) {
		return $array;
	} else {
		return $array[0];
	}
}

function ak_md5($string, $short = 0, $time = 1) {
	for($i = $time; $i >= 1; $i --) {
		$string = md5($string);
	}
	if($short == 0) return $string;
	return substr($string, 8, 16);
}

function readpathtoarray($path, $shortfilename = 0) {
	$return = array();
	if(!file_exists($path)) return $return;
	$fp = opendir($path);
	while (false !== ($file = readdir($fp))) {
		if($file == '.' || $file == '..') continue;
		if($shortfilename == 1) {
			$return[] = $file;
		} else {
			if(substr($path, -1) == '/') {
				$return[] = $path.$file;
			} else {
				$return[] = $path.'/'.$file;
			}
		}
	}
	closedir($fp);
	return $return;
}

function getwee($dateline = 0, $type = 'day') {
	global $thetime;
	empty($dateline) && $dateline = $thetime;
	list($year, $month, $day) = explode('-', date('Y-m-d', $dateline));
	if($type == 'day') {
		return mktime(0, 0, 0, $month, $day, $year);
	} elseif($type == 'month') {
		return mktime(0, 0, 0, $month, 1, $year);
	} elseif($type == 'year') {
		return mktime(0, 0, 0, 1, 1, $year);
	}
}

function convcharset($from, $to, $str) {
	if(function_exists('iconv')) {
		if(is_array($str)) {
			foreach($str as $key => $value) {
				$str[$key] = convcharset($from, $to, $value);
			}
			return $str;
		} elseif(is_string($str)) {
			return iconv($from, $to, $str);
		}
	} else {
		return $str;
	}
}

function querytoarray($url) {
	$return = array();
	$parsed = parse_url($url);
	if(!isset($parsed['query'])) return array();
	$array_query = explode('&', $parsed['query']);
	foreach($array_query as $query) {
		$keyvalue = explode('=', $query);
		if(isset($keyvalue[1])) {
			$return[$keyvalue[0]] = $keyvalue[1];
		}
	}
	return $return;
}

function whospider() {
	if(function_exists('curl_init') && function_exists('curl_exec')) {
		return 'curl';
	} elseif(function_exists('fsockopen')) {
		return 'fsock';
	} elseif(ini_get('allow_url_fopen') == '1') {
		return 'fopen';
	} elseif(!empty($GLOBALS['wget']) && function_exists('system')) {
		return 'wget';
	}
	return false;
}

function readfromurl($url, $convertcharset = 0, $type = '') {
	global $charset;
	if($type == '') $type = whospider();
	$agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; AKCMS)';
	if($type == 'wget') {
		$_tmp = AK_ROOT.'cache/'.md5($url);
		system("wget --timeout=10 --tries=3 --no-check-certificate --user-agent=\"$agent\" -q -O {$_tmp} \"".$url."\"");
		$content = readfromfile($_tmp);
		@unlink($_tmp);
	} elseif($type == 'curl') {
		$ch = curl_init();
		@curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		$content = curl_exec($ch);
		curl_close($ch);
	} elseif($type == 'fsock') {
		$offset = strpos($url, '://');
		if($offset === false) return false;
		if(strpos($url, '/', $offset + 3) === false) $url .= '/';
		$parts = parse_url($url);
		$host = $parts['host'];
		$port = 80;
		if($parts['scheme'] == 'https') $port = 443;
		$path = $parts['path'];
		if(!empty($parts['query'])) $path .= "?".$parts['query'];
		$request =  "GET ".$path." HTTP/1.0\r\n";
		$request .= "Host: ".$host."\r\n";
		$request .= "Accept: */*\r\n";
		$request .= "Connection: keep-alive\r\n";
		$request .= "User-Agent: {$agent}\r\n\r\n";
		if($parts['scheme'] == 'https') {
			$sHnd = fsockopen("ssl://".$host, $port, $errno, $errstr, 30);
		} else {
			$sHnd = fsockopen($host, $port, $errno, $errstr, 30);
		}
		if($sHnd === false) {
			debug($errstr);
			return false;
		}
		fputs($sHnd, $request);
		$content = '';
		while(!feof($sHnd)) {
			if(!isset($step)) $step = 4096;
			$line = fgets($sHnd, $step + 1);
			if(strpos($line, 'Location:') === 0) {
				$url = substr(trim($line), 10);
				return readfromurl($url, $convertcharset, $type);
			}
			if(isset($size) && isset($length)) {
				$content .= $line;
				if($length != -1) {
					$size += strlen($line);
					$step = min(4096, $length - $size);
					if($step <= 0) break;
				}
			}
			if(substr($line, 0, 15) == 'Content-Length:') $length = intval(substr($line, 15));
			if($line == "\r\n" && !isset($size)) {
				$size = 0;
				if(!isset($length)) $length = -1;
			}
		}
		fclose($sHnd);
	} elseif($type == 'fopen') {
		@ini_set('user_agent', $agent);
		$content = readfromfile($url);
	} else {
		return 'ERROR:spider disabled!';
	}
	if(!empty($convertcharset)) {
		if(strpos(strtolower($content), 'charset=gb') !== false && $charset == 'utf8') {
			$content = gbktoutf8($content);
		} elseif(strpos(strtolower($content), 'charset=utf') !== false && $charset == 'gbk') {
			$content = utf8togbk($content);
		}
	}
	return $content;
}

function akmicrotime() {
	$mtime = microtime();
	$s = substr($mtime, 11, 10);
	$ms = substr($mtime, 1, 9);
	return number_format($s.$ms, 4, '.', '');
}

function monitor($sign = '', $id = 0) {
	global $exe_times, $exe_signs, $exe_mems;
	if(is_array($sign)) $sign = reset($sign);
	if(!isset($exe_times)) $exe_times[$id] = array();
	if(!isset($exe_signs)) $exe_signs[$id] = array();
	$exe_times[$id][] = akmicrotime();
	$exe_mems[$id][] = ak_memused();
	$exe_signs[$id][] = $sign;
}

function monitor_log($logfile = '', $id = 0) {
	global $exe_times, $exe_signs, $exe_mems;
	if(!isset($exe_times) || !isset($exe_signs)) return false;
	$exe_times[$id][] = akmicrotime();
	$exe_signs[$id][] = 'END';
	$exe_mems[$id][] = ak_memused();
	$total = msformat(end($exe_times[$id]) - $exe_times[$id][0]);
	for($i = count($exe_times[$id]) - 1; $i >= 1; $i --) {
		$exe_times[$id][$i] = msformat($exe_times[$id][$i] - $exe_times[$id][$i - 1]);
	}
	$exe_times[$id][0] = date('Y-m-d H:i:s', $exe_times[$id][0]);
	$exes = array();
	$exes[0] = "0.000 ({$exe_mems[$id][$i]}) ({$exe_signs[$id][$i]}) {$exe_times[$id][0]}";
	for($i = 1; $i < count($exe_times[$id]); $i ++) {
		$exes[] = "{$exe_times[$id][$i]} ({$exe_mems[$id][$i]}) ({$exe_signs[$id][$i]})";
	}
	$exes[] = "$total (TOTAL)";
	if($logfile == '') {
		debug($exes);
	} else {
		aklog(implode("\t", $exes), AK_ROOT.'logs/'.$logfile);
	}
	unset($GLOBALS['exe_times'][$id]);
	unset($GLOBALS['exe_signs'][$id]);
}

function msformat($number) {
	return number_format($number, 4, '.', '');
}

function ak_filetime($filename) {
	global $timedifference;
	return filemtime($filename) + $timedifference * 3600;
}

function clearhtml($html, $force = 0) {
	$html = str_replace("\r", '', $html);
	$html = str_replace("\n", '', $html);
	$html = preg_replace("/\s+/", ' ', $html);
	$html = str_replace("> <", '><', $html);
	return $html;
}

function ak_rmdir($dir) {
	if(!file_exists($dir)) return;
	if($handle = opendir("$dir")) {
		while(false !== ($item = readdir($handle))) {
		if($item != "." && $item != "..") {
			if(is_dir("$dir/$item")) {
				ak_rmdir("$dir/$item");
			} else {
				unlink("$dir/$item");
			}
		}
	}
	closedir($handle);
	rmdir($dir);
	}
}

function getdomain($url) {//从url中截取域名
	$p1 = strpos($url, '://') + 3;
	$p2 = strpos($url, '/', $p1);
	return substr($url, $p1, $p2 - $p1);
}

function geturlpath($url) {
	if(substr($url, -1) == '/') return $url;
	$pos = ak_strrpos($url, '/');
	return substr($url, 0, $pos + 1);
}

function runquery($sql) {
	global $db;
	$ret = array();
	$num = 0;
	$sql = str_replace("\r\n", "\n", $sql);
	foreach(explode(";\n", trim($sql)) as $query) {
		$queries = explode("\n", trim($query));
		$ret[$num] = '';
		foreach($queries as $query) {
			if(preg_match('/^--/i', $query) || preg_match('/^#/i', $query)) {
				continue;
			}
			$ret[$num] .= $query;
		}
		$num++;
	}
	unset($sql);
	foreach($ret as $query) {
		$query = trim($query);
		if($query) $db->query($query);
	}
}

function ak_strrpos($string, $findme) {
	if(PHP_VERSION < '5.0') {
		$string = strrev($string);
		$findme = strrev($findme);
		$_pos1 = strpos($string, $findme);
		return strlen($string) - $_pos1 - strlen($findme);
	} else {
		return strrpos($string, $findme);
	}
}

function sortbylength($array) {
	$_a = array();
	foreach($array as $key => $string) {
		$_a[$key] = strlen($string);
	}
	asort($_a);
	$return = array();
	foreach($_a as $key => $value) {
		$return[] = $array[$key];
	}
	return $return;
}

function calfilenamefromurl($url) {
	$_pos = ak_strrpos($url, '/');
	return substr($url, $_pos + 1);
}

function calfilenamefromurl2($url) {
	$_pos = ak_strrpos($url, '/');
	return substr($url, $_pos + 1);
}

function ak_unserialize($str) {
	$return = @unserialize($str);
	if($return === false) {
		return '';
	} else {
		return $return;
	}
}

function htmltotext($html) {
	if(strpos($html, '<') === false) return $html;
	$html = preg_replace("/<br(.*?)>/is", '', $html);
	$text = strip_tags($html, '');
	return $text;
}

function mysql_createtable($key, $data) {
	global $db;
	$mysqlversion = $db->version();
	$sql = '';
	$sql .= "DROP TABLE IF EXISTS `{$key}`;\n";
	$sql .= "CREATE TABLE `$key`(\n";
	foreach($data['fields'] as $k => $v) {
		if($v['type'] == 'text' || $v['type'] == 'mediumtext') {
			$sql .= "`$k` {$v['type']} NOT NULL default ''";
		} else {
			if($v['type'] == 'float') {
				$sql .= "`$k` {$v['type']}";
			} else {
				if($v['type'] == 'int') $v['length'] = 11;
				$sql .= "`$k` {$v['type']}({$v['length']})";
			}
			if(!empty($v['unsigned'])) $sql .= " unsigned";
			if(empty($v['null'])) $sql .= " NOT NULL";
			if(isset($v['default'])) {
				$sql .= " default '{$v['default']}'";
			} else {
				if($v['type'] == 'varchar') $sql .= " default ''";
				if(strpos($v['type'], 'int') !== false && empty($v['auto_increment'])) $sql .= " default 0";
			}
			if(!empty($v['auto_increment'])) $sql .= ' auto_increment';
		}
		$sql .= ",\n";
	}
	if(!empty($data['indexs'])) {
		foreach($data['indexs'] as $k => $v) {
			if($v['type'] == 'primary') {
				$sql .= "PRIMARY KEY(`{$k}`),\n";
			} else {
				foreach($v['value'] as $_k => $_v) {
					$v['value'][$_k] = "`{$_v}`";
				}
				if($v['type'] == 'unique') $sql .= "UNIQUE ";
				$sql .= "KEY `$k`(".implode(',', $v['value'])."),\n";
			}
		}
	}
	$sql = substr($sql, 0, -2)."\n";
	$sql .= ")";
	if($mysqlversion < 4) {
		if(isset($data['engine'])) {
			if($data['engine'] == 'memory')	$sql .= " TYPE=HEAP";
		} else {
			$sql .= " TYPE=MYISAM";
		}
	} else {
		if(isset($data['engine'])) {
			if($data['engine'] == 'memory')	$sql .= " ENGINE=MEMORY";
		} else {
			$sql .= " ENGINE=MYISAM";
		}
	}
	if(isset($data['charset']) && $db->version() > '4.1') {
		if($data['charset'] == 'utf8') {
			$sql .= ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
		} elseif($data['charset'] == 'gbk') {
			$sql .= ' DEFAULT CHARACTER SET gbk COLLATE gbk_chinese_ci';
		} elseif($data['charset'] == 'english') {
			$sql .= ' DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci';
		}
	}
	return $sql;
}

function sqlite_createtable($key, $data) {
	$sql = '';
	$indexs_sql = '';
	$sql .= "CREATE TABLE {$key}(\n";
	foreach($data['fields'] as $k => $v) {
		if($v['type'] == 'int' || $v['type'] == 'mediumint' || $v['type'] == 'smallint' || $v['type'] == 'tinyint') $v['type'] = 'INTEGER';
		if($v['type'] == 'text') {
			$sql .= "{$k} text default '',\n";
			continue;
		}
		if($v['type'] == 'INTEGER' || $v['type'] == 'float') {
			$sql .= "{$k} {$v['type']}";
		} else {
			$sql .= "{$k} {$v['type']}({$v['length']})";
		}
		if(isset($v['default'])) {
			$sql .= " default '{$v['default']}'";
		} else {
			if($v['type'] == 'varchar') $sql .= " default ''";
			if($v['type'] == 'INTEGER' && $k != 'id') $sql .= " default 0";
		}
		$sql .= ",\n";
	}
	foreach($data['indexs'] as $k => $v) {
		if($v['type'] == 'primary') {
			$sql .= "PRIMARY KEY({$k})\n";
		} else {
			$indexs_sql .= "CREATE";
			if($v['type'] == 'unique') $indexs_sql .= " UNIQUE";
			$indexs_sql .= " INDEX {$k} on {$key}(".implode(',', $v['value']).");\n";
		}
	}
	$sql .= ");\n".$indexs_sql;
	return $sql;
}

function lan($charset, $language = 'english') {
	if($language == 'english') $charset = 'english';
	$languagefilename = CORE_ROOT."include/language/{$language}/{$charset}.lan";
	if(!file_exists($languagefilename)) aexit($charset.$language.' language not exist');
	$lan = array();
	$landata = readfromfile($languagefilename);
	$array = explode("\n", $landata);
	foreach($array as $_line) {
		$_fields = explode("\t", $_line);
		if(count($_fields) != 2) continue;
		$lan[$_fields[0]] = $_fields[1];
	}
	return $lan;
}

function userlan($charset, $language = 'english') {
	if($language == 'english') $charset = 'english';
	$languagefilename = CORE_ROOT."include/language/{$language}/{$charset}.user.lan";
	if(!file_exists($languagefilename)) aexit($charset.$language.' language not exist');
	$lan = array();
	$landata = readfromfile($languagefilename);
	$array = explode("\n", $landata);
	foreach($array as $_line) {
		$_fields = explode("\t", $_line);
		if(count($_fields) != 2) continue;
		$lan[$_fields[0]] = $_fields[1];
	}
	return $lan;
}

function calheadercharset($charset) {
	if($charset == 'gbk') {
		return 'gbk';
	} elseif($charset == 'utf8') {
		return 'utf-8';
	} elseif($charset == 'english') {
		return 'iso-8859-1';
	}
}

function caldbsetname($charset) {
	if($charset == 'gbk') {
		return 'gbk';
	} elseif($charset == 'utf8') {
		return 'utf8';
	} elseif($charset == 'english') {
		return 'latin1';
	}
}

function br2nl($html) {
	$text = preg_replace("/<br(.*?)>/is", "\n", $html);
	return $text;
}

function ak_xor($string, $key = '') {
	if('' == $string) return '';
	if('' == $key) $key = 'akcms';
	$len1 = strlen($string);
	$len2 = strlen($key);
	if($len1 > $len2) $key = str_repeat($key, ceil($len1 / $len2));
	return $string ^ $key;
}

function ak_memused() {
	if(function_exists('memory_get_usage')) return memory_get_usage();
	return 0;
}

function isobscure($variable) {
	if(in_array(substr($variable, 0, 1), array('I', 'O'))) return true;
	return false;
}

function highlight($text, $keywords) {
	$keywords = explode(' ', trim($keywords));
	foreach($keywords as $keyword) {
		$keyword = trim($keyword);
		if($keyword == '') continue;
		$text = replacekeyword($text, $keyword, "<span class='highlight'>$keyword</span>", 0, -1);
	}
	return $text;
}

function replacekeyword($text, $replace, $to, $caseless = 1, $count = -1) {
	$_replace2 = array();
	$_to2 = array();
	$_replace = array();
	$_to = array();
	preg_match_all('/<a(.*?)>(.*?)<\/a>/i', $text, $matchs);
	foreach($matchs[0] as $match) {
		if(strpos($match, $replace) === false) continue;
		$_replace[] = $match;
		$_to[] = md5($match);
	}
	if(!empty($_replace) && !empty($_to)) {
		$text = ak_replace($_replace, $_to, $text, $caseless, $count);
	}
	preg_match_all('/<(.*?)>/i', $text, $matchs);
	foreach($matchs[0] as $match) {
		if(in_string($match, $replace) == 0) continue;
		$_replace2[] = $match;
		$_to2[] = md5($match);
	}
	if(!empty($_replace2) && !empty($_to2)) {
		$text = ak_replace($_replace2, $_to2, $text, $caseless, $count);
	}
	$text = ak_replace($replace, $to, $text, $caseless, $count);
	if(!empty($_replace) && !empty($_to)) {
		$text = ak_replace($_to, $_replace, $text, $caseless, $count);
	}
	if(!empty($_replace2) && !empty($_to2)) {
		$text = ak_replace($_to2, $_replace2, $text, $caseless, $count);
	}
	return $text;
}

function ak_strtotime($str) {
	$return = strtotime($str);
	if($return === false || $return == -1) {
		$str = str_replace(chr(196).chr(234), '-', $str);
		$str = str_replace(chr(212).chr(194), '-', $str);
		$str = str_replace(chr(200).chr(213), ' ', $str);
		$return = strtotime($str);
	}
	return $return;
}

function ak_if($variable, $if, $else = '') {
	return '';
	eval('$result = '.$variable.';');
	if(!empty($result)) {
		return $if;
	} else {
		return $else;
	}
}

function sendmail($to, $subject, $html) {
	global $smtp;
	require_once CORE_ROOT.'include/mail.func.php';
	if(!isset($smtp)) $smtp = new smtp();
	$result = $smtp->sendmail($to, $subject, $html);
	if($result === false) {
		eventlog('Send Mail to :'.$to.' ERROR!');
		unset($smtp);
		return false;
	}
	eventlog('Send Mail to: '.$to.' success!');
}
function post_request($url, $params = array(), $postfield = '') {
	if(!empty($postfield)) {
		$str = $postfield;
	} else {
		$str = '';
		foreach($params as $k => $v) {
			$str .= '&'.$k.'='.urlencode($v);
		}
		$str = substr($str, 1);
	}
	$error = 1;
	if(function_exists('curl_init')) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, '');
		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
	} else {
		$context = array(
			'http' => array(
				'method' => 'POST',
				'header' => "Content-type: application/x-www-form-urlencoded\r\nUser-Agent:\r\nContent-length:".strlen($str),
				'content' => $str
			)
		);
		$contextid = stream_context_create($context);
		$sock = fopen($url, 'r', false, $contextid);
		if($sock){
			$errno = 0;
			$result = '';
			while(!feof($sock)) {
				$result .= fgets($sock, 4096);
			}
			fclose($sock);
		}
	}
	return array(
		'errno' => $errno,
		'result' => $result
	);
}
function unzip($zip, $to = '.') {
	$size = filesize($zip);
	$maximum_size = min(277, $size);
	$fp = fopen($zip, 'rb');
	fseek($fp, $size - $maximum_size);
	$pos = ftell($fp);
	$bytes = 0x00000000;
	while($pos < $size) {
		$byte = fread($fp, 1);
		if(PHP_INT_MAX > 2147483647) {
			$bytes = ($bytes << 32);
			$bytes = ($bytes << 8);
			$bytes = ($bytes >> 32);
		} else {
			$bytes = ($bytes << 8);
		}
		$bytes = $bytes | ord($byte);
		if($bytes == 0x504b0506) {
			$pos ++;
			break;
		}
		$pos ++;
	}
	$fdata = fread($fp, 18);
	$data = @unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size',$fdata);
	$pos_entry = $data['offset'];
	for($i=0; $i < $data['entries']; $i++) {
		fseek($fp, $pos_entry);
		$header = ReadCentralFileHeaders($fp);
		$header['index'] = $i;
		$pos_entry = ftell($fp);
		rewind($fp);
		fseek($fp, $header['offset']);
		$stat[$header['filename']] = ExtractFile($header, $to, $fp);
	}
	fclose($fp);
}

function ReadCentralFileHeaders($fp) {
	$binary_data = fread($fp, 46);
	$header = unpack('vchkid/vid/vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $binary_data);
	$header['filename'] = $header['extra'] = $header['comment'] = '';
	if($header['filename_len'] != 0) $header['filename'] = fread($fp, $header['filename_len']);
	if($header['extra_len'] != 0) $header['extra'] = fread($fp, $header['extra_len']);
	if($header['comment_len'] != 0) $header['comment'] = fread($fp, $header['comment_len']);
	$header['mtime'] = time();
	$header['stored_filename'] = $header['filename'];
	$header['status'] = 'ok';
	if(substr($header['filename'], -1) == '/') $header['external'] = 0x41FF0010;
	return $header;
}

function ReadFileHeader($fp) {
	$binary_data = fread($fp, 30);
	$data = unpack('vchk/vid/vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $binary_data);

	$header['filename'] = fread($fp, $data['filename_len']);
	if ($data['extra_len'] != 0) {
	  $header['extra'] = fread($fp, $data['extra_len']);
	} else { $header['extra'] = ''; }

	$header['compression'] = $data['compression'];
	$header['size'] = $data['size'];
	$header['compressed_size'] = $data['compressed_size'];
	$header['crc'] = $data['crc']; $header['flag'] = $data['flag'];
	$header['mdate'] = $data['mdate'];
	$header['mtime'] = $data['mtime'];

	if ($header['mdate'] && $header['mtime']){
	 $hour=($header['mtime']&0xF800)>>11;$minute=($header['mtime']&0x07E0)>>5;
	 $seconde=($header['mtime']&0x001F)*2;$year=(($header['mdate']&0xFE00)>>9)+1980;
	 $month=($header['mdate']&0x01E0)>>5;$day=$header['mdate']&0x001F;
	 $header['mtime'] = mktime($hour, $minute, $seconde, $month, $day, $year);
	}else{$header['mtime'] = time();}

	$header['stored_filename'] = $header['filename'];
	$header['status'] = "ok";
	return $header;
}

function ExtractFile($header, $to, $fp) {
	$header = readfileheader($fp);
	if(substr($to, -1) != '/') $to .= '/';
	if($to == './') $to = '';
	if(substr($to, 0, 1) == '/') $to = '.'.$to;
	$to = $to.$header['filename'];
	if(substr($to, -1) == '/') {
		ak_mkdir($to);
	} else {
		$path = pathinfo($to);
		if(!is_dir($path['dirname'])) ak_mkdir($path['dirname']);
	}
	if(strrchr($header['filename'],'/')=='/') return 1;
	if($header['compression'] == 0) {
		$nfp = fopen($to, 'wb');
		if(!$nfp) return(-1);
		$size = $header['compressed_size'];
		while ($size != 0) {
			$read_size = ($size < 2048 ? $size : 2048);
			$buffer = fread($fp, $read_size);
			$binary_data = pack('a'.$read_size, $buffer);
			@fwrite($nfp, $binary_data, $read_size);
			$size -= $read_size;
		}
		fclose($nfp);
	} else {
		$nfp = fopen($to.'.gz', 'wb');
		if(!$nfp) return -1;
		$binary_data = pack('va1a1Va1a1', 0x8b1f, Chr($header['compression']), Chr(0x00), time(), Chr(0x00), Chr(3));
		fwrite($nfp, $binary_data, 10);
		$size = $header['compressed_size'];
		while($size != 0) {
			$read_size = ($size < 1024 ? $size : 1024);
			$buffer = fread($fp, $read_size);
			$binary_data = pack('a'.$read_size, $buffer);
			fwrite($nfp, $binary_data, $read_size);
			$size -= $read_size;
		}
		$binary_data = pack('VV', $header['crc'], $header['size']);
		fwrite($nfp, $binary_data,8);
		fclose($nfp);
		$gzp = gzopen($to.'.gz', 'rb');
		if(!$gzp) return(-2);
		$nfp = fopen($to, 'wb');
		if(!$nfp) return(-1);
		$size = $header['size'];
		while($size != 0) {
			$read_size = ($size < 2048 ? $size : 2048);
			$buffer = gzread($gzp, $read_size);
			$binary_data = pack('a'.$read_size, $buffer);
			@fwrite($nfp, $binary_data, $read_size);
			$size -= $read_size;
		}
		fclose($nfp);
		gzclose($gzp);
		unlink($to.'.gz');
	}
	return true;
}

function ak_hmac($algo, $data, $key, $raw_output = false) {
	if(function_exists('hash_hmac')) {
		return hash_hmac($algo, $data, $key, $raw_output);
	} else {
		$algo = strtolower($algo);
		$pack = 'H'.strlen($algo('test'));
		$size = 64;
		$opad = str_repeat(chr(0x5C), $size);
		$ipad = str_repeat(chr(0x36), $size);
		if (strlen($key) > $size) {
			$key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
		} else {
			$key = str_pad($key, $size, chr(0x00));
		}
		for ($i = 0; $i < strlen($key) - 1; $i++) {
			$opad[$i] = $opad[$i] ^ $key[$i];
			$ipad[$i] = $ipad[$i] ^ $key[$i];
		}
		$output = $algo($opad.pack($pack, $algo($ipad.$data)));
		return ($raw_output) ? pack($pack, $output) : $output;
	}
}

function urlencode_rfc3986($input) {
	if (is_array($input)) {
		foreach($input as $k => $v) {
			$input[$k] = urlencode_rfc3986($v);
		}
		return $input;
	} else if (is_scalar($input)) {
		return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
	} else {
		return '';
	}
}

function aksetcookie($name, $value, $expire = 0) {
	global $cookiepre;
	return setcookie($cookiepre.'_'.$name, $value, $expire);
}

function akgetcookie($name) {
	global $cookiepre;
	if(isset($_COOKIE[$cookiepre.'_'.$name])) {
		return $_COOKIE[$cookiepre.'_'.$name];
	} else {
		return false;
	}
}

function xml2array($xml) {
	$parser = xml_parser_create();
	$result = xml_parse_into_struct($parser, $xml, $tags);
	$return = array();
	$a = array();
	$a[1] = &$return;
	foreach($tags as $r) {
		if($r['type'] == 'close') continue;
		$tag = strtolower($r['tag']);
		if($r['type'] == 'complete') {
			$a[$r['level']][$tag] = isset($r['value']) ? $r['value'] : '';
		} else {
			$a[$r['level']][$tag] = array();
		}
		if($r['type'] == 'open') $a[$r['level'] + 1] = &$a[$r['level']][$tag];
	}
	xml_parser_free($parser);
	return $return;
}

function toutf8($text) {
	global $charset;
	if($charset == 'gbk') return gbktoutf8($text);
	return $text;
}

function fromutf8($text) {
	global $charset;
	if($charset == 'gbk') return utf8togbk($text);
	return $text;
}

function togbk($text) {
	global $charset;
	if($charset == 'utf8') return utf8togbk($text);
	return $text;
}

function akheader($head) {
	global $db;
	if(isset($db)) $db->close();
	header($head);
}

function isemail($email) {
	return (strlen($email)>6) && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}

function getcookiesfromheader($header) {
	$cookies = array();
	$headers = explode("\r\n", $header);
	foreach($headers as $h) {
		if(strpos($h, 'Set-Cookie:') === false) continue;
		$k = getfield('Set-Cookie: ', '=', $h);
		$v = getfield('=', '', $h);
		if(strpos($v, ';') !== false) $v = getfield('', ';', $v);
		$cookies[$k] = $v;
	}
	return $cookies;
}

function httppost($url, $params, $cookies = array(), $headers = array()) {
	if(empty($headers['agent'])) {
		$agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.13) AKCMS';
	} else {
		$agent = $headers['agent'];
	}
	$referer = '';
	if(!empty($headers['Referer'])) $referer = $headers['Referer'];
	$parts = parse_url($url);
	$host = $parts['host'];
	$post = $parts['path'];
	if(!empty($parts['query'])) $post.='?'.$parts['query'];
	$str = '';
	foreach($params as $k => $v) {
		$str .= '&'.$k.'='.urlencode($v);
	}
	$str = substr($str, 1);
	$header = '';
	$header .= "POST {$post} HTTP/1.1\r\n";
	$header .= "Host: {$host}\r\n";
	$header .= "Content-type: application/x-www-form-urlencoded\r\n";
	$header .= "X-Requested-With: XMLHttpRequest\r\n";
	$header .= "User-Agent: {$agent}\r\n";
	$header .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
	$header .= "Accept-Language: zh-cn,zh;q=0.5\r\n";
	$header .= "Accept-Encoding: deflate\r\n";
	$header .= "Accept-Charset: GB2312,utf-8;q=0.7,*;q=0.7\r\n";
	$header .= "Keep-Alive:115\r\n";
	$header .= "Connection:keep-alive\r\n";
	$header .= "Referer: $referer\r\n";
	$header .= "Content-length:".strlen($str);
	if(!empty($cookies)) {
		$cs = array();
		foreach($cookies as $k => $v) {
			$cs[] = "$k=$v";
		}
		$header .= "\r\nCookie: ".implode("; ", $cs);
	} elseif(isset($headers['Cookie'])) {
		$header .= "\r\nCookie: ".$headers['Cookie'];
	}
	$header .= "\r\nCache-Control: max-age=0";
	$port = 80;
	if($parts['scheme'] == 'https') $port = 443;
	if($parts['scheme'] == 'https') {
		$sHnd = fsockopen("ssl://".$host, $port, $errno, $errstr, 30);
	} else {
		$sHnd = fsockopen($host, $port, $errno, $errstr, 30);
	}
	fputs($sHnd, $header."\r\n\r\n");
	fputs($sHnd, $str);
	$content = '';
	$i = 1;
	while(!feof($sHnd)) {
		$content .= fgets($sHnd, 4096);
		$i ++;
	}
	$cookies = getcookiesfromheader(getfield('', "\r\n\r\n", $content));
	$content = getfield("\r\n\r\n", '', $content);
	return array('content' => $content, 'cookies' => $cookies);
}

function ifinstalled() {
	return file_exists(AK_ROOT.'configs/install.lock');
}

function setinstalled() {
	ak_touch(AK_ROOT.'configs/install.lock');
}

function preg_replace_prepare($replace) {
	$replace = str_replace('(*)', '#.*?#', $replace);
	$replace = str_replace('(', '\(', $replace);
	$replace = str_replace(')', '\)', $replace);
	$replace = str_replace('#.*?#', '(.*?)', $replace);
	$replace = str_replace('[', '\[', $replace);
	$replace = str_replace(']', '\]', $replace);
	$replace = str_replace('/', '\/', $replace);
	$replace = str_replace('"', '\"', $replace);
	return $replace;
}

function setsetting($variable, $value) {
	global $db;
	$db->replaceinto('settings', array('variable' => $variable, 'value' => $value), 'variable');
	
}

function parselinks($html) {
	$html = strip_tags($html, '<a>');
	preg_match_all("'<\s*a.*?href\s*=(.+?)(\s+.*?)?>(.*?)<\s*/a\s*>'isx", $html, $matchs);
	$links = array();
	foreach($matchs[1] as $key => $link) {
		$link = str_replace('\'', '', $link);
		$link = str_replace('"', '', $link);
		$title = $matchs[3][$key];
		$links[$link] = $title;
	}
	return $links;
}

function getinitial($string) {
	require_once(CORE_ROOT.'include/pinyin.func.php');
	$pinyin = core_pinyin($string);
	return substr($pinyin, 0, 1);
}

function pinyin($string) {
	require_once(CORE_ROOT.'include/pinyin.func.php');
	return core_pinyin($string);
}

function encodeip($ip) {
	$d = explode('.', $ip);
	if(!isset($d[3])) return 'wrong ip';
	$d[3] = '*';
	return implode('.', $d);
}

function stringtoint($string) {
	$md5 = md5($string);
	$int = 0;
	for($i = 0; $i < 32; $i ++) {
		$int += ord($md5[$i]) * ord($md5[$i]);
	}
	return abs($int % mt_getrandmax());
}

# 跟php8冲突
function _match($pattern, $input, $fieldid, $filter = 0, $fieldfilter = 0) {
	preg_match_all($pattern, $input, $match);
	$count = count($match[0]);
	$return = array();
	for($i = 0; $i < $count; $i ++) {
		if(filter($filter, $match[0][$i]) === false) continue;
		$field = filter($fieldfilter, $match[$fieldid][$i]);
		if($field === false) continue;
		$return[] = $field;
	}
	return $return;
}

function pictureurl($filename, $prefix = '') {
	global $homepage;
	if(trim($filename) == '') return '';
	if($prefix == '') $prefix = $homepage;
	if(strpos($filename, 'http://') !== 0) $filename = $homepage.$filename;
	return $filename;
}
?>