<?php
if(!defined('CORE_ROOT')) exit("xxxxxxxxxxxx");
$sysname = 'AKCMS';
$sysedition = '6.1.2';
$itemfields = array('title', 'shorttitle', 'aimurl', 'filename', 'category', 'section', 'template', 'price', 'author', 'editor', 'source', 'dateline', 'publishtime', 'pageview', 'picture', 'attach', 'comment', 'keywords', 'tags', 'digest', 'data', 'paging', 'orderby', 'orderby2', 'orderby3', 'orderby4', 'orderby5', 'orderby6', 'orderby7', 'orderby8', 'string1', 'string2', 'string3', 'string4', 'pv1', 'pv2', 'pv3', 'pv4', 'outid');
$useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
if($__callmode == 'web') $_vc = ak_md5($useragent."\t".$_SERVER['REMOTE_ADDR'], 1);

function renderdata($data, $options) {
	$html = '';
	$array_templates = array();
	$_array = array();
	foreach($data as $value) {
		$_array = array_merge($_array, $value);
	}
	if(count($data) > 0) $keys = array_keys($_array);
	if(count($data) == 0) {
		if(!empty($options['return404'])) {
			fore404();
		} else {
			return $options['emptymessage'];
		}
	}
	foreach($keys as $key) {
		$array_templates[$key] = "[$key]";
	}
	$i = 0;
	preg_match_all('/\[%?(_[\w_-]{1,99})\]/', $options['template'], $match_datas, PREG_SET_ORDER);
	foreach($data as $id => $record) {
		
		$template = $options['template'];
		if(isset($record['template'])) $template = $record['template'];
		$template = recursiontemplate($options, $record, $template);
		foreach($match_datas as $match_data) {
			$str_key = $match_data[1];
			if(!isset($record[$str_key])) {
				$template = preg_replace("/\[%?{$str_key}(:[0-9a-z]+)?\]/s", '', $template);
			}
		}
		foreach($keys as $key) {
			$suffix = getfield("[$key:", ']', $template);
			if(!empty($suffix)) {
				$array_templates["$key:$suffix"] = "[$key:$suffix]";
				if(a_is_int($suffix)) {
					$_value = htmltotext($record[$key]);
					$record["$key:$suffix"] = ak_substr($_value, 0, $suffix);
				}
				if($suffix == 'text') {
					$record["$key:$suffix"] = htmltotext($record[$key]);
				}
				if(!isset($record["$key:$suffix"])) $record["$key:$suffix"] = '';
			}
			$suffix = getfield("[$key#", ']', $template);
			if(!empty($suffix)) {
				$array_templates["$key#$suffix"] = "[$key#$suffix]";
				$record["$key#$suffix"] = filter($suffix, $record[$key]);
			}
			$suffix = getfield("[$key@", ']', $template);
			if(!empty($suffix)) {
				$array_templates["$key@$suffix"] = "[$key@$suffix]";
				$record["$key@$suffix"] = $record[$key];
				if(function_exists($suffix)) {
					$record["$key@$suffix"] = $suffix($record["$key@$suffix"]);
				}
			}
		}
		$html .= ak_array_replace($array_templates, $record, $template);
		$i ++;
		if(isset($options['colspan']) && $options['colspan'] > 0) {
			if($i % $options['colspan'] == 0 && isset($data[$id + 1])) $html .= $options['overflow'];
		}
	}
	$html = preg_replace( '/\[%?_[\w_-]{1,99}\]/', '', $html);
	if(!empty($options['filter'])) $html = filter($options['filter'], $html);
	return $html;
}

function renderhtml($text, $pagevariables) {
	global $lr, $homepage, $setting_forbidstat, $currenturl;
	if(strpos($text, '<!--filter:') !== false) {
		$a = substr($text, -100);
		preg_match("/<!--filter:([0-9a-z_]+)-->/is", $a, $match);
		if(!empty($match)) {
			$filterid = $match[1];
			$text = filter($filterid, $text);
			$text = str_replace("<!--filter:{$filterid}-->", '', $text);
		}
	}
	if(isset($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) > 4 && substr($_SERVER['REQUEST_URI'], -4) == '.xml') $xml = 1;
	if(!empty($pagevariables['htmlfilename']) && substr($pagevariables['htmlfilename'], -4) == '.xml') $xml = 1;
	if(strlen($text) > 5 && substr($text, 0, 5) == '<?xml') $xml = 1;
	if(empty($setting_forbidstat)) {
		if(strpos($text, '[inc]') === false) {
			$text = preg_replace('/<\/body>/i', "[inc]{$lr}</body>", $text);
		}
	}
	if(!empty($pagevariables['_pageid'])) {
		$id = $pagevariables['_pageid'];
		$type = $pagevariables['_pagetype'];
		$inc = getinc($id, $type);
	} else {
		$inc = '';
	}
	$text = ak_replace('[inc]', $inc, $text);
	$text = ak_replace('[ad]', '', $text);
	$text = ak_replace('[powered]', '', $text);
	$text = ak_replace('[*home*]', $homepage, $text);
	
	$text = ak_replace('[n]', "\n", $text);
	if(substr($text, 0, 17) == '<!--clearspace-->') $text = clearhtml(substr($text, 17));
	return $text;
}

function getinc($id = 0, $type = 'item') {
	global $setting_statcode;
	if($id == 0) return '';
	if($type == 'category') $id = 'c'.$id;
	$template = "<script src='[*home*]akcms_inc.php?i=[id]'></script>";
	if(!empty($setting_statcode)) $template = $setting_statcode;
	$return = str_replace('[id]', $id, $template);
	return $return;
}

function getcopyrightinfo() {
	return "<center class='mininum' style='margin-top:5px;'>Copyleft ? 2007-2019 AKCMS 6.1.2 Finial</center>";
}