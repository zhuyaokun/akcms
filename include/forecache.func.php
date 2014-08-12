<?php
if(!defined('CORE_ROOT')) exit;
function getforecache($key) {
	global $thetime, $mtime;
	$cachefile = AK_ROOT.'configs/forecached_urls.php';
	if(file_exists($cachefile)) $cacheon = 1;
	if(!empty($cacheon)) {
		$_fp = fopen($cachefile, 'r');
		while(!feof($_fp)) {
			$_line = trim(fgets($_fp));
			if(substr($_line, 0, strlen($key) + 1) == $key."\t") {
				$cacheon = 2;
				break;
			}
		}
		@fclose($_fp);
		if($cacheon == 2) {
			$expire = substr($_line, strlen($key) + 1);
			if(!a_is_int($expire)) $expire = 3600;
			if(!$cache = getcache($key, 1)) {
				return false;
			} else {
				if(($thetime - $cache['time'] > $expire) || isset($_SERVER['HTTP_CACHE_CONTROL'])) {
					touchcache($key);
					return false;
				} else {
					$endmtime = explode(' ', microtime());
					$exetime = number_format($endmtime[1] + $endmtime[0] - $mtime[1] - $mtime[0], 3);
					exit($cache['value']."<!--cached $exetime-->");
				}
			}
		}
	}
}

function setforecache($key, $html) {
	return setcache($key, $html, 1);
}
?>