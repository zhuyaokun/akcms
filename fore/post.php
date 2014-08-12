<?php
require_once CORE_ROOT.'include/common.inc.php';
if($charset == 'gbk') {
	$_POST = utf8togbk($_POST);
	extract($_POST, EXTR_PREFIX_ALL, 'post');
}
isset($title) || $title = empty($post_title) ? '' : $post_title;
isset($content) || $content = empty($post_content) ? '' : $post_content;
isset($digest) || $digest = empty($post_digest) ? '' : $post_digest;
isset($category) || $category = empty($post_category) ? '' : $post_category;
isset($author) || $author = empty($post_author) ? '' : $post_author;
isset($keywords) || $keywords = empty($post_keywords) ? '' : $post_keywords;
isset($aimurl) || $aimurl = empty($post_aimurl) ? '' : $post_aimurl;
if(trim($title) == '') exit('1');
if(empty($category)) exit('2');
if(empty($allowcategories) && empty($denycategories)) exit('3');
if(!empty($allowcategories) && !in_array($category, $allowcategories)) exit('4');
if(!empty($denycategories) && in_array($category, $denycategories)) exit('4');
require_once CORE_ROOT.'include/fore.inc.php';
if(empty($nocaptcha)) verifycaptcha();
$draft = 1;
if(!empty($nodraft)) $draft = 0;
$value = array(
	'title' => $title,
	'digest' => $digest,
	'category' => $category,
	'dateline' => $thetime,
	'author' => $author,
	'keywords' => $keywords,
	'aimurl' => $aimurl,
	'draft' => $draft
);
$db->insert('items', $value);
$id = $db->insert_id();
$value = array(
	'itemid' => $id,
	'text' => nl2br($content)
);
$db->insert('texts', $value);
echo('0');
?>