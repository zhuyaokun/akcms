<?php
if(!defined('CORE_ROOT')) @include 'include/directaccess.php';
require CORE_ROOT.'include/admin.inc.php';
require CORE_ROOT.'include/se.func.php';
checkcreator();
if(empty($get_action)) {
	$query = $db->query_by('*', 'ses', '1', 'id');
	$seslist = '';
	while($se = $db->fetch_array($query)) {
		$value = ak_unserialize($se['value']);
		$updatetime = '-';
		if(!empty($value['lastupdate'])) $updatetime = date('Y-m-d', $value['lastupdate']);
		$keywordsnum = 0;
		if(!empty($value['keywordsnum'])) $keywordsnum = $value['keywordsnum'];
		$seslist .= "<tr><td>{$se['id']}</td><td><a href='index.php?file=se&action=se&id={$se['id']}'>{$se['name']}</a></td><td id='index_{$se['id']}' class='tdnum'>-</td><td id='unindexed_{$se['id']}' class='tdnum'>-</td><td>{$updatetime}</td><td><a href='index.php?file=se&action=keywords&sid={$se['id']}'>{$lan['words']}($keywordsnum)</a></td><td><a href='index.php?file=se&action=updateindex&id={$se['id']}'>{$lan['updateindex']}</a></td><td><a href='index.php?file=se&action=updateindex&id={$se['id']}&rebuild=1'>{$lan['rebuildindex']}</a></td><td>";
		if($value['ifcreatehtml']) $seslist.="<a href='index.php?file=se&action=createhtmlforkeywords&id={$se['id']}'>{$lan['createhtmlforkeywords']}</a>";
		$seslist .= "</td><td><a href='index.php?file=se&action=del&id={$se['id']}'>{$lan['del']}</a></td></tr>";
		$seslist .= "<script>$(document).ready(function(){indexnum({$se['id']})})</script>";
	}
	$smarty->assign('seslist', $seslist);
	displaytemplate('ses.htm');
} elseif($get_action == 'se' && !empty($get_id)) {
	$select_templates = get_select_templates();
	$se = $db->get_by('*', 'ses', "id='$get_id'");
	$smarty->assign('id', $get_id);
	$smarty->assign('name', $se['name']);
	$value = ak_unserialize($se['value']);
	foreach($value as $k => $v) {
		$smarty->assign($k, $v);
	}
	$smarty->assign('select_templates', $select_templates);
	displaytemplate('se.htm');
} elseif($get_action == 'add') {
	$select_templates = get_select_templates();
	$smarty->assign('field', 'title,digest,text');
	$smarty->assign('where', 'category=1');
	$smarty->assign('path', random(6).'/');
	$smarty->assign('ifcreatehtml', '0');
	$smarty->assign('select_templates', $select_templates);
	displaytemplate('se.htm');
} elseif($get_action == 'del') {
	$db->delete('ses', "id='$get_id'");
	adminmsg($lan['operatesuccess'], 'index.php?file=se');
} elseif($get_action == 'save') {
	if(empty($post_name)) adminmsg($lan['senamemustoffer'], 'back', 3, 1);
	$value = array(
		'field' => $post_field,
		'where' => $post_where,
		'orderby' => $post_orderby,
		'path' => $post_path,
		'separator' => $post_separator,
		'storemethod' => $post_storemethod,
		'template' => $post_template,
		'ifcreatehtml' => $post_ifcreatehtml
	);
	if(!empty($post_id) && $row = $db->get_by('value', 'ses', "id='$post_id'")) {
		$original = ak_unserialize($row);
		foreach($original as $k => $v) {
			if(isset($value[$k])) continue;
			$value[$k] = $v;
		}
	}
	$value = array(
		'name' => $post_name,
		'value' => serialize($value)
	);
	if(empty($post_id)) {
		$db->insert('ses', $value);
	} else {
		$db->update('ses', $value, "id='$post_id'");
	}
	updatecache('ses');
	adminmsg($lan['operatesuccess'], 'index.php?file=se');
} elseif($get_action == 'updateindex') {
	require_once(CORE_ROOT.'include/task.file.func.php');
	$ses = getcache('ses');
	$se = $ses[$get_id];
	if(empty($get_process)) {
		if(empty($get_start)) {
			$smarty->assign('id', $get_id);
			$smarty->assign('rebuild', isset($get_rebuild));
			displaytemplate('admincp_seindex.htm');
		} else {
			preparesetask($get_id, $get_rebuild);
			header('location:index.php?file=se&action=updateindex&frame=1&process=1&id='.$get_id.'&timeout='.$get_timeout.'&per='.$get_per);
		}
	} elseif(!empty($get_frame)) {
		$timeout = 50;
		$per = 1000;
		if(!empty($get_timeout)) $timeout = $get_timeout;
		if(!empty($get_per)) $per = $get_per;
		showprocess($lan['createindex'], 'index.php?file=se&action=updateindex&id='.$get_id.'&process=1&per='.$per, '', $timeout);
	} else {
		$parent = operatesetask($get_id, $get_per);
		aexit($parent."\t\t");
	}
} elseif($get_action == 'indexnum') {
	$ses = getcache('ses');
	if(empty($ses[$get_id])) exit;
	$se = $ses[$get_id];
	$where = $se['data']['where'];
	$num = $db->get_by('COUNT(*) as c', 'items', $where);
	updatesedata($get_id, array('itemnum' => $num));
	echo($get_id.'#'.$num);
	$lastupdate = 0;
	if(!empty($se['data']['lastupdate'])) $lastupdate = $se['data']['lastupdate'];
	$num = $db->get_by('COUNT(*) as c', 'items', "(dateline>$lastupdate OR lastupdate>$lastupdate) AND ".$where);
	echo('#'.$num);
	aexit();
} elseif($get_action == 'searchresult') {
	$result = readindex($get_sid, array($get_keyword));
	debug($result);
} elseif($get_action == 'keywords') {
	$sql_condition = $url_condition = '';
	if(empty($get_sid)) exit('error');
	if(!empty($get_key)) {
		$sql_condition .= " AND keyword LIKE '%{$get_key}%'";
		$url_condition .= "&key={$get_key}";
	}
	$sql_condition .= " AND sid='$get_sid'";
	$url_condition .= "&sid={$get_sid}";
	$ipp = 10;
	$page = 1;
	isset($get_page) && $page = $get_page;
	isset($post_page) && $page = $post_page;
	!a_is_int($page) && $page = 1;
	$orderby = 'num';
	$start_id = ($page - 1) * $ipp;
	$url = 'index.php?file=se&action=keywords'.ak_htmlspecialchars($url_condition);
	$sql_condition = '1'.$sql_condition;
	$count = $db->get_by('COUNT(*)', 'keywords', $sql_condition);
	$str_index = multi($count, $ipp, $page, $url);
	$smarty->assign('str_index', $str_index);
	$query = $db->query_by('*', 'keywords', $sql_condition, " `$orderby` DESC", "$start_id,$ipp");
	$str_items = '';
	$se = getsedata($get_sid);
	while($k = $db->fetch_array($query)) {
		extract($k);
		$k2 = urlencode($keyword);
		$url = keywordurl($k, $se);
		$str_items .= "<tr><td><input type='checkbox' name='batch[]' value='$id'>$id</td><td>$keyword</td><td>{$k['initial']}</td><td><a href='http://www.baidu.com/s?wd=$k2' target='_blank'>Baidu</a> <a href='http://www.google.com.hk/search?q=$k2' target='_blank'>Google</a> <a href='http://www.soso.com/q?w=$k2' target='_blank'>Soso</a> <a href='http://www.sogou.com/web?query=$k2' target='_blank'>Sogou</a></td><td><a href='index.php?file=se&action=searchresult&sid=$get_sid&keyword=$k2'>$num</a></td><td>$searchcount</td><td align='center'><a href='index.php?file=se&action=createhtmlforkeyword&id=$get_sid&keyword=$k2'>{$lan['createhtml']}</a></td><td><a href='$url' target='_blank'>$keyword</a></td><td><a href='index.php?file=se&action=deletekeywords&sid=$get_sid&id={$id}&vc=$vc' onclick='return confirmdelete()'>".alert($lan['delete'])."</a></td></tr>";
	}
	if($str_items == '') $str_items = "<tr><td colspan='9'>{$lan['nokeywordexists']}</td></tr>";
	$smarty->assign('sid', $get_sid);
	$smarty->assign('str_items', $str_items);
	displaytemplate('keywords.htm');
} elseif($get_action == 'deletekeywords') {
	vc();
	$db->delete('keywords', "id=$get_id");
	refreshkeywordsnum($get_sid);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'addkeyword') {
	vc();
	$k = $get_keyword;
	$kr = $db->get_by('*', 'keywords', "sid='$get_sid' AND keyword='$k'");
	if(!empty($kr)) adminmsg($lan['keywordexists'], 'back');
	$initial = getinitial($k);
	$db->insert('keywords', array('keyword' => $k, 'sid' => $get_sid, 'initial' => $initial));
	refreshkeywordsnum($get_sid);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'createhtmlforkeyword') {
	$id = $get_id;
	$keyword = $get_keyword;
	$se = $db->get_by('*', 'ses', "id='$id'");
	$value = ak_unserialize($se['value']);
	$keyword = $db->get_by('*', 'keywords', "sid=$id AND keyword='$keyword'");
	$keyword['template'] = $value['template'];
	$keyword['html'] = $value['ifcreatehtml'];
	$variable = array(
		'id' => $keyword['id'],
		'hash' => $keyword['hash'],
		'keyword' => $keyword['keyword']
	);
	$keyword['htmlfilename'] = FORE_ROOT.calstoremethod($value['storemethod'], $variable);
	$keyword['keyword_url'] = urlencode($keyword['keyword']);
	$keyword['keyword_html'] = ak_htmlspecialchars($keyword['keyword']);
	$keyword['html'] = 1;
	render_template($keyword, '', 1);
	adminmsg($lan['operatesuccess'], 'back');
} elseif($get_action == 'createhtmlforkeywords') {
	if(empty($get_id)) aexit('error');
	require_once(CORE_ROOT.'include/task.file.func.php');
	$taskkey = 'createhtmlforkeywords'.$get_id;
	if(isset($get_frame)) {
		$timeout = 10;
		showprocess($lan['createhtml'], 'index.php?file=se&action=createhtmlforkeywords&id='.$get_id.'&process=1', '', $timeout);
	} elseif(isset($get_process)) {
		$task = gettask($taskkey);
		if($task === false) aexit("100\t\t");
		$task['html'] = 1;
		render_template($task, '', 1);
		$db->update('keywords', array('latesthtml' => $thetime), "id='{$task['id']}'");
		$percent = gettaskpercent($taskkey);
		aexit($percent."\t\t");
	} else {
		$se = $db->get_by('*', 'ses', "id='$get_id'");
		$value = ak_unserialize($se['value']);
		$query = $db->query_by('*', 'keywords', "sid=$get_id", 'id');
		while($keyword = $db->fetch_array($query)) {
			$keyword['template'] = $value['template'];
			$keyword['html'] = $value['ifcreatehtml'];
			$variable = array(
				'id' => $keyword['id'],
				'hash' => $keyword['hash'],
				'keyword' => $keyword['keyword']
			);
			$keyword['htmlfilename'] = FORE_ROOT.calstoremethod($value['storemethod'], $variable);
			$keyword['keyword_url'] = urlencode($keyword['keyword']);
			$keyword['keyword_html'] = ak_htmlspecialchars($keyword['keyword']);
			addtask($taskkey, $keyword);
		}
		header('location:index.php?file=se&action=createhtmlforkeywords&frame=1&process=1&id='.$get_id);
	}
} elseif($get_action == 'batchkeywords') {
	if($post_batchtype == 'delete') {
		$ids = implode(',', $post_batch);
		$db->delete('keywords', "id IN ($ids)");
		refreshkeywordsnum($post_sid);
	} elseif($post_batchtype == 'createhtml') {
		require_once(CORE_ROOT.'include/task.file.func.php');
		$taskkey = 'createhtmlforkeywords'.$post_sid;
		$se = $db->get_by('*', 'ses', "id='$post_sid'");
		$value = ak_unserialize($se['value']);
		$ids = implode(',', $post_batch);
		$query = $db->query_by('*', 'keywords', "id IN ($ids)", 'id');
		while($keyword = $db->fetch_array($query)) {
			$keyword['template'] = $value['template'];
			$keyword['html'] = $value['ifcreatehtml'];
			$variable = array(
				'id' => $keyword['id'],
				'hash' => $keyword['hash'],
				'keyword' => $keyword['keyword']
			);
			$keyword['htmlfilename'] = FORE_ROOT.calstoremethod($value['storemethod'], $variable);
			$keyword['keyword_url'] = urlencode($keyword['keyword']);
			$keyword['keyword_html'] = ak_htmlspecialchars($keyword['keyword']);
			addtask($taskkey, $keyword);
		}
		header('location:index.php?file=se&action=createhtmlforkeywords&frame=1&process=1&id='.$post_sid);
	}
	adminmsg($lan['operatesuccess'], 'back');
}
runinfo();
aexit();
?>