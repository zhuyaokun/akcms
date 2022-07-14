<?php
if(!defined('CORE_ROOT')) @include 'include/directaccess.php';
require CORE_ROOT.'include/admin.inc.php';
require CORE_ROOT.'include/image.func.php';
if(isset($_SERVER['HTTP_CONTENT_DISPOSITION']) && preg_match('/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i',$_SERVER['HTTP_CONTENT_DISPOSITION'], $info)){
	$filename = fromutf8(urldecode($info[2]));
	if(!ispicture($filename)) uploaderror($lan['pictureexterror']);
	$newfilename = get_upload_filename($filename, 0, 0, 'image');
  $a = file_get_contents("php://input");
	if(!file_exists(FORE_ROOT.$newfilename))
  {
   writetofile($a, FORE_ROOT.$newfilename);
   }
} else {
	$filename = $file_filedata['name'];
	if(!ispicture($filename)) uploaderror($lan['pictureexterror']);
	$newfilename = get_upload_filename($filename, 0, 0, 'image');
	uploadfile($file_filedata['tmp_name'], FORE_ROOT.$newfilename);
}
$modules = getcache('modules');
operateuploadpicture(FORE_ROOT.$newfilename, $modules[akgetcookie('lastmoduleid')]);
$picurl = $homepage.$newfilename;
$db->insert('attachments', array('itemid' => $get_id, 'filename' => $newfilename, 'filesize' => filesize(FORE_ROOT.$newfilename), 'dateline' => $thetime, 'originalname' => $filename));
$count = $db->get_by('COUNT(*)', 'attachments', "itemid='$get_id'");
$db->update('items', array('attach' => $count), "id='$get_id'");
$msg = "{'url':'".$picurl."','localname':'".$newfilename."','id':'1'}";
aexit("{'err':'','msg':".$msg."}");

function uploaderror($msg) {
	aexit("{'err':'','msg':".$msg."}");
}
?>