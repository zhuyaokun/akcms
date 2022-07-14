<?php
if(!defined('CORE_ROOT')) @include 'include/directaccess.php';
require CORE_ROOT.'include/admin.inc.php';
require CORE_ROOT.'include/task.file.func.php';
require CORE_ROOT.'include/spider.func.php';
checkcreator();
if(empty($get_action)) {
	$listrules = $contentrules = $contentpagerules = '';
	$query = $db->query_by('*', 'spider_listrules', '1', 'id');
	while($rule = $db->fetch_array($query)) {
		$value = unserialize($rule['value']);
		$listrules .= "<tr><td>{$rule['id']}</td><td><a href='index.php?file=spider&action=editspiderlist&id={$rule['id']}'>{$value['name']}</a></td><td><a href='index.php?file=spider&action=previewspiderlist&id={$rule['id']}'>{$lan['preview']}</a></td><td><a href='index.php?file=spider&action=spiderlist&id={$rule['id']}'>{$lan['spidernow']}</a></td><td><a href='index.php?file=spider&action=exportlistrule&id={$rule['id']}'>{$lan['export']}</a></td><td><a href='index.php?file=spider&action=deletespiderlist&id={$rule['id']}'>{$lan['delete']}</a></td></tr>";
	}
	$query = $db->query_by('*', 'spider_contentrules', '1', 'id');
	while($rule = $db->fetch_array($query)) {
		$value = unserialize($rule['value']);
		$contentrules .= "<tr><td>{$rule['id']}</td><td><a href='index.php?file=spider&action=editspidercontent&id={$rule['id']}'>{$value['name']}</a></td><td><a href='index.php?file=spider&action=previewspidercontent&id={$rule['id']}'>{$lan['preview']}</a></td><td><a href='index.php?file=spider&action=exportcontentrule&id={$rule['id']}'>{$lan['export']}</a></td><td><a href='index.php?file=spider&action=deletespidercontent&id={$rule['id']}'>{$lan['delete']}</a></td></tr>";
	}
	$query = $db->query_by('*', 'spider_contentpagerules', '1', 'id');
	while($rule = $db->fetch_array($query)) {
		$value = unserialize($rule['value']);
		$contentpagerules .= "<tr><td>{$rule['id']}</td><td><a href='index.php?file=spider&action=editspidercontentpage&id={$rule['id']}'>{$value['name']}</a></td><td><a href='index.php?file=spider&action=previewspidercontentpage&id={$rule['id']}'>{$lan['preview']}</a></td><td><a href='index.php?file=spider&action=exportcontentpagerule&id={$rule['id']}'>{$lan['export']}</a></td><td><a href='index.php?file=spider&action=deletespidercontentpage&id={$rule['id']}'>{$lan['delete']}</a></td></tr>";
	}
	$smarty->assign('listrules', $listrules);
	$smarty->assign('contentrules', $contentrules);
	$smarty->assign('contentpagerules', $contentpagerules);
	displaytemplate('admincp_spiders.htm');
} elseif($get_action == 'savenewspidercontent') {
	$db->insert('spider_contentrules', array('value' => serialize(array('name' => $post_name))));
	$id = $db->insert_id();
	adminmsg($lan['operatesuccess'], 'index.php?file=spider&action=editspidercontent&id='.$id);
} elseif($get_action == 'newspidercontentpage') {
	$smarty->assign('ids', array(1, 2, 3, 4, 5));
	displaytemplate('admincp_spidercontentpage.htm');
} elseif($get_action == 'editspidercontent') {
	$rule = $db->get_by('value', 'spider_contentrules', "id='$get_id'");
	$value = unserialize($rule);
	$extnames = $extvalues = array();
	foreach($value as $k => $v) {
		if(substr($k, 0, 7) == 'extname') $extnames[substr($k, 7)] = ak_htmlspecialchars($v);
		if(substr($k, 0, 8) == 'extvalue') $extvalues[substr($k, 8)] = ak_htmlspecialchars($v);
		$smarty->assign($k, ak_htmlspecialchars($v));
		$$k = ak_htmlspecialchars($v);
	}
	$smarty->assign('extnames', $extnames);
	$smarty->assign('extvalues', $extvalues);
	foreach(array('start', 'end', 'spiderpic', 'filter', 'repeat') as $tag) {
		$v = array();
		for($i = 1; $i <= 20; $i ++) {
			if(isset($value[$tag.$i])) $v[$i] = ak_htmlspecialchars($value[$tag.$i]);
		}
		$smarty->assign($tag, $v);
	}
	$pageruleselect = "<option value='0'>{$lan['pleasechoose']}</option>";
	$query = $db->query_by('id,value', 'spider_contentpagerules');
	while($rule = $db->fetch_array($query)) {
		$value = unserialize($rule['value']);
		$pageruleselect .= "<option value='{$rule['id']}'>{$value['name']}</option>";
	}
	$smarty->assign('pageruleselect', $pageruleselect);
	$smarty->assign('id', $get_id);
	$smarty->assign('ids', array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20));
	$saveto1 = $saveto2 = '';
	$fields = array('title', 'shorttitle', 'aimurl', 'dateline', 'filename', 'pageview', 'author', 'source', 'editor', 'digest', 'data', 'keywords', 'picture', 'comment', 'orderby', 'orderby2', 'orderby3', 'orderby4', 'orderby5', 'orderby6', 'orderby7', 'orderby8',);
	foreach($fields as $f) {
		if(!isset($$f)) $$f = '';
		$v = $$f;
		$filter = $f.'_filter';
		if(!isset($$filter)) $$filter = '';
		$filter = $$filter;
		$saveto1 .= "<tr><td>{$lan[$f]}</td><td><input type='text' name='$f' value='{$v}'></td><td><input type='text' name='{$f}_filter' value='$filter' size='5'></td></tr>";
	}
	for($i = 1; $i <= 10; $i ++) {
		$n = "extname".$i;
		$v = "extvalue".$i;
		$f = "extfilter".$i;
		if(!isset($$n)) $$n = '';
		if(!isset($$v)) $$v = '';
		if(!isset($$f)) $$f = '';
		$saveto2 .= "<tr><td>{$i}.<input type='text' name='extname{$i}' size='10' value='{$$n}'></td><td><input type='text' name='extvalue$i' value='{$$v}'></td><td><input type='text' name='extfilter$i' value='{$$f}' size='5'></td></tr>";
	}
	$smarty->assign('saveto1', $saveto1);
	$smarty->assign('saveto2', $saveto2);
	displaytemplate('admincp_spidercontent.htm');
} elseif($get_action == 'editspidercontentpage') {
	$rule = $db->get_by('value', 'spider_contentpagerules', "id='$get_id'");
	$value = unserialize($rule);
	foreach($value as $k => $v) {
		$smarty->assign($k, ak_htmlspecialchars($v));
	}
	$smarty->assign('id', $get_id);
	$smarty->assign('ids', array(1, 2, 3, 4, 5));
	foreach(array('start', 'end', 'spiderpic', 'filter', 'repeat') as $tag) {
		$v = array();
		for($i = 1; $i <= 5; $i ++) {
			if(isset($value[$tag.$i])) $v[$i] = ak_htmlspecialchars($value[$tag.$i]);
		}
		$smarty->assign($tag, $v);
	}
	displaytemplate('admincp_spidercontentpage.htm');
} elseif($get_action == 'savespidercontent') {
	$id = $post_id;
	$value = array('value' => serialize($_POST));
	if(!empty($post_id)) {
		$db->update('spider_contentrules', $value, "id='$id'");
	} else {
		$db->insert('spider_contentrules', $value);
		$id = $db->insert_id();
		$_POST['id'] = $id;
	}
	setcache('spidercontentrule'.$id, $_POST);
	adminmsg($lan['operatesuccess'], 'index.php?file=spider&action=editspidercontent&id='.$id);
} elseif($get_action == 'savespidercontentpage') {
	$id = $post_id;
	$value = array('value' => serialize($_POST));
	if(!empty($post_id)) {
		$db->update('spider_contentpagerules', $value, "id='$id'");
	} else {
		$db->insert('spider_contentpagerules', $value);
		$id = $db->insert_id();
		$_POST['id'] = $id;
	}
	setcache('spidercontentpagerule'.$id, $_POST);
	adminmsg($lan['operatesuccess'], 'index.php?file=spider&action=editspidercontentpage&id='.$id);
} elseif($get_action == 'newspiderlist') {
	$query = $db->query_by('id,value', 'spider_contentrules');
	$select = '';
	while($rule = $db->fetch_array($query)) {
		$value = unserialize($rule['value']);
		$select .= "<option value='{$rule['id']}'>{$value['name']}</option>";
	}
	$smarty->assign('select', $select);
	displaytemplate('admincp_spiderlist.htm');
} elseif($get_action == 'editspiderlist') {
	$rule = $db->get_by('value', 'spider_listrules', "id='$get_id'");
	$value = unserialize($rule);
	foreach($value as $k => $v) {
		$smarty->assign($k, ak_htmlspecialchars($v));
	}
	$smarty->assign('id', $get_id);
	$select = '';
	$query = $db->query_by('id,value', 'spider_contentrules');
	while($rule = $db->fetch_array($query)) {
		$value = unserialize($rule['value']);
		$select .= "<option value='{$rule['id']}'>{$value['name']}</option>";
	}
	$smarty->assign('select', $select);
	displaytemplate('admincp_spiderlist.htm');
} elseif($get_action == 'savespiderlist') {
	$id = $post_id;
	$value = array('value' => serialize($_POST));
	if(!empty($post_id)) {
		$db->update('spider_listrules', $value, "id='$id'");
	} else {
		$db->insert('spider_listrules', $value);
		$id = $db->insert_id();
		$_POST['id'] = $id;
	}
	setcache('spiderlistrule'.$id, $_POST);
	adminmsg($lan['operatesuccess'], 'index.php?file=spider&action=editspiderlist&id='.$id);
} elseif($get_action == 'previewspidercontent') {
	$rule = getcache('spidercontentrule'.$get_id);
	$result = spiderurl($rule);
	debug($result);
} elseif($get_action == 'previewspidercontentpage') {
	$rule = getcache('spidercontentpagerule'.$get_id);
	$result = spiderurlpage($rule, $rule['url']);
	debug($result);
} elseif($get_action == 'previewspiderlist') {
	$rule = getcache('spiderlistrule'.$get_id);
	$result = spiderlist($rule, 0);
	debug($result);
} elseif($get_action == 'spiderlist') {
	$rule = getcache('spiderlistrule'.$get_id);
	clearspidertask();
	$result = spiderlist($rule, 1);
	header('location:index.php?file=spider&action=spider&r='.random(6));
} elseif($get_action == 'deletespidercontent') {
	$db->delete('spider_contentrules', "id='$get_id'");
	adminmsg($lan['operatesuccess'], 'index.php?file=spider');
} elseif($get_action == 'deletespidercontentpage') {
	$db->delete('spider_contentpagerules', "id='$get_id'");
	adminmsg($lan['operatesuccess'], 'index.php?file=spider');
} elseif($get_action == 'deletespiderlist') {
	$db->delete('spider_listrules', "id='$get_id'");
	adminmsg($lan['operatesuccess'], 'index.php?file=spider');
} elseif($get_action == 'spider') {
	$result = spider();
	if($result === false) debug('finished!', 1);
	debug($result);
	refreshself(1000);
} elseif($get_action == 'spiderpage') {
	if(!empty($post_url)) {
		$listrule = getcache('spiderlistrule'.$post_rule);
		$contentrule = getcache('spidercontentrule'.$listrule['rule']);
		$contentrule['finish'] = 1;
		$task = array(
			'url' => $post_url,
			'listrule' => $listrule
		);
		$result = spiderurl($contentrule, $task);
		aksetcookie('spiderpagerule', $post_rule);
		if($result === false) {
			adminmsg($lan['spidererror']);
		} else {
			$task = array(
				'itemid' => 0,
				'url' => $post_url,
				'list' => $post_rule,
				'norecord' => 1
			);
			$id = insertspidereddata($result, $listrule, $task);
			batchhtml($id);
			header('location:index.php?file=admincp&action=edititem&id='.$id);
		}
	} else {
		$query = $db->query_by('id,value', 'spider_listrules');
		$select = '';
		while($rule = $db->fetch_array($query)) {
			$value = unserialize($rule['value']);
			$select .= "<option value='{$rule['id']}'>{$value['name']}</option>";
		}
		$smarty->assign('lastrule', akgetcookie('spiderpagerule'));
		$smarty->assign('select', $select);
		displaytemplate('admincp_spiderpage.htm');
	}
} elseif($get_action == 'exportcontentrule') {
	$rule = getcache('spidercontentrule'.$get_id);
	unset($rule['id']);
	$smarty->assign('data', base64_encode(serialize($rule)));
	$smarty->assign('actiontitle', $lan['export']);
	displaytemplate('admincp_importexport.htm');
} elseif($get_action == 'exportcontentpagerule') {
	$rule = getcache('spidercontentpagerule'.$get_id);
	unset($rule['id']);
	$smarty->assign('data', base64_encode(serialize($rule)));
	$smarty->assign('actiontitle', $lan['export']);
	displaytemplate('admincp_importexport.htm');
} elseif($get_action == 'exportlistrule') {
	$rule = getcache('spiderlistrule'.$get_id);
	unset($rule['id']);
	$smarty->assign('data', base64_encode(serialize($rule)));
	$smarty->assign('actiontitle', $lan['export']);
	displaytemplate('admincp_importexport.htm');
} elseif($get_action == 'importcontentrule') {
	if(empty($_POST)) {
		$smarty->assign('actiontitle', $lan['import']);
		$smarty->assign('action', 'index.php?file=spider&action=importcontentrule');
		displaytemplate('admincp_importexport.htm');
	} else {
		$value = base64_decode($post_data);
		if($value === false) debug('error', 1);
		$insertvalue = array(
			'value' => $value
		);
		$db->insert('spider_contentrules', $insertvalue);
		$id = $db->insert_id();
		setcache('spidercontentrule'.$id, unserialize($value));
		adminmsg($lan['operatesuccess'], 'index.php?file=spider&action=editspidercontent&id='.$id);
	}
} elseif($get_action == 'importlistrule') {
	if(empty($_POST)) {
		$smarty->assign('actiontitle', $lan['import']);
		$smarty->assign('action', 'index.php?file=spider&action=importlistrule');
		displaytemplate('admincp_importexport.htm');
	} else {
		$value = base64_decode($post_data);
		if($value === false) debug('error', 1);
		$insertvalue = array(
			'value' => $value
		);
		$db->insert('spider_listrules', $insertvalue);
		$id = $db->insert_id();
		setcache('spiderlistrule'.$id, unserialize($value));
		adminmsg($lan['operatesuccess'], 'index.php?file=spider&action=editspiderlist&id='.$id);
	}
}
runinfo();
aexit();
?>