<?php
function operatehtml($html, $params, $starttime) {
	global $ifdebug;
	$endtime = akmicrotime();
	if(empty($params['noelapse']) && (!empty($ifdebug) || !empty($params['elapse']))) $html.='<!--'.msformat($endtime - $starttime).'-->';
	if(!empty($params['return'])) {
		return $html;
	} elseif(!empty($params['assign'])) {
		if(!empty($params['explode'])) $html = explode($params['explode'], $html);
		$GLOBALS['html_smarty']->_tpl_vars[$params['assign']] = $html;
	} else {
		echo $html;
	}
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
	}
	$j = 0;
	$datas = array();
	foreach($items as $item) {
		$j ++;
		$c = $item['category'];
		if(!isset($categories[$c])) $categories[$c] = getcategorycache($c);
		list($item['y'], $item['m'], $item['d'], $item['h'], $item['i'], $item['s'], $item['sy'], $item['sm'], $item['sd'], $item['F'], $item['M'], $item['l'], $item['D'], $item['r']) = explode(' ', date('Y m d H i s y n j F M l D r', $item['dateline']));
		if(strpos($params['template'], 'last_') !== false) {
			if($item['lastupdate'] == 0) $item['lastupdate'] = $item['dateline'];
			list($item['last_y'], $item['last_m'], $item['last_d'], $item['last_h'], $item['last_i'], $item['last_s'], $item['last_sy'], $item['last_sm'], $item['last_sd']) = explode(' ', date('Y m d H i s y n j', $item['lastupdate']));
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
		$item['itemid'] = $item['id'];
		$item['aimurl'] = str_replace('[home]', $homepage, $item['aimurl']);
		$item['id'] = $j;
		$item['realid'] = $j + $params['start'] - 1;
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
		if(empty($item['picture'])) $item['picture'] = $params['nopicture'];
		if(strpos($params['template'], '[author_encode]') !== false) {
			$item['author_encode'] = urlencode($item['author']);
		}
		$_item = array();
		foreach($params['fields'] as $k) {
			if(strpos($k, 'picture') === false) {
				$_p = strpos($k , ':');
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
	if(empty($params['sid'])) {
		$sql_where = '1';
		$leftjoin = '';
		if(!empty($setting_ifdraft)) {
			$sql_where .= " AND draft=0";
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
			$sql_where .= " AND id>'{$params['last']}'";
		}
		if($params['next'] != 0) {
			$sql_where .= " AND id<'{$params['next']}'";
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
		if(!empty($params['initial'])) {
			$sql_where .= " AND initial='".strtolower($params['initial'])."'";
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
	} else {
		if(empty($params['keywords'])) {
			$inids = array();
		} else {
			require_once(CORE_ROOT.'include/se.func.php');
			$sid = $params['sid'];
			$ses = getcache('ses');
			$se = $ses[$sid];
			$keywords = trim($params['keywords']);
			if($_offset = strpos($keywords, ',')) $keywords = substr($keywords, 0, $_offset);
			if($_offset = strpos($keywords, '/')) $keywords = substr($keywords, 0, $_offset);
			$keywords = explode(' ', $keywords);
			if(count($keywords) == 1) {
				$index = readsortedindex($se, $keywords, $params['orderby'], $params['start'], $params['num']);
				$count = $index['count'];
				$index = $index['value'];
			} else {
				$index = readsortedindex($se, $keywords, $params['orderby'], $params['start'], $params['num']);
				$count = $index['count'];
				$index = $index['value'];
			}
			if(!empty($params['bandindex'])) {
				$GLOBALS['index'.$params['bandindex'].'count'] = $count;
				$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
			}
			if(empty($index)) $index = array();
			$GLOBALS['inids'] = $inids = $index;
			$sqlorderby = '';
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
		list($comment['y'], $comment['m'], $comment['d'], $comment['h'], $comment['i'], $comment['s'], $comment['sy'], $comment['sm'], $comment['sd']) = explode(' ', date('Y m d H i s y n j', $comment['dateline']));
		list($comment['ry'], $comment['rm'], $comment['rd'], $comment['rh'], $comment['ri'], $comment['rs'], $comment['rsy'], $comment['rsm'], $comment['rsd']) = explode(' ', date('Y m d H i s y n j', $comment['reviewtime']));
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
	$sqlorderby = order_operate($params['orderby'], 'attachments');
	$count = $db->get_by('COUNT(*) as c', 'attachments', $sql_where);
	if(!empty($params['bandindex'])) {
		$GLOBALS['index'.$params['bandindex'].'count'] = $count;
		$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
	}
	return "SELECT * FROM {$tablepre}_attachments WHERE {$sql_where} ORDER BY {$sqlorderby} LIMIT {$params['start']},{$params['num']}";
}

function getkeywords($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('keywords', $params);
	if(isset($cache_memory[$params['cachekey']])) {
		$html = $cache_memory[$params['cachekey']];
	} elseif(!$html = getcachedata($params)) {
		$datas = getkeywordsdata($params);
		$html = renderdata($datas, $params);
		if(!empty($GLOBALS['batchcreateitemflag']) && empty($params['nocache'])) $cache_memory[$params['cachekey']] = $html;
		if($params['expire'] > 0) setcachedata($params, $html);
	}
	return operatehtml($html, $params, $starttime);
}

function getkeywordsdata($params) {
	global $db, $homepage;
	$sid = $params['sid'];
	$se = $db->get_by('*', 'ses', "id=$sid");
	$value = ak_unserialize($se['value']);
	$sql = getkeywordssql($params);
	$query = $db->query($sql);
	$keywords = array();
	while($keyword = $db->fetch_array($query)) {
		$keywords[$keyword['id']] = $keyword;
	}
	$j = 0;
	$datas = array();
	foreach($keywords as $keyword) {
		$j ++;
		$variables['keyword'] = urlencode($keyword['keyword']);
		$keyword['url'] = $homepage.calstoremethod($value['storemethod'], $variables);
		$keyword['keyword_url'] = rawurlencode($keyword['keyword']);
		$keyword['keyword_html'] = ak_htmlspecialchars($keyword['keyword']);
		$keyword['kid'] = $keyword['id'];
		$keyword['id'] = $j;
		$keyword['realid'] = $j + $params['start'] - 1;
		$datas[] = $keyword;
	}
	return $datas;
}

function getkeywordssql($params) {
	global $tablepre, $dbtype, $db;
	$params['start'] = max(0, $params['start'] - 1);
	$sql_where = "sid='{$params['sid']}'";
	if(isset($params['keywords'])) {
		$keywords = tidyitemlist($params['keywords'], ',', 0);
		$keywords = explode(',', $keywords);
		foreach($keywords as $k => $v) {
			$keywords[$k] = "'".$db->addslashes($v)."'";
		}
		if(!empty($keywords)) {
			$keywords = implode(',', $keywords);
			$sql_where .= " AND keyword IN ($keywords)";
		}
	}
	if(!empty($params['morethan'])) {
		$sql_where .= " AND num>'{$params['morethan']}'";
	}
	if(!empty($params['initial'])) {
		$sql_where .= " AND initial='".strtolower($params['initial'])."'";
	}
	$sqlorderby = order_operate($params['orderby'], 'keywords');
	$count = $db->get_by('COUNT(*) as c', 'keywords', $sql_where);
	if(!empty($params['bandindex'])) {
		$GLOBALS['index'.$params['bandindex'].'count'] = $count;
		$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
	}
	return "SELECT * FROM {$tablepre}_keywords WHERE {$sql_where} ORDER BY {$sqlorderby} LIMIT {$params['start']},{$params['num']}";
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
	$query = $db->query($params['sql']);
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

function getuser($params) {
	global $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('user', $params);
	$datas = array();
	require_once CORE_ROOT.'include/user.class.php';
	$user = new user();
	$datas[0]['username'] = calusername($user->username);
	$datas[0]['uid'] = $user->uid;
	if(empty($user->uid)) $params['template'] = $params['offlinetemplate'];
	$html = renderdata($datas, $params);
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
		if($key == 'where' || $key == 'emptymessage' || strpos($key, 'template') !== false) continue;
		if($type == 'lists' && $key == 'list') continue;
		if($type == 'items' && $key == 'overflow') continue;
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
		$return['newinseconds'] = !empty($params['newinseconds']) && a_is_int($params['newinseconds']) ? $params['newinseconds'] : 0;//最近几秒新增
		$return['editinseconds'] = !empty($params['editinseconds']) && a_is_int($params['editinseconds']) ? $params['editinseconds'] : 0;//最近几秒修改
		$return['section'] = empty($params['section']) ? '' : tidyitemlist($params['section']);
		$return['skipsection'] = empty($params['skipsection']) ? '' : tidyitemlist($params['skipsection']);
		$return['category'] = empty($params['category']) ? '' : tidyitemlist($params['category']);
		$return['skipcategory'] = empty($params['skipcategory']) ? '' : tidyitemlist($params['skipcategory'].',0,');
		$return['id'] = empty($params['id']) ? '' : tidyitemlist($params['id']);
		$return['sid'] = (empty($params['sid']) || !a_is_int($params['sid'])) ? '' : $params['sid'];
		$return['skip'] = empty($params['skip']) ? '' : tidyitemlist($params['skip']);
		$return['timelimit'] = !empty($params['timelimit']) ? 1 : 0;
		$return['includesubcategory'] = empty($params['includesubcategory']) ? 0 : 1;//是否包含下级分类，默认不包含
		$template = empty($params['template']) ? '[title]<br>' : $params['template'];
		$return['show_text'] = strpos($template, '[text') !== false ? 1 : 0;
		$return['order'] = empty($params['order']) ? 0 : $params['order'];
		$return['keywords'] = isset($params['keywords']) ? $params['keywords'] : '';
		$return['picture'] = isset($params['picture']) ? $params['picture'] : 0;//>0带图<0不带图0随便
		$return['nopicture'] = empty($params['nopicture']) ? '' : $params['nopicture'];
		$return['last'] = empty($params['last']) ? 0 : $params['last'];
		$return['year'] = empty($params['year']) ? 0 : $params['year'];
		$return['month'] = empty($params['month']) ? 0 : $params['month'];
		$return['day'] = empty($params['day']) ? 0 : $params['day'];
		$return['next'] = empty($params['next']) ? 0 : $params['next'];
		$return['where'] = empty($params['where']) ? '' : $params['where'];
		$return['head'] = empty($params['head']) ? 255 : $params['head'];
		$return['author'] = empty($params['author']) ? '' : $params['author'];
		preg_match_all("/\[%?(_?[a-zA-Z0-9_]+(:[^\]]+)?)\]/is", $template, $match);
		$return['fields'] = $match[1];
	} elseif($type == 'index') {
		$return['id'] = empty($params['id']) ? '1' : $params['id'];
		$return['nohtml'] = !empty($params['nohtml']);
		$return['page'] = $params['page'];
		global $global_category;
		$return['ipp'] = $GLOBALS['index'.$return['id'].'ipp'];
		if(empty($return['ipp'])) $return['ipp'] = 10;
		isset($params['ipp']) && $return['ipp'] = $params['ipp'];
		$return['total'] = $GLOBALS['index'.$return['id'].'count'];
		isset($params['total']) && $return['total'] = $params['total'];
		$return['last'] = ceil($return['total'] / $return['ipp']);
		$return['keywords'] = empty($params['keywords']) ? '' : $params['keywords'];
		$return['category'] = empty($params['category']) ? '' : $params['category'];
		$return['item'] = empty($params['item']) ? '' : $params['item'];
		$baseurl = empty($params['baseurl']) ? '' : $params['baseurl'];
		if(isset($global_category)) $baseurl = str_replace('[category]', urlencode($global_category), $baseurl);
		$baseurl = str_replace('[category]', urlencode($return['category']), $baseurl);
		$return['baseurl'] = $baseurl;
		$template = empty($params['template']) ? '[indexs]' : $params['template'];
		$return['linktemplate'] = empty($params['linktemplate']) ? '[link]' : $params['linktemplate'];
		if(isset($params['firstpage'])) $return['firstpage'] = $params['firstpage'];
		foreach($params as $key => $value) {
			if(substr($key, 0, 1) == '_') $return[$key] = $value;
		}
	} elseif($type == 'paging') {
		$return['id'] = empty($params['id']) ? '1' : $params['id'];
		$return['nohtml'] = !empty($params['nohtml']);
		$return['page'] = $params['page'];
		$return['template'] = empty($params['template']) ? '[page]' : $params['template'];
		$return['currenttemplate'] = empty($params['currenttemplate']) ? '[page]' : $params['currenttemplate'];
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
	} elseif($type == 'keywords') {
		$return['sid'] = empty($params['sid']) ? '0' : $params['sid'];
		$return['morethan'] = empty($params['morethan']) ? '0' : $params['morethan'];
		$return['orderby'] = empty($return['orderby']) ? 'count' : $return['orderby'];
		$template = empty($params['template']) ? '[keyword]<br>' : $params['template'];
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
	if(!in_array($type, array('items', 'categories', 'comments', 'attachments', 'keywords', 'texts', 'lists'))) {
		return '';
	}
	if(strpos($rule, 'random') !== false) return 'random';
	$array_items_field = array(
		'id' => $tablepre.'_items.id',
		'orderby' => 'orderby',
		'orderby2' => 'orderby2',
		'orderby3' => 'orderby3',
		'orderby4' => 'orderby4',
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
		'id' => 'id'
	);
	$array_comments_field = array(
		'id' => 'id',
		'time' => 'dateline',
		'goodnum' => 'goodnum',
		'badnum' => 'badnum'
	);
	$array_attachments_field = array(
		'id' => 'id',
		'itemid' => 'itemid',
		'orderby' => 'orderby'
	);
	$array_keywords_field = array(
		'count' => 'num',
		'num' => 'num',
		'searchcount' => 'searchcount'
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
		if(isset($array_temp[1]) && $array_temp[1] == 'reverse') {
			$return .= ','.$arrayname[$array_temp[0]].' DESC';
		} else {
			$return .= ','.$arrayname[$array_temp[0]];
		}
	}
	return substr($return, 1);
}

function getindexs($params) {
	global $exe_times, $thetime, $cache_memory, $ifdebug;
	$starttime = akmicrotime();
	$params = operateparams('index', $params);
	$replaces = array(
		'item' => $params['item'],
		'category' => $params['category'],
		'keywords' => $params['keywords']
	);
	foreach($params as $key => $value) {
		if(substr($key, 0, 1) != '_') continue;
		$replaces[$key] = $value;
	}
	if(empty($params['nohtml']) && !empty($GLOBALS['index_work'])) {
		list($type, $id, $filenamebase) = explode("\n", $GLOBALS['index_work']);
		$dirbase = $filenamebase;
		if(substr($filenamebase, -1) != '/') {
			$dirbase = dirname($filenamebase);
		} else {
			$dirbase = substr($filenamebase, 0, -1);
		}
		require_once(CORE_ROOT.'include/task.file.func.php');
		for($i = 2; $i <= $params['last']; $i ++) {
			$filename = calindexurl($params['baseurl'], $i, $replaces, $params['firstpage']);
			$filename = $dirbase.'/'.$filename;
			if($filename == $filenamebase) continue;
			addtask('indextask'.$type, $id."\n".$filename."\n".$i);
		}
	}
	if(empty($params['page'])) $params['page'] = 1;
	$pre = $params['page'] - 1;
	$next = min($params['last'], $params['page'] + 1);
	$preurl = calindexurl($params['baseurl'], $pre, $replaces, $params['firstpage']);
	$nexturl = calindexurl($params['baseurl'], $next, $replaces, $params['firstpage']);
	$datas[0]['first'] = calindexurl($params['baseurl'], 1, $replaces, $params['firstpage']);
	$datas[0]['pre'] = $preurl;
	$datas[0]['next'] = $nexturl;
	$datas[0]['last'] = calindexurl($params['baseurl'], $params['last'], $replaces);
	$datas[0]['lastid'] = $params['last'];
	$_indexs = '';
	$_start = max($params['page'] - 3, 1);
	if(empty($params['ipp'])) $params['ipp'] = 10;
	$_end = min($_start + 9, ceil($params['total'] / $params['ipp']));
	if($_end == 0) return $params['emptymessage'];
	for($i = $_start; $i <= $_end; $i ++) {
		$_url = calindexurl($params['baseurl'], $i, $replaces, $params['firstpage']);
		if($params['page'] == $i) {
			$_indexs .= str_replace('[link]', "<a class=\"current\">$i</a>", $params['linktemplate']);
		} else {
			$_indexs .= str_replace('[link]', "<a href=\"{$_url}\">$i</a>", $params['linktemplate']);
		}
	}
	$datas[0]['indexs'] = $_indexs;
	$datas[0]['total'] = $params['total'];
	$html = renderdata($datas, $params);
	return operatehtml($html, $params, $starttime);
}

function getpaging($params) {
	global $exe_times, $thetime, $cache_memory, $ifdebug;
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
			$startid = $params['maxpage'] - $params['num'];
			$endid = $params['maxpage'];
		}
	}
	$paging = '';
	if(empty($params['nohtml']) && !empty($GLOBALS['index_work'])) {
		list($type, $id, $filenamebase) = explode("\n", $GLOBALS['index_work']);
		$dirbase = $filenamebase;
		if(substr($filenamebase, -1) != '/') {
			$dirbase = dirname($filenamebase);
		} else {
			$dirbase = substr($filenamebase, 0, -1);
		}
		preg_match('/<a([^>]+)href=([\'"]?)([^>\'" ]+)([\' "]?)>/is', $params['template'], $match);
		$baseurl = $match[3];
		$replaces = array();
		require_once(CORE_ROOT.'include/task.file.func.php');
		for($i = 2; $i <= $params['maxpage']; $i ++) {
			$filename = calindexurl($baseurl, $i, $replaces);
			$filename = $dirbase.'/'.$filename;
			if($filename == $filenamebase) continue;
			addtask('indextask'.$type, "$type\n$id\n".$filename."\n".$i);
		}
	}
	for($i = $startid; $i <= $endid; $i ++) {
		if($i != $page) {
			$t = $params['template'];
		} else {
			$t = $params['currenttemplate'];
		}
		if($i == 1 && !empty($params['firstpagetemplate'])) {
			$t = str_replace('[page]', $i, $params['firstpagetemplate']);
		} else {
			$t = str_replace('[page]', $i, $t);
		}
		$paging .= $t;
	}
	if($page <= 1) {
		$previous = $params['noprevioustemplate'];
		$first = $params['alreadyfirsttemplate'];
	} else {
		$first = $params['firsttemplate'];
		$previous = $params['previoustemplate'];
		$previous = str_replace('[page]', $page - 1, $previous);
	}
	if($page >= $params['maxpage']) {
		$next = $params['nonexttemplate'];
		$last = $params['alreadylasttemplate'];
	} else {
		$next = $params['nexttemplate'];
		$last = $params['lasttemplate'];
		$next = str_replace('[page]', $page + 1, $next);
		$last = str_replace('[page]', $params['maxpage'], $last);
	}
	$datas = array();
	$datas[0]['total'] = $total;
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
	return AK_ROOT.'cache/'.$params['type'].'/'.$key;
}

function recursiontemplate($params, $data, $template = '') {
	global $html_smarty;
	if($template == '') $template = $params['template'];
	$pos1 = strpos($template, '<#');
	if($pos1 === false) return $template;
	$pos2 = strpos($template, '#>');
	$recursion = substr($template, $pos1 + 2, $pos2 - $pos1 - 2);
	$fields = explode('(#)', $recursion);
	$function = $fields[0];
	if(isset($html_smarty->registered_plugins['function'][$function])) {
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
?>