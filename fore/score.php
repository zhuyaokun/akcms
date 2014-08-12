<?php
require_once CORE_ROOT.'include/common.inc.php';
(!isset($post_itemid) || !a_is_int($post_itemid)) && exit('1');
(!isset($post_score) || !a_is_int($post_score)) && exit('2');
$itemid = $post_itemid;
$score = $post_score;
if(!isset($maxscore)) $maxscore = 100;
if(!isset($minscore)) $minscore = -100;
if($score > $maxscore || $score < $minscore) exit('3');
require_once CORE_ROOT.'include/fore.inc.php';
if(empty($nocaptcha)) verifycaptcha();
if(!$item = $db->get_by('id', 'items', "id=$itemid")) aexit('4');
$value = array(
	'itemid' => $itemid,
	'score' => $score,
	'dateline' => $thetime,
	'ip' => $onlineip
);
$db->insert('scores', $value);
updateitemscore($itemid);
echo '0';
?>