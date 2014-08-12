<?php
function operatehtml($html, $params, $starttime) {
	global $ifdebug;
	$endtime = akmicrotime();
	if(empty($params['noelapse']) && empty($params['assign']) && (!empty($ifdebug) || !empty($params['elapse']))) $html.='<!--'.msformat($endtime - $starttime).'-->';
	if(!empty($params['return'])) {
		return $html;
	} elseif(!empty($params['assign'])) {
		if(!empty($params['explode'])) $html = explode($params['explode'], $html);
		$GLOBALS['tpl']->assign(array($params['assign'] => $html));
	} else {
		echo $html;
	}
}

function gettime($params) {
	global $thetime;
	$starttime = akmicrotime();
	$params = operateparams('time', $params);
	$time = $thetime;
	if(!empty($params['time'])) $time = $params['time'];
	$fields = str_split('YmdHis');
	$datas = array();
	foreach($fields as $field) {
		$datas[0][$field] = date($field, $time);
	}
	$html = renderdata($datas, $params);
	return operatehtml($html, $params, $starttime);
}

function getitems($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('items', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		$datas = getitemsdata($params);
		$html = renderdata($datas, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function getitemsdata($params) {
	global $db, $sections, $homepage, $attachurl;
	if(!isset($attachurl)) $attachurl = $homepage;
	$sections = getcache('sections');
	$sql = getitemsql($params);
	$_items = $db->querytoarray($sql, 'id', $params['num']);
	$items = array();
	if(!empty($GLOBALS['inids'])) {
		foreach($GLOBALS['inids'] as $item) {
			if(empty($_items[$item])) continue;
			$items[$item] = $_items[$item];
		}
		unset($GLOBALS['inids']);
	} else {
		if($params['orderby'] == 'inid') {
			$_ids = explode(',', $params['id']);
			foreach($_ids as $_id) {
				if(empty($_items[$_id])) continue;
				$items[$_id] = $_items[$_id];
			}
		} else {
			$items = $_items;
		}
	}
	unset($_items);
	$ids = array();
	foreach($items as $item) {
		if(!empty($item['id'])) $ids[] = $item['id'];
	}
	if(!empty($ids)) {
		$_ids = implode(',', $ids);
		if(strpos($params['template'], '[_') !== false || strpos($params['template'], '[%_') !== false) {
			$query = $db->query_by('*', 'item_exts', "id IN ($_ids)");
			while($ext = $db->fetch_array($query)) {
				$values = unserialize($ext['value']);
				if(is_array($values)) {
					foreach($values as $key => $value) {
						$items[$ext['id']][$key] = $value;
					}
				}
			}
		}
		if(strpos($params['template'], '[data') !== false) {
			foreach($ids as $_i) {
				$items[$_i]['data'] = '';
			}
			$texts = $db->list_by('text,itemid', 'texts', "page=0 AND itemid IN ($_ids)");
			foreach($texts as $text) {
				$items[$text['itemid']]['data'] = $text['text'];
				if(strpos($params['template'], '[data_highlight]') !== false) $items[$text['itemid']]['data_highlight'] = highlight($items[$text['itemid']]['data'], $params['keywords']);
			}
		}
		preg_match_all("/\[%?#([a-zA-Z0-9]+)\]/i", $params['template'], $matches);
		$actionlist = $matches[1];
		if(!empty($actionlist) && !empty($params['uid'])) {
			$query = $db->query_by('*', 'actions', "uid='{$params['uid']}' AND iid IN ($_ids)");
			while($row = $db->fetch_array($query)) {
				$actions[$row['iid']][$row['action']] = $row['value'];
			}
		}
	}
	$j = 0;
	$datas = array();
	foreach($items as $item) {
		$j ++;
		if(!empty($actionlist)) {
			foreach($actionlist as $action) {
				$item['#'.$action] = 0;
				if(isset($actions[$item['id']][$action])) $item['#'.$action] = $actions[$item['id']][$action];
			}
		}
		$c = $item['category'];
		if(!isset($categories[$c])) $categories[$c] = getcategorycache($c);
		list($item['y'], $item['m'], $item['d'], $item['h'], $item['i'], $item['s'], $item['sy'], $item['sm'], $item['sd'], $item['F'], $item['M'], $item['l'], $item['D'], $item['r']) = explode('#', date('Y#m#d#H#i#s#y#n#j#F#M#l#D#r', $item['dateline']));
		if(strpos($params['template'], 'last_') !== false) {
			if($item['lastupdate'] == 0) $item['lastupdate'] = $item['dateline'];
			list($item['last_y'], $item['last_m'], $item['last_d'], $item['last_h'], $item['last_i'], $item['last_s'], $item['last_sy'], $item['last_sm'], $item['last_sd'], $item['last_F'], $item['last_M'], $item['last_l'], $item['last_D'], $item['last_r']) = explode('#', date('Y#m#d#H#i#s#y#n#j#F#M#l#D#r', $item['lastupdate']));
		}
		for($k = 1; $k <= 4; $k ++) {
			$item['url'.$k] = itemurl($item['id'], $k, $item, $categories[$c]);
		}
		$item['url'] = $item['url1'];
		$_texttitle = $item['texttitle'] = $item['title'];
		if(strpos($params['template'], '[title_keywords]') !== false) {
			$item['title_keywords'] = renderkeywords($item['title'], $item['keywords']);
		}
		$_textshorttitle = $item['textshorttitle'] = empty($item['shorttitle']) ? $item['texttitle'] : $item['shorttitle'];
		if($params['length'] != 0) {
			$_texttitle = ak_substr($item['texttitle'], 0, $params['length'], $params['strip']);
			$_textshorttitle = ak_substr($item['textshorttitle'], 0, $params['length'], $params['strip']);
		}
		$item['title'] = htmltitle($_texttitle, $item['titlecolor'], $item['titlestyle']);
		$item['texttitle'] = $_texttitle;
		if(strpos($params['template'], '[title_highlight]') !== false) $item['title_highlight'] = highlight($item['title'], $params['keywords']);
		$item['shorttitle'] = htmltitle($_textshorttitle, $item['titlecolor'], $item['titlestyle']);
		$item['textshorttitle'] = $_textshorttitle;
		$item['pv'] = $item['pageview'];
		$item['sectionid'] = $item['section'];
		$item['section'] = $sections[$item['sectionid']]['section'];
		$item['categoryid'] = $c;
		$item['category'] = $categories[$item['categoryid']]['category'];
		$item['categorypath'] = $categories[$item['categoryid']]['path'];
		$item['categoryhomepath'] = $categories[$item['categoryid']]['fullpath'];
		$item['categoryurl'] = getcategoryurl($item['categoryid']);
		$item['categoryup'] = $categories[$item['categoryid']]['categoryup'];
		$item['sectionurl'] = getsectionurl($item['sectionid']);
		$item['sectionalias'] = $sections[$item['sectionid']]['alias'];
		$item['itemid'] = $item['id'];
		$item['aimurl'] = str_replace('[home]', $homepage, $item['aimurl']);
		$item['id'] = $j;
		$item['realid'] = $j + $params['start'] - 1;
		if(empty($item['picture'])) $item['picture'] = $params['nopicture'];
		if(!empty($item['picture'])) {
			if(strpos($params['template'], '[picture:') !== false) {
				$_picture = getfield('[picture:', ']', $params['template']);
				if(strpos($_picture, '*') !== false) {
					require_once(CORE_ROOT.'include/image.func.php');
					list($w, $h) = explode('*', $_picture);
					$thumb = getthumbofpicture($item['picture'], $w, $h);
					$item['picture:'.$_picture] = $thumb;
				}
			}
			$item['picture'] = pictureurl($item['picture'], $attachurl);
		}
		if(strpos($params['template'], '[author_encode]') !== false) {
			$item['author_encode'] = urlencode($item['author']);
		}
		$_item = array();
		foreach($params['fields'] as $k) {
			if(strpos($k, 'picture') === false) {
				$_p = max(strpos($k , ':'), strpos($k , '#'), strpos($k , '@'));
				if($_p !== false) {
					$k = substr($k, 0, $_p);
				}
			}
			$_item[$k] = $item[$k];
		}
		$datas[] = $_item;
	}
	return $datas;
}

function getitemsql($params) {
	global $tablepre, $db, $thetime, $dbtype, $setting_ifdraft;
	$params['start'] = max(0, $params['start'] - 1);
	if(empty($params['sid']) || 1) {
		$sql_where = '1';
		$leftjoin = '';
		if(!empty($setting_ifdraft)) {
			if(empty($params['ignoredraft'])) $sql_where .= " AND draft=0";
		}
		if(!empty($params['category'])) {
			if($params['includesubcategory']) {
				$categories = includesubcategories($params['category']);
			} else {
				$categories = $params['category'];
			}
			$sql_where .= " AND category IN ({$categories})";
		} elseif(!empty($params['skipcategory'])) {
			if($params['includesubcategory']) {
				$skipcategories = includesubcategories($params['skipcategory']);
			} else {
				$skipcategories = $params['skipcategory'];
			}
			$sql_where .= " AND category NOT IN ({$skipcategories})";
		} else {
			$sql_where .= " AND category>0";
		}
		if(!empty($params['section'])) {
			$sql_where .= " AND section IN ({$params['section']})";
		} elseif(!empty($params['skipsection'])) {
			$sql_where .= " AND section NOT IN ({$params['skipsection']})";
		}
		if(!empty($params['id'])) {
			$sql_where .= " AND {$tablepre}_items.id IN ({$params['id']})";
		} elseif(!empty($params['skip'])) {
			$sql_where .= " AND {$tablepre}_items.id NOT IN ({$params['skip']})";
		}
		if($params['editinseconds'] != 0) {
			$timepoint = $thetime - intval($params['editinseconds']);
			$sql_where .= " AND lastupdate>'{$timepoint}'";
		}
		if($params['newinseconds'] != 0) {
			$timepoint = $thetime - intval($params['newinseconds']);
			$sql_where .= " AND dateline>'{$timepoint}'";
		}
		if(!empty($params['year'])) {
			if(!empty($params['month'])) {
				$startyear = $endyear = $params['year'];
				$startmonth = $endmonth = $params['month'];
				if(!empty($params['day'])) {
					$startday = $params['day'];
					$endday = $startday + 1;
				} else {
					$startday = $endday = 1;
					$endmonth += 1;
				}
			} else {
				$startyear = $params['year'];
				$endyear = $startyear + 1;
				$startmonth = $endmonth = 1;
				$startday = $endday = 1;
			}
			$_startpoint = mktime(0, 0, 0, $startmonth, $startday, $startyear);
			$_endpoint = mktime(0, 0, 0, $endmonth, $endday, $endyear);
			$sql_where .= " AND dateline>='$_startpoint' AND dateline<'$_endpoint'";
		}
		if($params['last'] != 0) {
			$sql_where .= " AND {$tablepre}_items.id>'{$params['last']}'";
		}
		if($params['next'] != 0) {
			$sql_where .= " AND {$tablepre}_items.id<'{$params['next']}'";
		}
		if(!empty($params['order'])) $sql_where .= " AND orderby>='{$params['order']}'";
		if(!empty($params['orderby2'])) $sql_where .= " AND orderby2='{$params['orderby2']}'";
		if(!empty($params['orderby3'])) $sql_where .= " AND orderby3='{$params['orderby3']}'";
		if(!empty($params['orderby4'])) $sql_where .= " AND orderby4='{$params['orderby4']}'";
		if($params['picture'] == 1) {
			$sql_where .= " AND picture<>''";
		} elseif($params['picture'] == -1) {
			$sql_where .= " AND picture=''";
		}
		if(!empty($params['author'])) {
			$sql_where .= " AND author='{$params['author']}'";
		}
		if(!empty($params['keywords'])) {
			if(!empty($params['searchtext'])) $leftjoin = "LEFT JOIN {$tablepre}_texts ON {$tablepre}_texts.itemid={$tablepre}_items.id";
			$array_keywords = explode(',', $params['keywords']);
			$sql_keywords = '0';
			foreach($array_keywords as $keyword) {
				if(!empty($keyword)) {
					$sql_keywords .= " OR title LIKE '%{$keyword}%' OR keywords LIKE '%{$keyword}%'";
					if(!empty($params['searchtext'])) $sql_keywords .= " OR {$tablepre}_texts.text LIKE '%{$keyword}%'";
				}
			}
			$sql_where .= " AND ($sql_keywords)";
		} elseif(isset($params['keywords'])) {
			$sql_where .= ' AND 0';
		}
		if($params['timelimit'] == 1) $sql_where .= " AND dateline < '$thetime'";
		if(!empty($params['where'])) {
			foreach($params as $_k => $_v) {
				if($_k == 'where') continue;
				$params['where'] = str_replace("[$_k]", $_v, $params['where']);
			}
			$sql_where .= " AND ".$params['where'];
		}
		$orderby = order_operate($params['orderby'], 'items');
		if(!empty($params['sqlorderby'])) {
			$sqlorderby = 'ORDER BY '.$params['sqlorderby'];
		} else {
			$sqlorderby = '';
			if($orderby == 'random') {
				if(strpos($dbtype, 'mysql') !== false) {
					$sqlorderby = 'ORDER BY rand()';
				} elseif(strpos($dbtype, 'sqlite') !== false) {
					$sqlorderby = "ORDER BY random()";
				}
			} elseif($orderby != '' && $orderby != 'inid') {
				$sqlorderby = 'ORDER BY '.$orderby;
			}
		}
		hookfunction('getitems', $sql_where, $params);
		if(!empty($params['bandindex'])) {
			$_r = $db->get_one("SELECT COUNT(*) as c FROM {$tablepre}_items {$leftjoin} WHERE $sql_where");
			$count = $_r['c'];
			$GLOBALS['index'.$params['bandindex'].'count'] = $count;
			$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
		}
		$sql = "SELECT {$tablepre}_items.id as itemid FROM {$tablepre}_items {$leftjoin} WHERE {$sql_where} {$sqlorderby} LIMIT {$params['start']},{$params['num']}";
		$inids = $db->querytoarray($sql, '', $params['num']);
		foreach($inids as $k => $v) {
			$inids[$k] = $v['itemid'];
		}
	}
	if(!empty($inids)) {
		if(count($inids) == 1) {
			$sql_where = "id='".current($inids)."'";
		} else {
			$inids = implode(',', $inids);
			$sql_where = "id IN ({$inids})";
		}
	} else {
		$sql_where = '0';
	}
	if($sqlorderby == 'ORDER BY rand()') $sqlorderby = '';
	$sql = "SELECT * FROM {$tablepre}_items WHERE {$sql_where} {$sqlorderby}";
	return $sql;
}

function gettexts($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('texts', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		$datas = gettextsdata($params);
		$html = renderdata($datas, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function gettextsdata($params) {
	global $db;
	$categories = $items = array();
	$sql = gettextssql($params);
	$_texts = $db->querytoarray($sql);
	$j = 0;//序号
	$datas = array();
	foreach($_texts as $text) {
		$j ++;
		$itemid = $text['itemid'];
		if(!isset($items[$itemid])) {
			$item = $db->get_by('*', 'items', "id=$itemid");
			$items[$itemid] = $item;
			$c = $item['category'];
			$category = getcategorycache($c);
			$categories[$c] = $category;
		} else {
			$item = $items[$itemid];
			$category = $categories[$c];
		}
		$text['textid'] = $text['id'];
		$text['id'] = $j;
		$text['url'] = itempageurl($itemid, $text['page'], $item, $category);
		$datas[] = $text;
	}
	return $datas;
}

function gettextssql($params) {
	global $tablepre;
	$params['start'] = max(0, $params['start'] - 1);
	$sql_where = '1';
	if(!empty($params['itemid'])) {
		$sql_where .= " AND itemid={$params['itemid']}";
	}
	if(isset($params['itempage'])) {
		if($params['itempage'] == 0) {
			$sql_where .= " AND 0";
		} else {
			$sql_where .= " AND page={$params['itempage']}";
		}
	}
	$sqlorderby = order_operate($params['orderby'], 'texts');
	return "SELECT * FROM {$tablepre}_texts WHERE {$sql_where} ORDER BY {$sqlorderby} LIMIT {$params['start']},{$params['num']}";
}

function getcategories($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('categories', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		$datas = getcategoriesdata($params);
		$html = renderdata($datas, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function getcategoriesdata($params) {
	global $db;
	if(isset($params['childcategory'])) {
		$_c = $params['childcategory'];
		$_categories = array();
		while($_c != 0) {
			if(!isset($categories[$_c])) $categories[$_c] = getcategorycache($_c);
			$_categories[] = $categories[$_c];
			$_c = $categories[$_c]['categoryup'];
		}
		if($params['orderby'] != 'orderby_reverse') $_categories = array_reverse($_categories);
		$_categories = array_slice($_categories, 0, min(count($_categories), $params['num']));
	} else {
		$sql = getcategoriessql($params);
		$_categories = $db->querytoarray($sql);
	}
	$j = 0;//序号
	$datas = array();
	foreach($_categories as $category) {
		$j ++;
		$category['url'] = getcategoryurl($category['id']);
		$category['categoryid'] = $category['id'];
		$category['id'] = $j;
		if(!empty($category['picture'])) {
			if(strpos($params['template'], '[picture:') !== false) {
				$_picture = getfield('[picture:', ']', $params['template']);
				if(strpos($_picture, '*') !== false) {
					require_once(CORE_ROOT.'include/image.func.php');
					list($w, $h) = explode('*', $_picture);
					$thumb = getthumbofpicture($category['picture'], $w, $h);
					$category['picture:'.$_picture] = $thumb;
				}
			}
			$category['picture'] = pictureurl($category['picture'], $attachurl);
		}
		if(empty($category['picture'])) $category['picture'] = $params['nopicture'];
		if(is_array($category['subcategories'])) $category['subcategories'] = implode(',', $category['subcategories']);
		$datas[] = $category;
		
	}
	return $datas;
}

function getcategoriessql($params) {
	global $tablepre, $db;
	$params['start'] = max(0, $params['start'] - 1);
	$sql_where = '1';
	if($params['rootcategory'] >= 0) {
		$sql_where .= " AND categoryup ='{$params['rootcategory']}'";
	}
	if(!empty($params['skipsub'])) {
		$sql_where .= " AND categoryup ='0'";
	}
	if(!empty($params['path'])) {
		$sql_where .= " AND path ='{$params['path']}'";
	}
	if(!empty($params['id'])) {
		if(strpos($params['id'], ',') === false) {
			$sql_where .= " AND id = '{$params['id']}'";
		} else {
			$sql_where .= " AND id IN ({$params['id']})";
		}
	}
	if(!empty($params['skipid'])) {
		$sql_where .= " AND id NOT IN ({$params['skipid']})";
	}
	if(!empty($params['order'])) {
		$sql_where .= " AND orderby>={$params['order']}";
	}
	if(!empty($params['module'])) {
		$sql_where .= " AND module='{$params['module']}'";
	}
	if(!empty($params['bandindex'])) {
		$_r = $db->get_one("SELECT COUNT(*) as c FROM {$tablepre}_categories WHERE $sql_where");
		$count = $_r['c'];
		$GLOBALS['index'.$params['bandindex'].'count'] = $count;
		$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
	}
	$sqlorderby = order_operate($params['orderby'], 'categories');
	return "SELECT * FROM {$tablepre}_categories WHERE {$sql_where} ORDER BY {$sqlorderby} LIMIT {$params['start']},{$params['num']}";
}

function getcomments($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('comments', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		$datas = getcommentsdata($params);
		$html = renderdata($datas, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function getcommentsdata($params) {
	global $db;
	$sql = getcommentssql($params);
	$comments = $db->querytoarray($sql);
	$j = 0;
	$datas = array();
	foreach($comments as $comment) {
		$j ++;
		$comment['id'] = $j;
		$comment['realid'] = $j + $params['start'] - 1;
		$comment['secretip'] = encodeip($comment['ip']);
		$comment['message'] = ak_htmlspecialchars($comment['message']);
		$comment['title'] = ak_htmlspecialchars($comment['title']);
		$comment['username'] = ak_htmlspecialchars($comment['username']);
		$comment['review'] = ak_htmlspecialchars($comment['review']);
		list($comment['y'], $comment['m'], $comment['d'], $comment['h'], $comment['i'], $comment['s'], $comment['sy'], $comment['sm'], $comment['sd']) = explode('#', date('Y#m#d#H#i#s#y#n#j', $comment['dateline']));
		list($comment['ry'], $comment['rm'], $comment['rd'], $comment['rh'], $comment['ri'], $comment['rs'], $comment['rsy'], $comment['rsm'], $comment['rsd']) = explode('#', date('Y#m#d#H#i#s#y#n#j', $comment['reviewtime']));
		$datas[] = $comment;
	}
	return $datas;
}

function getcommentssql($params) {
	global $tablepre, $db;
	$params['start'] = max(0, $params['start'] - 1);
	$sql_where = '1';
	if(!empty($params['itemid'])) {
		$sql_where .= " AND itemid = '{$params['itemid']}'";
	}
	if(!empty($params['reviewed'])) {
		$sql_where .= " AND reviewtime>0";
	}
	if(!empty($params['bandindex'])) {
		$count = $db->get_by("COUNT(*) as c", 'comments', $sql_where);
		$GLOBALS['index'.$params['bandindex'].'count'] = $count;
		$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
	}
	$sqlorderby = order_operate($params['orderby'], 'comments');
	return "SELECT * FROM {$tablepre}_comments WHERE {$sql_where} ORDER BY {$sqlorderby} LIMIT {$params['start']},{$params['num']}";
}

function getsections($params) {
	global $cache_memory, $ifdebug, $sections;
	$starttime = akmicrotime();
	$params = operateparams('sections', $params);
	$data = getsectionsdata($params);
	$html = renderdata($data, $params);
	return operatehtml($html, $params, $starttime);
}

function getsectionsdata($params) {
	global $db;
	$data = array();
	$j = 0;
	$sql = getsectionssql($params);
	$data = $db->querytoarray($sql);
	foreach($data as $k => $v) {
		$j++;
		$data[$k]['sectionid'] = $data[$k]['id'];
		$data[$k]['id'] = $j;
		unset($data[$k]['sectionhomemethod']);
            	unset($data[$k]['defaulttemplate']);
            	unset($data[$k]['listtemplate']);
            	unset($data[$k]['html']);
	}
	return $data;
}

function getsectionssql($params){
	global $tablepre, $db;
	$params['start'] = max(0, $params['start'] - 1);
	$sql_where = '1';
	if(!empty($params['id'])) {
		if(strpos($params['id'], ',') === false) {
			$sql_where .= " AND id = '{$params['id']}'";
		} else {
			$sql_where .= " AND id IN ({$params['id']})";
		}
	}
	if(!empty($params['skipid'])) {
		$sql_where .= " AND id NOT IN ({$params['skipid']})";
	}
	if(!empty($params['alias'])) {
		$sql_where .= " AND alias='{$params['alias']}'";
	}
	return "SELECT * FROM {$tablepre}_sections WHERE {$sql_where} LIMIT {$params['start']},{$params['num']}";
}

function getlists($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('lists', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		$datas = getlistsdata($params);
		$html = renderdata($datas, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function getlistsdata($params) {
	$params['list'] = tidyitemlist($params['list'], $params['sc'], 0);
	if(empty($params['list'])) return array();
	$lists = explode($params['sc'], $params['list']);
	$j = 0;
	if(!empty($params['bandindex'])) {
		$GLOBALS['index'.$params['bandindex'].'count'] = count($lists);
		$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
	}
	if($params['orderby'] == 'id_reverse') $lists = array_reverse($lists);
	if($params['orderby'] == 'random') {
		if(isset($params['seed'])) {
			$int = stringtoint($params['seed']);
			srand($int);
		}
		shuffle($lists);
		srand();
	}
	$datas = array();
	foreach($lists as $list) {
		if($params['start'] > 1) {
			$params['start'] --;
			continue;
		}
		$j ++;
		if($params['num'] > 0 && $j > $params['num']) break;
		$item['item'] = $list;
		$item['iteminurl'] = urlencode($list);
		$item['iteminhtml'] = ak_htmlspecialchars($list);
		$item['id'] = $j;
		$datas[] = $item;
	}
	return $datas;
}

function getattachments($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('attachments', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		$datas = getattachmentsdata($params);
		$html = renderdata($datas, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function getattachmentsdata($params) {
	global $db, $sections, $homepage, $thumburlroot, $attachurlroot;
	$sql = getattachmentsql($params);
	$query = $db->query($sql);
	$attachments = array();
	$exts = array();
	$skipexts = array();
	if(!empty($params['ext'])) $exts = explode(',', $params['ext']);
	if(!empty($params['skipext'])) $skipexts = explode(',', $params['skipext']);
	while($attachment = $db->fetch_array($query)) {
		$_ext = fileext($attachment['filename']);
		if(!empty($exts) && !in_array($_ext, $exts)) continue;
		if(!empty($skipexts) && in_array($_ext, $skipexts)) continue;
		$attachments[$attachment['id']] = $attachment;
	}
	$j = 0;
	$datas = array();
	foreach($attachments as $attachment) {
		$j ++;
		if(ispicture($attachment['filename'])) {
			if(strpos($params['template'], '[thumb:') !== false) {
				$_picture = getfield('[thumb:', ']', $params['template']);
				if(strpos($_picture, '*') !== false) {
					list($w, $h) = explode('*', $_picture);
					require_once(CORE_ROOT.'include/image.func.php');
					$thumb = getthumbofpicture($attachment['filename'], $w, $h);
					$attachment['thumb:'.$_picture] = $thumb;
				}
			}
		}
		if(!empty($attachurlroot)) {
			$attachment['filename'] = $attachurlroot.$attachment['filename'];
		} elseif(strpos($attachment['filename'], '://') === false) {
			$attachment['filename'] = $homepage.$attachment['filename'];
		} else {
			$attachment['filename'] = $attachment['filename'];
		}
		if(strpos($params['template'], '[itemurl]') !== false) {
			if(!isset($items[$attachment['itemid']])) {
				$items[$attachment['itemid']] = $db->get_by('*', 'items', "id='{$attachment['itemid']}'");
			}
			$item = $items[$attachment['itemid']];
			$attachment['itemurl'] = itemurl($item['id'], 1, $item);
		}
		if(empty($attachment['originalname'])) $attachment['originalname'] = basename($attachment['filename']);
		if(!empty($attachment['ext'])) {
			$attachment['ext'] = unserialize($attachment['ext']);
			foreach($attachment['ext'] as $key => $v) {
				$attachment['_'.$key] = $v;
			}
			unset($attachment['ext']);
		}
		list($attachment['y'], $attachment['m'], $attachment['d'], $attachment['h'], $attachment['i'], $attachment['s'], $attachment['sy'], $attachment['sm'], $attachment['sd'], $attachment['F'], $attachment['M'], $attachment['l'], $attachment['D'], $attachment['r']) = explode(' ', date('Y m d H i s y n j F M l D r', $attachment['dateline']));
		$datas[] = $attachment;
	}
	return $datas;
}

function getattachmentsql($params) {
	global $db, $tablepre;
	$params['start'] = max(0, $params['start'] - 1);
	$sql_where = '1';
	if(!empty($params['id'])) $sql_where .= " AND id IN ({$params['id']})";
	if(!empty($params['itemid'])) $sql_where .= " AND itemid IN ({$params['itemid']})";
	if(!empty($params['category'])) $sql_where .= " AND category IN ({$params['category']})";
	if(!empty($params['ispicture'])) $sql_where .= " AND ispicture=0";
	$sqlorderby = order_operate($params['orderby'], 'attachments');
	$count = $db->get_by('COUNT(*) as c', 'attachments', $sql_where);
	if(!empty($params['bandindex'])) {
		$GLOBALS['index'.$params['bandindex'].'count'] = $count;
		$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
	}
	return "SELECT * FROM {$tablepre}_attachments WHERE {$sql_where} ORDER BY {$sqlorderby} LIMIT {$params['start']},{$params['num']}";
}

function getsqls($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('sql', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		$datas = getsqlsdata($params);
		$html = renderdata($datas, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function getsqlsdata($params) {
	global $db;
	$query = $db->query($params['sql'], 1);
	$j = 0;
	$datas = array();
	while($row = $db->fetch_array($query)) {
		$j ++;
		$datas[] = $row;
	}
	return $datas;
}

function getinfo($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$infos = getcache('infos');
	$html = $params['template'];
	foreach($infos as $k => $v) {
		$html = str_replace("[$k]", $v, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function getpictures($params) {
	global $cache_memory, $ifdebug, $homepage, $attachurl;
	$starttime = akmicrotime();
	$params = operateparams('pictures', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		if($params['sourcetype'] == 'spider') {
			$html = readfromurl($params['source']);
			$baseurl = $params['source'];
		} else {
			$html = $params['source'];
			$baseurl = $homepage;
		}
		if(!empty($params['bodystart']) && !empty($params['bodyend'])) {
			$html = getfield($params['bodystart'], $params['bodyend'], $html);
		}
		preg_match_all("/<img(.*?)src=(.+?)['\" >]+/is", $html, $match);
		$_pics = $pics = array();
		foreach($match[2] as $pic) {
			$pic = str_replace('"', '', $pic);
			$pic = str_replace('\'', '', $pic);
			if(!empty($params['character']) && strpos($pic, $params['character']) === false) continue;
			if(!empty($params['skip']) && strpos($pic, $params['skip']) !== false) continue;
			$pic = calrealurl($pic, $baseurl);
			$_pics[] = $pic;
		}
		$_pics = array_unique($_pics);
		foreach($_pics as $pic) {
			$item = array('picture' => $pic);
			if(strpos($params['template'], '[thumb:') !== false) {
				$_picture = getfield('[thumb:', ']', $params['template']);
				if(strpos($_picture, '*') !== false) {
					list($w, $h) = explode('*', $_picture);
					require_once(CORE_ROOT.'include/image.func.php');
					$thumb = getthumbofpicture($pic, $w, $h);
					if($thumb === false) continue;
					$item['thumb:'.$_picture] = $thumb;
				}
			}
			$pics[] = $item;
			if(count($pics) >= $params['num']) break;
		}
		$html = renderdata($pics, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function akecho($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$html = $params['source'];
	if(!empty($params['filter'])) $html = filter($params['filter'], $html);
	return operatehtml($html, $params, $starttime);
}

function akincludeurl($params) {
	global $host;
	$starttime = akmicrotime();
	if(!isset($params['url'])) return '';
	if(substr($params['url'], 0, 1) == '/') $params['url'] = 'http://'.$host.$params['url'];
	if(strpos($params['url'], 'http://') === false) return '';
	if(!isset($params['expire'])) {
		$html = readfromurl($params['url'], 1);
	} else {
		$p = array('type' => 'url', 'url' => $params['url'], 'expire' => $params['expire']);
		$html = getcachedata($p);
		if($data == '') {
			$html = readfromurl($params['url'], 1);
			setcachedata($p, $html);
		}
	}
	if(!empty($params['filter'])) $html = filter($params['filter'], $html);
	return operatehtml($html, $params, $starttime);
}

function operateparams($type, $params) {
	global $lr, $tablepre, $db;
	if(!isset($db)) $db = db();
	foreach($params as $key => $value) {
		if($key == 'sql' || $key == 'where' || $key == 'emptymessage' || $key == 'overflow' || strpos($key, 'template') !== false) continue;
		if($type == 'lists' && $key == 'list') continue;
		if($type == 'pictures' && $key == 'source') continue;
		if($type == 'paging' && $key == 'paging') continue;
		$params[$key] = $db->addslashes($value);
	}
	$start = isset($params['start']) && a_is_int($params['start']) ? $params['start'] : 1;
	$num = empty($params['num']) ? 10 : intval($params['num']);
	$colspan = !empty($params['colspan']) && a_is_int($params['colspan']) ? $params['colspan'] : 0;//循环几次后插入修正符
	$overflow = empty($params['overflow']) ? '' : $params['overflow'];//修正符
	$expire = !empty($params['expire']) && a_is_int($params['expire']) ? $params['expire'] : 0;//缓存有效期，单位秒
	$length = !empty($params['length']) && a_is_int($params['length']) ? $params['length'] : 0;//长度限制
	$strip = empty($params['strip']) ? '' : $params['strip'];//超出长度限制后显示的字符
	$orderby = empty($params['orderby']) ? '' : tidyitemlist($params['orderby'], ',', 0);
	$emptymessage = empty($params['emptymessage']) ? '' : $params['emptymessage'];
	$emptymessage = str_replace('()', '"', $emptymessage);
	if(isset($params['page']) && a_is_int($params['page']) && $params['page'] > 0) $start = ($params['page'] - 1) * $num + 1;
	$return = array(
		'start' => $start,
		'num' => $num,
		'colspan' => $colspan,
		'overflow' => $overflow,
		'expire' => $expire,
		'type' => $type,
		'length' => $length,
		'strip' => $strip,
		'orderby' => $orderby,
		'bandindex' => !empty($params['bandindex']),
		'return' => !empty($params['return']),
		'emptymessage' => $emptymessage,
		'elapse' => !empty($params['elapse']),
		'noelapse' => !empty($params['noelapse']),
	);
	if($type == 'items') {
		$return['newinseconds'] = !empty($params['newinseconds']) && a_is_int($params['newinseconds']) ? $params['newinseconds'] : 0;
		$return['editinseconds'] = !empty($params['editinseconds']) && a_is_int($params['editinseconds']) ? $params['editinseconds'] : 0;
		$return['section'] = empty($params['section']) ? '' : tidyitemlist($params['section']);
		$return['skipsection'] = empty($params['skipsection']) ? '' : tidyitemlist($params['skipsection']);
		$return['category'] = empty($params['category']) ? '' : tidyitemlist($params['category']);
		$return['skipcategory'] = empty($params['skipcategory']) ? '' : tidyitemlist($params['skipcategory'].',0,');
		$return['id'] = empty($params['id']) ? '' : tidyitemlist($params['id']);
		$return['skip'] = empty($params['skip']) ? '' : tidyitemlist($params['skip']);
		$return['timelimit'] = !empty($params['timelimit']) ? 1 : 0;
		$return['includesubcategory'] = empty($params['includesubcategory']) ? 0 : 1;
		$template = empty($params['template']) ? '[title]<br>' : $params['template'];
		$return['show_text'] = strpos($template, '[text') !== false ? 1 : 0;
		$return['order'] = empty($params['order']) ? 0 : $params['order'];
		$return['picture'] = isset($params['picture']) ? $params['picture'] : 0;
		$return['nopicture'] = empty($params['nopicture']) ? '' : $params['nopicture'];
		$return['last'] = empty($params['last']) ? 0 : $params['last'];
		$return['year'] = empty($params['year']) ? 0 : $params['year'];
		$return['month'] = empty($params['month']) ? 0 : $params['month'];
		$return['day'] = empty($params['day']) ? 0 : $params['day'];
		$return['next'] = empty($params['next']) ? 0 : $params['next'];
		$return['where'] = empty($params['where']) ? '' : $params['where'];
		$return['head'] = empty($params['head']) ? 255 : $params['head'];
		$return['author'] = empty($params['author']) ? '' : $params['author'];
		preg_match_all("/\[%?(_?#?[a-zA-Z0-9_]+([:#@][^\]]+)?)\]/is", $template, $match);
		$return['fields'] = $match[1];
	} elseif($type == 'paging') {
		$return['id'] = empty($params['id']) ? '1' : $params['id'];
		$return['nohtml'] = !empty($params['nohtml']);
		$return['page'] = $params['page'];
		$template = empty($params['template']) ? '[page]' : $params['template'];
		$return['currenttemplate'] = empty($params['currenttemplate']) ? '<a href="javascript:void(0)">[page]</a>' : $params['currenttemplate'];
		$return['paging'] = empty($params['paging']) ? '[paging]' : $params['paging'];
		$return['total'] = $GLOBALS['index'.$return['id'].'count'];
		$return['total'] = isset($params['total']) ? $params['total'] : $return['total'];
		$return['ipp'] = $GLOBALS['index'.$return['id'].'ipp'];
		if(empty($return['ipp'])) $return['ipp'] = 10;
		isset($params['ipp']) && $return['ipp'] = $params['ipp'];
		$return['maxpage'] = ceil($return['total'] / $return['ipp']);
		if(isset($params['maxlimit'])) $return['maxpage'] = min($params['maxlimit'], $return['maxpage']);
	} elseif($type == 'texts') {
		$template = empty($params['template']) ? '<h1>[subtitle]</h1><br />[text]<br />' : $params['template'];
		$return['itemid'] = (isset($params['itemid']) && a_is_int($params['itemid'])) ? $params['itemid'] : 0;
		$return['orderby'] = empty($return['orderby']) ? 'page' : $return['orderby'];
	} elseif($type == 'categories') {
		$template = empty($params['template']) ? '[category]&nbsp;' : $params['template'];
		$return['rootcategory'] = isset($params['rootcategory']) && a_is_int($params['rootcategory']) ? $params['rootcategory'] : -1;
		$return['childcategory'] = isset($params['childcategory']) && a_is_int($params['childcategory']) ? $params['childcategory'] : null;
		$return['id'] = isset($params['id']) ? tidyitemlist($params['id']) : '';
		$return['skipid'] = isset($params['skipid']) ? tidyitemlist($params['skipid']) : '';
		$return['skipsub'] = empty($params['skipsub']) ? 0 : 1;
		$return['orderby'] = empty($return['orderby']) ? 'id' : $return['orderby'];
		$return['order'] = (empty($params['order']) || !a_is_int($params['order'])) ? 0 : $params['order'];
	} elseif($type == 'comments') {
		$template = empty($params['template']) ? '[message]<br>' : $params['template'];
		$return['itemid'] = empty($params['itemid']) || !a_is_int($params['itemid']) ? 0 : $params['itemid'];
		$return['reviewed'] = empty($params['reviewed']) ? 0 : $params['reviewed'];
		$return['orderby'] = empty($return['orderby']) ? 'id_reverse' : $return['orderby'];
	} elseif($type == 'lists') {
		$template = empty($params['template']) ? '[item]<br>' : $params['template'];
		$return['sc'] = empty($params['sc']) ? ',' : $params['sc'];
		$return['list'] = empty($params['list']) ? '' : $params['list'];
		$return['num'] = empty($params['num']) ? -1 : $params['num'];
	} elseif($type == 'attachments') {
		$return['itemid'] = empty($params['itemid']) ? '' : tidyitemlist($params['itemid']);
		$return['id'] = empty($params['id']) ? '' : tidyitemlist($params['id']);
		$return['ext'] = empty($params['ext']) ? '' : tidyitemlist($params['ext'], ',', 0);
		$return['skipext'] = empty($params['skipext']) ? '' : tidyitemlist($params['skipext'], ',', 0);
		$return['category'] = empty($params['category']) ? '' : tidyitemlist($params['category']);
		$return['type'] = empty($params['type']) ? 'all' : $params['type'];
		$template = empty($params['template']) ? '<a href=()[filename]()>[originalname]</a><br />' : $params['template'];
		$return['orderby'] = empty($return['orderby']) ? 'id' : $return['orderby'];
	} elseif($type == 'sql') {
		$return['sql'] = str_replace('[tablepre]', $tablepre, $params['sql']);
		$template = $params['template'];
	} elseif($type == 'pictures') {
		if(!in_array($params['sourcetype'], array('html', 'spider'))) {
			$return['sourcetype'] = 'html';
		} else {
			$return['sourcetype'] = $params['type'];
		}
		$template = $params['template'];
	} else {
		$template = isset($params['template']) ? $params['template'] : '';
	}
	$template = ak_replace('()', '"', $template);
	$template = ak_replace('[lr]', $lr, $template);
	$return['template'] = $template;
	foreach($params as $_k => $_v) {
		if(isset($return[$_k])) continue;
		$return[$_k] = $_v;
	}
	$key = ak_md5(serialize($return), 1);
	$return['cachekey'] = $key;
	return $return;
}

function order_operate($rule, $type = 'items') {
	global $tablepre;
	if(!in_array($type, array('items', 'categories', 'comments', 'attachments', 'texts', 'lists'))) {
		return '';
	}
	if(strpos($rule, 'random') !== false) return 'random';
	$array_items_field = array(
		'id' => $tablepre.'_items.id',
		'orderby' => 'orderby',
		'orderby2' => 'orderby2',
		'orderby3' => 'orderby3',
		'orderby4' => 'orderby4',
		'orderby5' => 'orderby5',
		'orderby6' => 'orderby6',
		'orderby7' => 'orderby7',
		'orderby8' => 'orderby8',
		'time' => 'dateline',
		'update' => 'lastupdate',
		'pv' => 'pageview',
		'title' => 'title',
		'count' => 'count',
		'inid' => 'inid',
		'pv1' => 'pv1',
		'pv2' => 'pv2',
		'pv3' => 'pv3',
		'pv4' => 'pv4',
		'comment' => 'lastcomment',
		'commentnum' => 'commentnum',
	);
	$array_categories_field = array(
		'orderby' => 'orderby',
		'id' => 'id',
		'items' => 'items'
	);
	$array_sections_field = array(
		'orderby' => 'orderby',
		'id' => 'id'
	);
	$array_comments_field = array(
		'id' => 'id',
		'time' => 'dateline',
		'goodnum' => 'goodnum',
		'badnum' => 'badnum',
		'floor' => 'floor'
	);
	$array_attachments_field = array(
		'id' => 'id',
		'itemid' => 'itemid',
		'orderby' => 'orderby'
	);
	$array_texts_field = array(
		'page' => 'page',
	);
	$array_lists_field = array(
		'id' => 'id',
	);
	$arrayname = "array_{$type}_field";
	$arrayname = $$arrayname;
	$rules = explode(',', $rule);
	$return = '';
	foreach($rules as $rule) {
		$array_temp = explode('_', $rule);
		if(!isset($array_temp[0]) || !array_key_exists($array_temp[0], $arrayname)) {
			continue;
		}
		$return .= ',`'.str_replace('.', '`.`', $arrayname[$array_temp[0]]).'`';
		if(isset($array_temp[1]) && $array_temp[1] == 'reverse') $return .= ' DESC';
	}
	$return = str_replace('`inid`', 'inid', $return);
	$return = str_replace('`random`', 'random', $return);
	return substr($return, 1);
}

function getpaging($params) {
	$starttime = akmicrotime();
	$params = operateparams('paging', $params);
	
	$page = $params['page'];
	$total = $params['total'];
	if($params['maxpage'] <= $params['num']) {
		$startid = 1;
		$endid = $params['maxpage'];
	} else {
		$range = floor($params['num'] / 2);
		$startid = $page - $range;
		$endid = $page + $params['num'] - $range - 1;
		if($startid < 1) {
			$startid = 1;
			$endid = $params['num'];
		} elseif($endid > $params['maxpage']) {
			$startid = $params['maxpage'] - $params['num'] + 1;
			$endid = $params['maxpage'];
		}
	}
	if(empty($params['nohtml']) && !empty($GLOBALS['index_work'])) {
		list($type, $id, $filenamebase) = explode("\n", $GLOBALS['index_work']);
		$dirbase = $filenamebase;
		if(substr($filenamebase, -1) != '/') {
			$dirbase = dirname($filenamebase);
		} else {
			$dirbase = substr($filenamebase, 0, -1);
		}
		$replaces = array();
		for($i = 2; $i <= $params['maxpage']; $i ++) {
			$baseurl = $params['baseurl'];
			$filename = calindexurl($baseurl, $i, $replaces);
			$filename = FORE_ROOT.$filename;
			if($filename == $filenamebase) continue;
			addtask('createhtml_'.$type, "$id\t".$filename."\t".$i);
		}
	}
	$paging = '';
	$currenturl = calindexurl($params['baseurl'], $page, array(), $params['firstpage']);
	for($i = $startid; $i <= $endid; $i ++) {
		$url = calindexurl($params['baseurl'], $i, array(), $params['firstpage']);
		$url = calrelativeurl($currenturl, $url);
		if($i != $page) {
			$t = $params['template'];
		} else {
			$t = $params['currenttemplate'];
		}
		if($i == 1) {
			if($page == 1 && !empty($params['currentfirstpagetemplate'])) {
				$t = $params['currentfirstpagetemplate'];
			} elseif($page > 1 && !empty($params['firstpagetemplate'])) {
				$t = $params['firstpagetemplate'];
			}
		}
		$t = str_replace('[url]', $url, $t);
		$t = str_replace('[page]', $i, $t);
		$paging .= $t;
	}
	if($page <= 1) {
		$previous = $params['noprevioustemplate'];
		$first = $params['alreadyfirsttemplate'];
		$previousid = 1;
	} else {
		$first = $params['firsttemplate'];
		$previous = $params['previoustemplate'];
		$previousid = $page - 1;
	}
	if($page >= $params['maxpage']) {
		$next = $params['nonexttemplate'];
		$last = $params['alreadylasttemplate'];
		$nextid = $params['maxpage'];
	} else {
		$next = $params['nexttemplate'];
		$last = $params['lasttemplate'];
		$nextid = $page + 1;
	}
	
	$previousurl = calindexurl($params['baseurl'], $previousid, array(), $params['firstpage']);
	$nexturl = calindexurl($params['baseurl'], $nextid, array(), $params['firstpage']);
	$lasturl = calindexurl($params['baseurl'], $params['maxpage'], array(), $params['firstpage']);
	$firsturl = calindexurl($params['baseurl'], 1, array(), $params['firstpage']);
	
	$previousurl = calrelativeurl($currenturl, $previousurl);
	$nexturl = calrelativeurl($currenturl, $nexturl);
	$lasturl = calrelativeurl($currenturl, $lasturl);
	$firsturl = calrelativeurl($currenturl, $firsturl);
	
	
	$previous = str_replace('[url]', $previousurl, $previous);
	$next = str_replace('[url]', $nexturl, $next);
	$last = str_replace('[url]', $lasturl, $last);
	$first = str_replace('[url]', $firsturl, $first);
	$datas = array();
	$datas[0]['total'] = $total;
	$datas[0]['totalpage'] = $params['maxpage'];
	$datas[0]['paging'] = $paging;
	$datas[0]['previous'] = $previous;
	$datas[0]['next'] = $next;
	$datas[0]['first'] = $first;
	$datas[0]['last'] = $last;
	$params['template'] = $params['paging'];
	$html = renderdata($datas, $params);
	return operatehtml($html, $params, $starttime);
}

function calindexurl($baseurl, $page, $replaces, $firstpage = '') {
	if($page < 1) $page = 1;
	$url = $baseurl;
	if($page == 1 && $firstpage != '') $url = $firstpage;
	$url = str_replace('[page]', $page, $url);
	foreach($replaces as $r => $t) {
		$url = str_replace("[$r]", rawurlencode($t), $url);
	}
	return $url;
}

function setcachedata($params, $data) {
	$cachefile = getcachefile($params);
	writetofile(serialize($data), $cachefile);
}

function getcachedata($params) {
	global $thetime;
	if($params['expire'] <= 0) return '';
	$cachefile = getcachefile($params);
	if(is_readable($cachefile)) {
		if($thetime - ak_filetime($cachefile) > $params['expire']) {
			touch($cachefile);
			return '';
		}
		$data = unserialize(readfromfile($cachefile));
		if($data == false) return '';
		return $data;
	} else {
		return '';
	}
}

function getcachefile($params) {
	$key = ak_md5(serialize($params), 1);
	$cachelayer = 2;
	if(isset($params['cachelayer']) && a_is_int($params['cachelayer'])) $cachelayer = $params['cachelayer'];
	return AK_ROOT.'cache/getdata/'.generatefilename($key, $cachelayer);
}

function recursiontemplate($params, $data, $template = '') {
	if($template == '') $template = $params['template'];
	$pos1 = strpos($template, '<#');
	if($pos1 === false) return $template;
	$pos2 = strpos($template, '#>');
	$recursion = substr($template, $pos1 + 2, $pos2 - $pos1 - 2);
	$fields = explode('(#)', $recursion);
	$function = $fields[0];
	if(templatefunctionexists($function)) {
		$recursionparams = array('return' => 1);
		for($i = 1; $i < count($fields); $i ++) {
			$_p1 = strpos($fields[$i], '=');
			if($_p1 === false) continue;
			$_k = substr($fields[$i], 0, $_p1);
			$_v = substr($fields[$i], $_p1 + 1);
			foreach($data as $_k2 => $_v2) {
				$_v = str_replace("[%$_k2]", $_v2, $_v);
			}
			foreach($params as $_k3 => $_v3) {
				$_v = str_replace("[#{$_k3}]", $_v3, $_v);
			}
			$recursionparams[$_k] = $_v;
		}
		$recursionreturn = $function($recursionparams);
	} else {
		$exec = '$recursionreturn = '.$function.'(';
		for($i = 1; $i < count($fields); $i ++) {
			$_v = $fields[$i];
			foreach($data as $_k2 => $_v2) {
				$_v = str_replace("[%{$_k2}]", $_v2, $_v);
			}
			foreach($params as $_k3 => $_v3) {
				$_v = str_replace("[#{$_k3}]", $_v3, $_v);
			}
			$exec .= "'".str_replace("'", "\\'", $_v)."',";
		}
		$exec = substr($exec, 0, -1).');';
		eval($exec);
	}
	$return = substr($template, 0, $pos1).$recursionreturn;
	if(strlen($template) > $pos2 + 2) $return .= recursiontemplate($params, $data, substr($template, $pos2 + 2));
	return $return;
}

function templatefunctionexists($function) {
	global $tpl;
	if($tpl->functionexists($function)) return true;
	return false;
}
?>