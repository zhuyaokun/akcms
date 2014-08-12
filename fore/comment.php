<?php
require_once CORE_ROOT.'include/common.inc.php';
require_once CORE_ROOT.'include/fore.inc.php';
header("Content-Type:text/html;charset=utf-8");
if(empty($setting_commentswitch)) fore404();
if($charset == 'gbk') {
	$_POST = utf8togbk($_POST);
	extract($_POST, EXTR_PREFIX_ALL, 'post');
}
(empty($post_itemid) || !a_is_int($post_itemid)) && exit('1');
empty($post_comment) && aexit('2');

@include(actionhookfile('savecomment'));

if(isset($post_username)) $username = $post_username;

$title = isset($post_title) ? $post_title : '';
$itemid = $post_itemid;
$comment = $post_comment;
if(!$item = $db->get_by('id, category, section', 'items', "id='$itemid'")) aexit('3');
$value = array(
	'itemid' => $itemid,
	'username' => $username,
	'title' => $title,
	'message' => $comment,
	'dateline' => $thetime,
	'category' => $item['category'],
	'section' => $item['section'],
	'ip' => $onlineip
);
$db->insert('comments', $value);
refreshcommentnum($itemid, 1);
if(!empty($setting_ifcommentrehtml)) {
	batchhtml($itemid);
}
aexit('0');
?>