<?php
if(!defined('CORE_ROOT')) exit;
function spider() {
	global $db;
	if($task = gettask('spiderpicture')) {
		list($url, $filename) = explode("\t", $task);
		$picturedata = readfromurl($url);
		writetofile($picturedata, $filename);
		require_once(CORE_ROOT.'include/image.func.php');
		operateuploadpicture($filename);
		$return = $url;
	} elseif($task = gettask('spideritempage')) {
		$rule = getcache('spidercontentpagerule'.$task['rule']);
		$result = spiderurlpage($rule, $task['url'], $task['itemid']);
		$maxpage = getmaxpage($task['itemid']);
		$result['itemid'] = $task['itemid'];
		$result['page'] = $maxpage + 1;
		$db->insert('texts', $result);
		$db->update('items', array('pagenum' => '+1'), "id='{$task['itemid']}'");
		if(isset($task['createhtml'])) batchhtml($task['itemid']);
		$return = $task['url'];
	} elseif($task = gettask('spideritem')) {
		$contentrule = getcache('spidercontentrule'.$task['contentrule']);
		$listrule = getcache('spiderlistrule'.$task['listrule']);
		$result = spiderurl($contentrule, $task, $listrule);
		if(!empty($result)) {
			$return = $task['url'].' OK';
			$id = insertspidereddata($result, $listrule, $task);
			if(!empty($result['pageurls'])) {
				foreach($result['pageurls'] as $k => $r) {
					$task = array(
						'url' => $r['url'],
						'rule' => $contentrule['pagerule'],
						'itemid' => $id,
						'title' => $r['title']
					);
					if($k + 1 == count($result['pageurls'])) $task['createhtml'] = 1;
					addtask('spideritempage', $task);
				}
			} else {
				batchhtml($id);
			}
			debug($id.','.$result['title']);
		} else {
			$return = $task['url'].' skip';
		}
	} elseif($task = gettask('spiderlist')) {
		$rule = $task['rule'];
		$write = $task['write'];
		$url = $task['url'];
		spiderlist($rule, $write, $url);
		$return = $url;
	}
	if(isset($return)) return $return;
	return false;
}

function spiderlist($rule, $write = 1, $listurl = '') {
	global $db, $lan;
	$item_urls = $us = array();
	$u = $listurl;
	if($u == '') $u = $rule['listurl'];
	$us[] = $u;
	if(strpos($u, '(*)') !== false && isset($rule['startid']) && isset($rule['endid'])) {
		$us = array();
		$step = 1;
		if(!empty($rule['step'])) $step = $rule['step'];
		if(is_numeric($rule['startid'])) {
			for($i = $rule['startid']; $i <= $rule['endid']; $i += $step) {
				$i = str_pad($i, strlen($rule['startid']), '0', STR_PAD_LEFT);
				$us[] = str_replace('(*)', $i, $u);
			}
		} else {
			for($i = ord($rule['startid']); $i <= ord($rule['endid']); $i += $step) {
				$us[] = str_replace('(*)', chr($i), $u);
			}
		}
	}
	$url = array_shift($us);
	$text = readfromurl($url, 1);
	debug($url.$lan['downloaded'].strlen($text));
	$text = str_replace("\r", '', $text);
	$text = filter($rule['filter'], $text);
	$text = getfield($rule['start'], $rule['end'], $text, '###');
	$texts = explode('###', $text);
	foreach($texts as $text) {
		$links = parselinks($text);
		$urls = array();
		foreach($links as $link => $title) {
			$link = calrealurl($link, $url);
			$link = filter($rule['urlfilter'], $link);
			$title = filter($rule['titlefilter'], $title);
			if(empty($link)) continue;
			if($title === false) continue;
			if(!in_array($link, $urls)) {
				$urls[] = $link;
				$html = '';
				if(!empty($rule['appendloophtml'])) $html = $text;
				$item_urls[] = array(
					'url' => $link,
					'title' => $title,
					'html' => $html
				);
			}
		}
	}
	foreach($item_urls as $key => $_item) {
		$_t = array('url' => $_item['url'], 'listrule' => $rule['id']);
		if($catched = ifcatched($_t)) {
			if($rule['update'] == 0) {
				unset($item_urls[$key]);
			} else {
				$itemid[$_item['url']] = $catched;
			}
		}
	}
	$item_urls = array_reverse($item_urls);
	$hookfunction = "hook_spidelist_{$rule['id']}";
	if(function_exists($hookfunction)) $item_urls = $hookfunction($item_urls);
	if(!empty($write)) {
		foreach($item_urls as $item) {
			$task = $item;
			$task['listrule'] = $rule['id'];
			$task['contentrule'] = $rule['rule'];
			if(!empty($itemid[$item['url']])) $task['itemid'] = $itemid[$item['url']];
			addtask('spideritem', $task);
		}
		if(!empty($us)) {
			foreach($us as $u) {
				$task = array(
					'rule' => $rule,
					'write' => $write,
					'url' => $u
				);
				addtask('spiderlist', $task);
			}
		}
	}
	return $item_urls;
}

function spiderurl($rule, $task = array(), $listrule = array()) {
	global $db, $charset;
	if(isset($rule['url'])) $url = $rule['url'];
	if(isset($task['url'])) $url = $task['url'];
	if(!isset($url)) return false;
	$return = array();
	$html = $linktext = $append_html = '';
	if(isset($task['title'])) $linktext = $task['title'];
	if(isset($task['html'])) $append_html = $task['html'];
	if(!empty($url) && substr($url, 0, 1) != '#') {
		$html = readfromurl($url, 1);
		if($html == '') return false;
		debug($url.' downloaded!');
	}
	$content = "<url:{$url}>\n<title:{$linktext}>\n".$html.$append_html;
	$content = str_replace("\r", '', $content);
	if(!empty($rule['htmlfilter'])) $content = filter($rule['htmlfilter'], $content);
	$array_replace = array('[linktext]');
	$array_to = array($linktext);
	$array_replace[] = '[itemid]';
	$array_to[] = isset($task['itemid']) ? $task['itemid'] : 0;
	for($i = 1; $i <= 20; $i ++) {
		$field_start = $rule["start{$i}"];
		$field_end = $rule["end{$i}"];
		$field_start = str_replace('[n]', "\n", $field_start);
		$field_end = str_replace('[n]', "\n", $field_end);
		$repeat = $rule["repeat{$i}"];
		if(!empty($field_start) && !empty($field_end)) {
			$field[$i] = getfield($field_start, $field_end, $content, empty($rule['repeat'.$i]) ? '' : '<!--akcmsspidersplit-->');
			$array_replace[] = "[field{$i}]";
			if($field[$i] === false) {
				$array_to[] = '';
			} else {
				empty($listrule) ? $category = 0 : $category = $listrule['category'];
				$config = array(
					'itemurl' => $url,
					'spiderpic' => !empty($rule['spiderpic'.$i]),
					'repeat' => $rule['repeat'.$i],
					'filter' => $rule['filter'.$i],
					'category' => $category
				);
				$spiderfield = calspiderfield($field[$i], $config, $url, !empty($rule['finish']));
				$array_to[] = $spiderfield;
			}
		}
	}
	if(!empty($rule['skipwhere'])) {
		$skipwhere = ak_replace($array_replace, $array_to, $rule['skipwhere']);
		if($db->get_by('id', 'items', $skipwhere)) return array();
	}
	if(!empty($rule['pagerule'])) {
		if($rule['pagebreakstart'] != '' && $rule['pagebreakend'] != '') {
			$pagehtml = getfield($rule['pagebreakstart'], $rule['pagebreakend'], $content);
		} else {
			$pagehtml = $content;
		}
		$_links = parselinks($pagehtml);
		$pageurls = array();
		foreach($_links as $link => $title) {
			$link = calrealurl($link, $url);
			$link = filter($rule['pagebreakurlfilter'], $link);
			$title = filter($rule['pagebreaktitlefilter'], $title);
			if($link === false || $title === false) continue;
			if($link != $url) $pageurls[$link] = array(
				'url' => $link,
				'title' => $title
			);
		}
		$return['pageurls'] = $pageurls;
	}
	if(!empty($rule['dateline'])) {
		for($i = 1; $i <= 20; $i ++) {
			if(strpos($rule['dateline'], "[field{$i}]") !== false) {
				$array_to[$i] = ak_strtotime($array_to[$i]);
			}
		}
	}
	foreach(array('title', 'aimurl', 'shorttitle', 'dateline', 'author', 'source', 'editor', 'data', 'keywords', 'digest', 'picture', 'orderby', 'orderby2', 'orderby3', 'orderby4', 'orderby5', 'orderby6', 'orderby7', 'orderby8', 'comment', 'filename', 'pageview') as $field) {
		$return[$field] = ak_replace($array_replace, $array_to, $rule[$field]);
		if($field == 'dateline') $return[$field] = eval('return '.$return[$field].';');
	}
	if(trim($return['title']) == '') return false;
	if($return['picture'] != '') {
		if(strpos($return['picture'], '<') !== false) {
			$return['picture'] = pickpicture($return['picture'], $url);
		} else {
			$picture = calrealurl($return['picture'], $url);
			if(substr($picture, 0, 7) != 'http://') {
				$return['picture'] = '';
			} else {
				$return['picture'] = $picture;
			}
		}
	}
	if(substr($return['keywords'], 0, 6) == '[auto]') {
		$keywords = cloudkeywords($return['title'], substr($return['keywords'], 6));
		$return['keywords'] = '';
		if(!empty($keywords)) $return['keywords'] = implode(',', $keywords);
	}
	for($i = 1; $i <= 20; $i ++) {
		if(empty($rule['extname'.$i]) || empty($rule['extvalue'.$i])) continue;
		$v = ak_replace($array_replace, $array_to, $rule['extvalue'.$i]);
		$filter = $rule['extfilter'.$i];
		$v = filter($filter, $v);
		$return['_'.$rule['extname'.$i]] = $v;
	}
	foreach($return as $k => $v) {
		if(substr($k, 0, 1) == '_') continue;
		if(!isset($rule[$k.'_filter'])) continue;
		$filter = $rule[$k.'_filter'];
		$return[$k] = filter($filter, $v);
	}
	$hookfunction = "hook_spiderurl_{$rule['id']}";
	if(function_exists($hookfunction)) $return = $hookfunction($return);
	return $return;
}

function spiderurlpage($rule, $url, $itemid = 0) {
	global $db, $charset;
	$return = array();
	$html = '';
	if(!empty($url) && substr($url, 0, 1) != '#') {
		$html = readfromurl($url, 1);
		if($html == '') return false;
		debug($url.' downloaded!');
	}
	$content = "<url:{$url}>\n".$html;
	$content = str_replace("\r\n", "\n", $content);
	if(!empty($rule['htmlfilter'])) $content = filter($rule['htmlfilter'], $content);
	$array_replace = $array_to = array();
	for($i = 1; $i <= 5; $i ++) {
		$field_start = $rule["start{$i}"];
		$field_end = $rule["end{$i}"];
		$field_start = str_replace('[n]', "\n", $field_start);
		$field_end = str_replace('[n]', "\n", $field_end);
		$repeat = $rule["repeat{$i}"];
		if(!empty($field_start) && !empty($field_end)) {
			$field[$i] = getfield($field_start, $field_end, $content, empty($rule['repeat'.$i]) ? '' : '<!--akcmsspidersplit-->');
			$array_replace[] = "[field{$i}]";
			if($field[$i] === false) {
				$array_to[] = '';
			} else {
				$config = array(
					'itemurl' => $url,
					'spiderpic' => !empty($rule['spiderpic'.$i]),
					'repeat' => $rule['repeat'.$i],
					'filter' => $rule['filter'.$i]
				);
				$spiderfield = calspiderfield($field[$i], $config, $url, !empty($rule['finish']));
				if(isset($rule['pagebreakfield']) && $rule['pagebreakfield'] == $i) {
					foreach($pagehtmls as $url => $html) {
						$_field = getfield($field_start, $field_end, $html, empty($rule['repeat'.$i]) ? '' : '<!--akcmsspidersplit-->');
						if(!empty($rule["trim{$i}"])) $_field = trim($_field);
						$_field = calspiderfield($_field, $config, $url, !empty($rule['finish']));
						$spiderfield .= $_field;
					}
				}
				$array_to[] = $spiderfield;
			}
		}
	}
	foreach(array('subtitle', 'text') as $field) {
		$return[$field] = ak_replace($array_replace, $array_to, $rule[$field]);
	}
	if(trim($return['text']) == '' && $return['subtitle'] == '') return false;
	$hookfunction = "hook_spiderurlpage_{$rule['id']}";
	if(function_exists($hookfunction)) $return = $hookfunction($return);
	return $return;
}

function getspiderpicturetask() {
	$task = gettask('spiderpicture');
	return $task;
}

function calspiderfield($html, $config, $url = '', $finish = 0) {
	global $homepage;
	if(strpos($html, '<!--akcmsspidersplit-->') !== false && !empty($config['repeat'])) {
		$htmls = explode('<!--akcmsspidersplit-->', $html);
		$return = array();
		foreach($htmls as $html) {
			$return[] = calspiderfield($html, $config, $url, $finish);
		}
		return implode($config['repeat'], $return);
	}
	$html = str_replace("\t", '', $html);
	$html = str_replace("\r", '', $html);
	$html = str_replace("\n", '', $html);
	$html = filter($config['filter'], $html);
	if(!empty($config['spiderpic'])) {
		$html = copypicturetolocal($html, $config);
	}
	return $html;
}

function decode_htmlspecialchars($str) {
	$str = str_replace('&nbsp;', ' ', $str);
	$str = str_replace('&#39;', '\'', $str);
	$str = str_replace('&#8216;', '\'', $str);
	$str = str_replace('&#8217;', '\'', $str);
	$str = str_replace('&#8221;', '"', $str);
	return $str;
}

function killrepeatspace($str) {
	return preg_replace("/([ \t]+)/i", ' ', $str);
}

function insertspidereddata($spiderresult, $listrule, $task) {
	global $db, $thetime;
	$itemid = empty($task['itemid']) ? 0 : $task['itemid'];
	$modules = getcache('modules');
	$value = $spiderresult;
	unset($value['data'], $value['comment'], $value['pageurls']);
	if(!isset($value['category'])) $value['category'] = $listrule['category'];
	$value['section'] = $listrule['section'];
	if(empty($value['dateline'])) $value['dateline'] = $thetime;
	$value['lastupdate'] = $value['dateline'];

	$extvalue = array();
	$category = getcategorycache($value['category']);
	$module = $modules[$category['module']];
	if(!empty($module['data']['fields']['title']['ifinitial'])) $value['initial'] = getinitial($value['title']);
	foreach($value as $k => $v) {
		if(isset($module['data']['fields'][$k]['type']) && $module['data']['fields'][$k]['type'] == 'rich') $v = nl2br($v);
		if(substr($k, 0, 1) == '_') {
			$extvalue[$k] = $v;
			unset($value[$k]);
		}
	}
	if(!empty($extvalue)) $value['ext'] = 1;
	if($module['data']['fields']['data']['type'] == 'rich') $spiderresult['data'] = nl2br($spiderresult['data']);
	if(!empty($itemid)) {
		$db->update('items', $value, "id='$itemid'");
		if(!empty($spiderresult['data'])) {
			$db->update('texts', array('text' => $spiderresult['data']), "itemid='$itemid' AND page=0");
		}
		if(!empty($extvalue)) {
			$db->update('item_exts', array('value' => serialize($extvalue)), "id='$itemid'");
		}
	} else {
		$result = $db->insert('items', $value);
		$itemid = $db->insert_id();
		if(!empty($spiderresult['data'])) {
			$db->insert('texts', array('itemid' => $itemid, 'text' => $spiderresult['data']));
		}
		if(!empty($extvalue)) {
			$db->insert('item_exts', array('id' => $itemid, 'value' => serialize($extvalue)));
		}
		if(empty($task['norecord'])) {
			if(!ifcatched($task)) {
				$catched = array(
					'key' => ak_md5($task['url'], 1),
					'url' => $task['url'],
					'dateline' => $thetime,
					'rule' => $task['listrule'],
					'itemid' => $itemid
				);
				$db->insert('spider_catched', $catched);
			}
		}
	}
	return $itemid;
}

function ifcatched($task) {
	global $db;
	$key = ak_md5($task['url'], 1);
	$row = $db->get_by('*', 'spider_catched', "`key`='$key' AND rule='{$task['listrule']}'");
	if($row !== false) {
		return $row['itemid'];
	} else {
		return 0;
	}
}

function clearspidertask() {
	deletetask('spideritempage');
	deletetask('spideritem');
	deletetask('spiderlist');
	deletetask('spiderpicture');
}
?>