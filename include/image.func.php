<?php
if(!defined('CORE_ROOT')) exit;
function addwatermark($source, $sourcesize = array()) {
	global $setting_attachwatermarkposition;
	if(file_exists(AK_ROOT.'configs/images/watermark.png')) {
		$watermarkfile = AK_ROOT.'configs/images/watermark.png';
	} elseif(file_exists(AK_ROOT.'configs/images/watermark.gif')) {
		$watermarkfile = AK_ROOT.'configs/images/watermark.gif';
	} else {
		return $source;
	}
	$position = $setting_attachwatermarkposition;
	$position == 0 && $position = rand(1, 9);
	$watermarkinfo = getimagesize($watermarkfile);
	list($s_w, $s_h) = $sourcesize;
	list($w_w, $w_h) = $watermarkinfo;
	if($w_w > $s_w || $w_h > $s_h) return $source;
	switch($position) {
		case 1:
			$x = +5;
			$y = +5;
			break;
		case 2:
			$x = ($s_w - $w_w) / 2;
			$y = +5;
			break;
		case 3:
			$x = $s_w - $w_w - 5;
			$y = +5;
			break;
		case 4:
			$x = +5;
			$y = ($s_h - $w_h) / 2;
			break;
		case 5:
			$x = ($s_w - $w_w) / 2;
			$y = ($s_h - $w_h) / 2;
			break;
		case 6:
			$x = $s_w - $w_w - 5;
			$y = ($s_h - $w_h) / 2;
			break;
		case 7:
			$x = +5;
			$y = $s_h - $w_h - 5;
			break;
		case 8:
			$x = ($s_w - $w_w) / 2;
			$y = $s_h - $w_h - 5;
			break;
		case 9:
			$x = $s_w - $w_w - 5;
			$y = $s_h - $w_h - 5;
			break;
	}
	if(substr($watermarkfile, -4) == '.png') {
		$watermark = imageCreateFrompng($watermarkfile);
	} else {
		$watermark = imageCreateFromGIF($watermarkfile);
	}
	imagecopy($source, $watermark, $x, $y, 0, 0, $w_w, $w_h);
	return $source;
}

function corecaptcha($captcha) {
	$width = 38;
	$height = 15;
	$im = imagecreate($width, $height);

	$bg = imagecolorallocate($im, 255, 255, 255);
	$textcolor = imagecolorallocate($im, 0, 0, 255);
	for($i = 0; $i < $width; $i ++) {
		for($j = 0; $j < $height; $j ++) {
			$bgcrumb = imagecolorallocate($im, rand(200,255), rand(200,255), rand(200,255));
			if(empty($bgcrumb) || $bgcrumb == -1) $bgcrumb = rand(2, 255);
			imagefilledrectangle($im, $i, $j, $i + 1, $j + 1, $bgcrumb);
		}
	}
	imagestring($im, 5, 2, 0, $captcha, $textcolor);
	header("Content-type: image/png");
	imagepng($im);
}

function reducepicture($sourceimg, $sourcesize, $size) {
	list($sw, $sh) = $sourcesize;
	if($size >= $sw) return $sourceimg;
	$tw = $size;
	$th = floor($tw * $sh / $sw);
	$targetimg = imagecreatetruecolor($tw, $th);
	imagecopyresampled($targetimg, $sourceimg, 0, 0, 0, 0, $tw, $th, $sw, $sh);
	return $targetimg;
}

function operateuploadpicture($source, $module) {
	global $setting_attachimagequality, $setting_attachwatermarkposition;
	if(!file_exists($source) || filesize($source) < 43) return false;
	$sourcesize = getimagesize($source);
	if($sourcesize['mime'] == 'image/gif') {
		$fp = fopen($source, 'r');
		$head = fread($fp, 11);
		fclose($fp);
		if($head == 'NETSCAPE2.0') return false;
		unset($head);
	}
	list($sw, $sh) = $sourcesize;
	$sourceimg = imagecreatefromfile($source, $sourcesize);
	if(!empty($module['data']['picturemaxsize']) && $module['data']['picturemaxsize'] < $sw) {
		$sourceimg = reducepicture($sourceimg, $sourcesize, $module['data']['picturemaxsize']);
		$tw = $module['data']['picturemaxsize'];
		$th = floor($tw * $sh / $sw);
		$sourcesize[0] = $tw;
		$sourcesize[1] = $th;
	}
	if($setting_attachwatermarkposition != -1) {
		$sourceimg = addwatermark($sourceimg, $sourcesize);
	}
	imagejpeg($sourceimg, $source, $setting_attachimagequality);
}

function setimagequality($source, $quality) {
	$sourceimg = imagecreatefromfile($source);
	imagejpeg($sourceimg, $source, $quality);
}

function imagecreatefromfile($sourcefile, $sourceinfo = array()) {
	if(empty($sourceinfo)) $sourceinfo = getimagesize($sourcefile);
	switch($sourceinfo['mime']) {
		case 'image/jpeg':
			$source = imageCreateFromJPEG($sourcefile);
			break;
		case 'image/gif':
			$gifdata = readfromfile($sourcefile);
			if(strpos($gifdata, 'NETSCAPE2.0') !== false) return false;
			$source = imageCreateFromGIF($sourcefile);
			break;
		case 'image/png':
			$source = imageCreateFromPNG($sourcefile);
			break;
		default:
			return false;
	}
	return $source;
}

function getthumbofpicture($picture, $tw, $th = 'auto') {
	global $setting_attachimagequality, $setting_thumbmethod, $homepage;
	$md5 = ak_md5($picture, 1);
	$thumbmethod = str_replace('[size]', $tw.'x'.$th, $setting_thumbmethod);
	$thumbmethod = str_replace('[hash1]', substr($md5, 0, 1), $thumbmethod);
	$thumbmethod = str_replace('[hash2]', substr($md5, 1, 1), $thumbmethod);
	$thumbmethod = str_replace('[hash3]', substr($md5, 2, 1), $thumbmethod);
	$thumb = $thumbmethod.$md5.'.'.fileext($picture);
	if(file_exists(FORE_ROOT.$thumb)) return cdnurl($thumb);
	if(substr($picture, 0, 7) == 'http://') {
		$_p = readfromurl($picture);
		$picture = AK_ROOT.'cache/'.md5($picture).'-';
		writetofile($_p, $picture);
	} else {
		$picture = FORE_ROOT.$picture;
		if(!file_exists($picture) || filesize($picture) < 43) return false;
	}
	$info = getimagesize($picture);
	if($info === false) return false;
	$sw = $info[0];
	$sh = $info[1];
	$sourceimg = imagecreatefromfile($picture, $info);
	if(substr($picture, -1) == '-') unlink($picture);
	if($th == 'auto') {
		$th = ceil($sh * $tw / $sw);
	}
	$targetimg = imagecreatetruecolor($tw, $th);
	$r1 = $sw / $sh;
	$r2 = $tw / $th;
	if($r1 > $r2) {
		$sw2 = $sh * $tw / $th;
		$l1 = 0.5 * ($sw - $sw2);
		$t1 = 0;
		$sw = $sw2;
	} elseif($r2 > $r1) {
		$sh2 = $sw * $th / $tw;
		$l1 = 0;
		$t1 = 0.5 * ($sh - $sh2) * 0.382;
		$sh = $sh2;
	} else {
		$l1 = 0;
		$t1 = 0;
	}
	imagecopyresampled($targetimg, $sourceimg, 0, 0, $l1, $t1, $tw, $th, $sw, $sh);
	ak_touch(FORE_ROOT.$thumb);
	imagejpeg($targetimg, FORE_ROOT.$thumb, $setting_attachimagequality);
	touchcdn($homepage.$thumb);
	return cdnurl($thumb);
}

function cdnurl($url) {
	global $setting_cdnpath, $homepage;
	if(empty($setting_cdnpath)) return $homepage.$url;
	return $setting_cdnpath.$url;
}

function touchcdn($url) {
	global $setting_cdn;
	if(empty($setting_cdn)) return true;
	$url = $setting_cdn.'?url='.$url;
	$result = readfromurl($url);
	return($result);
}
?>