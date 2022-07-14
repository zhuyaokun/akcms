<?php
if(!defined('CORE_ROOT')) exit;
function preparesetask($id, $rebuild = 0) {
	global $db;
	$ses = getcache('ses');
	$se = $ses[$id];
	$starttime = -1;
	if(empty($rebuild) && !empty($se['data']['lastupdate'])) $starttime = $se['data']['lastupdate'];
	if(!empty($rebuild)) {
		deleteindex($id);
		$db->update('keywords', array('num' => 0), "sid='$id'");
	}
	$query = $db->query_by('id', 'items', "lastupdate>'$starttime' AND ".$se['data']['where'], "id DESC");
	$ids = array();
	deletetask('buildindex_'.$id);
	deletetask('write_index_log'.$id);
	deletetask('index_limit'.$id);
	deletetask('index_keywords'.$id);
	setcache('build_index_level'.$id, 0);
	while($item = $db->fetch_array($query)) {
		$ids[] = $item['id'];
		if(count($ids) > 1000) {
			addtasks('buildindex_'.$id, $ids);
			$ids = array();
		}
	}
	addtasks('buildindex_'.$id, $ids);
}

function getseorders($se) {
	$orderby = explode(',', $se['data']['orderby']);
	$orders = array();
	foreach($orderby as $order) {
		$order = trim($order);
		if($order == '') continue;
		$length = 4;
		if(strpos($order, ':') !== false) list($order, $length) = explode(':', $order);
		$orders[$order] = $length;
	}
	return $orders;
}

function operatesetask($id, $per = 20) {
	global $db, $thetime;
	$ses = getcache('ses');
	$se = $ses[$id];
	$orders = getseorders($se);
	$keywordsnum = empty($se['data']['keywordsnum']) ? $se['data']['itemnum'] : max($se['data']['keywordsnum'], $se['data']['itemnum']);
	$stagepercent = number_format(($se['data']['itemnum'] * 100) / ($se['data']['itemnum'] + 10 * $keywordsnum), 2);
	$level = getcache('build_index_level'.$id);
	if(empty($level) || $level == 1) {
		$startpercent = 0;
		$endpercent = $stagepercent;
		$fields = explode(',', $se['data']['field']);
		$itemfields = $_itemfields = $extfield = array();
		foreach($fields as $field) {
			$field = trim($field);
			if(in_array($field, array('title', 'aimurl', 'shorttitle', 'author', 'source', 'keywords', 'digest'))) {
				$itemfields[] = $field;
			} elseif(substr($field, 0, 1) == '_') {
				$extfield[] = $field;
			} elseif($field == 'text') {
				$iftext = 1;
			}
		}
		$_itemfields = $itemfields;
		foreach($orders as $k => $v) {
			if($k == 'count') continue;
			if(substr($k, 0, 1) == '_') {
				if(!in_array($k, $extfield)) $extfield[] = $k;
			} else {
				if(!in_array($k, $itemfields)) $itemfields[] = $k;
			}
		}
		$strfield = 'id,dateline';
		if(!empty($itemfields)) $strfield .= ','.implode(',', $itemfields);
		$task = gettask('buildindex_'.$id, $per);
		if(empty($task)) {
			$percent = $endpercent;
			$query = $db->query_by('keyword', 'keywords', "latestupdate=-1 AND sid=$id AND num>0");
			while($row = $db->fetch_array($query)) {
				addtask('index_keywords'.$id, $row['keyword']);
			}
			setcache('build_index_level'.$id, 2);
		} else {
			if(!is_array($task)) $task = array($task);
			$ids = implode(',', $task);
			if(empty($se['data']['separator'])) $keywords = readkeywords($id);
			$query = $db->query_by($strfield, 'items', "id IN ($ids)");
			while($item = $db->fetch_array($query)) {
				$indexbuffer = array();
				$fulltext = '';
				foreach($_itemfields as $f) {
					$fulltext .= $item[$f].',';
				}
				if(isset($iftext)) {
					$fulltext .= $db->get_by('text', 'texts', "itemid='{$item['id']}'").',';
				}
				if(!empty($extfield)) {
					$ext = $db->get_by('value', 'item_exts', "id='{$item['id']}'");
					$extvalue = unserialize($ext);
					foreach($extfield as $f) {
						$fulltext .= $extvalue[$f].',';
					}
				}
				if(empty($se['data']['separator'])) {
					foreach($keywords as $keyword) {
						if(strpos($fulltext, $keyword) !== false) {
							$count = substr_count($fulltext, $keyword);
							$buffer = array(
								'keyword' => $keyword,
								'itemid' => $item['id'],
								'count' => $count,
								'time' => $item['dateline']
							);
							foreach($orders as $order) {
								$buffer[$order] = $item[$order];
							}
							$indexbuffer[] = $buffer;
							$db->update('keywords', array('num' => '+1', 'latestupdate' => -1), "sid='$id' AND keyword='".addslashes($keyword)."'");
						}
					}
				} else {
					$fulltext = tidyitemlist($fulltext, ',', 0);
					$words = explode($se['data']['separator'], $fulltext);
					$words = array_count_values($words);
					foreach($words as $word => $count) {
						$l = strlen($word);
						if($l < 3 || $l > 28) continue;
						$db->update('keywords', array('num' => '+1', 'latestupdate' => -1), "sid='$id' AND keyword='".addslashes($word)."'");
						if($db->affectedrows() == 0) {
							$db->insert('keywords', array('num' => 1, 'sid' => $id, 'keyword' => $word, 'latestupdate' => -1, 'initial' => getinitial($word)));
						}
						$buffer = array(
							'keyword' => $word,
							'itemid' => $item['id'],
							'count' => $count,
							'time' => $item['dateline']
						);
						foreach($orders as $k => $v) {
							if($k == 'count') continue;
							$buffer[$k] = $item[$k];
						}
						$indexbuffer[] = $buffer;
					}
					refreshkeywordsnum($id);
				}
				writeindexs($se, $indexbuffer);
			}
			$percent = number_format($endpercent / 100 * gettaskpercent('buildindex_'.$id), 2);
		}
	} elseif($level == 2) {
		$startpercent = $stagepercent;
		$endpercent = 100;
		if(empty($orders)) return $endpercent;
		$keywords = gettask('index_keywords'.$id, $per);
		if(empty($keywords)) {
			touchse($id);
			$percent = 100;
		} else {
			if(!is_array($keywords)) $keywords = array($keywords);
			foreach($keywords as $keyword) {
				sortindex($se, $keyword);
				$db->update('keywords', array('latestupdate' => $thetime), "sid='$id' AND keyword='".addslashes($keyword)."'");
			}
			$percent = number_format($startpercent + ($endpercent - $startpercent) / 100 * gettaskpercent('index_keywords'.$id), 2);
			if($percent == '100.00') $percent = '99.99';
		}
	}
	return $percent;
}

function writeindexs($se, $data) {
	$orders = getseorders($se);
	$linelength = array_sum($orders) + 3;
	$keywords = array();
	foreach($data as $d) {
		$keywords[$d['keyword']] = inttobin($d['itemid'], 3);
		foreach($orders as $order => $length) {
			$keywords[$d['keyword']] .= inttobin($d[$order], $length);
		}
	}
	unset($data);
	foreach($keywords as $keyword => $value) {
		if(strlen($value) % $linelength != 0) aklog($keyword, AK_ROOT.'logs/size.error');
		$index = hashindex($se, $keyword);
		ak_touch($index);
		if($fp = fopen($index, 'ab')) {
			flock($fp, LOCK_EX);
			fwrite($fp, $value);
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}
}

function sortindex($se, $keyword) {
	global $db;
	$batchnum = 1000;
	$index = hashindex($se, $keyword);
	if(!file_exists($index)) return false;
	$indexsize = filesize($index);
	$orders = getseorders($se);
	if(empty($orders)) return true;
	$linelength = array_sum($orders) + 3;
	if($indexsize > $batchnum * $linelength) {
		if($fp = fopen($index, 'rb')) {
			$i = 0;
			$batchdata = array();
			while(!feof($fp)) {
				$line = fread($fp, $linelength);
				if(empty($line)) break;
				$itemid = bintoint(substr($line, 0, 3));
				$offset = 3;
				foreach($orders as $order => $length) {
					$batchdata[$order][$itemid] =  bintoint(substr($line, $offset, $length));
					$offset += $length;
				}
				$i ++;
				if($i % $batchnum == 0 || $i == $indexsize / $linelength) {
					foreach($batchdata as $order => $data) {
						$sorted = '';
						arsort($data);
						foreach($data as $k => $v) {
							$sorted .= inttobin($k, 3).inttobin($v, $orders[$order]);
						}
						writetofile($sorted, "$index.$order.".ceil($i / $batchnum));
						$sorted = '';
					}
					$batchdata = array();
				}
			}
			fclose($fp);
			$maxindexid = ceil($i / $batchnum);
			$fp = $values = $itemids = array();
			for($j = 1; $j <= $maxindexid; $j ++) {
				foreach($orders as $order => $length) {
					if(!isset($fp[$j][$order])) $fp[$j][$order] = fopen("$index.$order.$j", 'rb');
					$line = fread($fp[$j][$order], $length + 3);
					$itemid = bintoint(substr($line, 0, 3));
					$_str = substr($line, 3, $length);
					$values[$order][$j] = bintoint($_str);
					$itemids[$j] = $itemid;
				}
			}
			foreach($orders as $order => $length) {
				$i = 1;
				$sortindex = hashindex($se, $keyword, $order);
				$sortfp = fopen($sortindex, 'wb');
				while(1) {
					if(empty($values[$order])) break;
					$max = max($values[$order]);
					$key = array_search($max, $values[$order]);
					fwrite($sortfp, inttobin($itemids[$key], 3));
					$i ++;
					if($line = fread($fp[$key][$order], $length + 3)) {
						$values[$order][$key] = bintoint(substr($line, 3));
					} else {
						unset($values[$order][$key]);
						fclose($fp[$key][$order]);
						unlink("$index.$order.$key");
					}
				}
				fclose($sortfp);
			}
		}
	} else {
		if($fp = fopen($index, 'rb')) {
			$i = 0;
			$batchdata = array();
			while(!feof($fp)) {
				$line = fread($fp, $linelength);
				if(empty($line)) break;
				$itemid = bintoint(substr($line, 0, 3));
				$offset = 3;
				foreach($orders as $order => $length) {
					$batchdata[$order][$itemid] =  bintoint(substr($line, $offset, $length));
					$offset += $length;
				}
			}
			fclose($fp);
			foreach($batchdata as $order => $data) {
				$countsortindex = hashindex($se, $keyword, $order);
				arsort($data);
				$sorted = '';
				foreach($data as $k => $v) {
					$sorted .= inttobin($k, 3);
				}
				writetofile($sorted, $countsortindex);
			}
		}
	}
	$db->update('keywords', array('flag' => 0), "keyword='".$db->addslashes($keyword)."' AND sid='{$se['id']}'");
}

function readindex($sid, $keywords, $offset = 0, $num = 0) {
	$return = array();
	$ses = getcache('ses');
	$se = $ses[$sid];
	foreach($keywords as $keyword) {
		$index = hashindex($se, $keyword);
		if(!file_exists($index)) return $return;
		if($fp = fopen($index, 'rb')) {
			if($offset > 0) fseek($fp, $offset * 8);
			$i = 0;
			while(!feof($fp)) {
				$line = fread($fp, 8);
				if(empty($line)) continue;
				$itemid = bintoint(substr($line, 0, 3));
				$count = bintoint(substr($line, 3, 1));
				$dateline = bintoint(substr($line, 4));
				$return[$keyword][] = array(
					'itemid' => $itemid,
					'count' => $count,
					'time' => $dateline
				);
				$i ++;
				if($num > 0 && $i >= $num) break;
			}
			fclose($fp);
		}
	}
	return $return;
}

function readsortedindex($se, $keywords, $orderby, $offset = 0, $num = 0) {
	if($orderby == '') {
		$orders = getseorders($se);
		$linelength = array_sum($orders) + 3;
	} else {
		$linelength = 3;
	}
	$return = array();
	if(count($keywords) == 1) {
		$keyword = current($keywords);
		$index = hashindex($se, $keyword, $orderby);
		if(!file_exists($index)) return false;
		$return['count'] = filesize($index) / $linelength;
		if($fp = fopen($index, 'rb')) {
			if($offset > 0) fseek($fp, $offset * $linelength);
			$i = 0;
			while(!feof($fp)) {
				$line = fread($fp, $linelength);
				if(empty($line)) continue;
				$return['value'][] = bintoint(substr($line, 0, 3));
				$i ++;
				if($num > 0 && $i >= $num) break;
			}
			fclose($fp);
		}
	} else {
		foreach($keywords as $keyword) {
			$indexfile[$keyword] = hashindex($se, $keyword, $orderby);
			if(!file_exists($indexfile[$keyword])) {
				return array('count' => 0,'value' => array());
			}
		}
		foreach($keywords as $keyword) {
			$_index = array();
			$fp = fopen($indexfile[$keyword], 'rb');
			while(!feof($fp)) {
				$line = fread($fp, $linelength);
				if(empty($line)) continue;
				$_index[] = bintoint(substr($line, 0, 3));
			}
			fclose($fp);
			if(!isset($index)) {
				$index = $_index;
			} else {
				$index = array_intersect($index, $_index);
			}
		}
		$return = array('count' => count($index), 'value' => array_slice($index, $offset, $num));
	}
	return $return;
}

function readindexcount($se, $keyword) {
	$index = hashindex($se, $keyword);
	if(!file_exists($index)) return 0;
	return filesize($index) / 8;
}

function inttobin($int, $length = 0) {
	$i = $int;
	if($length == 1 && $int > 255) $int = 255;
	if($length == 2 && $int > 65535) $int = 65535;
	if($length == 3 && $int > 16777215) $int = 16777215;
	$return = '';
	while($i > 0) {
		$return = chr($i % 256).$return;
		$i = floor($i / 256);
	}
	if($length > 0 && $length > strlen($return)) {
		$return = str_repeat(chr(0), $length - strlen($return)).$return;
	}
	if($length > 0 && $length < strlen($return)) $return = substr($return, 0, $length);
	return $return;
}

function bintoint($str) {
	$len = strlen($str);
	$return = 0;
	for($i = 0; $i < $len; $i ++) {
		$return = $return * 256 + ord($str[$i]);
	}
	return $return;
}

function hashindex($se, $keyword, $type = '') {
	$md5 = md5($keyword);
	$path = '';
	for($i = 0; $i < 2; $i ++) {
		$path .= $md5[$i].'/';
	}
	$basepath = calindexpath($se);
	if($type == '') {
		return $basepath.'/'.$path.$md5.'.aki';
	} else {
		return $basepath.'/'.$path.$md5.".{$type}.aki";
	}
}

function calindexpath($se) {
	$basepath = str_replace('\\', '/', $se['data']['path']);
	if(substr($basepath, 0, 1) != '/' && strpos($basepath, ':') === false) $basepath = AK_ROOT.'index/'.$basepath;
	if(substr($basepath, -1) == '/') $basepath = substr($basepath, 0, -1);
	return $basepath;
}

function deleteindex($sid) {
	global $se, $thetime;
	if($se['id'] != $sid) {
		$ses = getcache('ses');
		$secache = $ses[$sid];
	} else {
		$secache = $se;
	}
	$indexpath = calindexpath($secache);
	@rename($indexpath, $indexpath.'/../indexbak-'.$thetime);
}

function readkeywords($sid) {
	global $db;
	$keywords = array();
	$query = $db->query_by('keyword', 'keywords', "sid='$sid'");
	while($k = $db->fetch_array($query)) {
		$keywords[] = $k['keyword'];
	}
	return $keywords;
}

function touchse($id, $time = 0) {
	global $db, $thetime;
	$se = $db->get_by('*', 'ses', "id='$id'");
	$value = unserialize($se['value']);
	$value['lastupdate'] = $thetime;
	$value = serialize($value);
	$db->update('ses', array('value' => $value), "id='$id'");
	updatecache('ses');
}

function refreshkeywordsnum($sid) {
	global $db;
	$num = $db->get_by('count(*)', 'keywords', "sid='$sid'");
	updatesedata($sid, array('keywordsnum' => $num));
}

function updatesedata($sid, $data) {
	global $db;
	$ses = getcache('ses');
	$se = $ses[$sid];
	$value = ak_unserialize($se['value']);
	if(empty($value)) return false;
	foreach($data as $k => $v) {
		if(!isset($value[$k]) || $value[$k] != $v) {
			$value[$k] = $v;
			$change = 1;
		}
	}
	if(!empty($change)) {
		$value = serialize($value);
		$db->update('ses', array('value' => $value), "id='$sid'");
	}
	updatecache('ses');
}
?>