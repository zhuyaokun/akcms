<?php
if(!defined('CORE_ROOT')) exit;
if(empty($pricefield)) $pricefield = 'price';
function buy($goodsid, $num, $uid = 0) {
	global $pricefield, $db;
	if(empty($uid)) $uid = 1;//debug
	$goods = $db->get_by('*', 'items', "id='$goodsid'");
	$price = $goods[$pricefield];
	$amount = $price * $num;
	$user = $db->get_by('money,freeze', 'users', "id='$uid'");
	$money = $user['money'];
	if($money < $amount) return -1;
	addmoney($uid, $amount * -1);
	return 0;
}

function payorder($orderid = 0) {
	global $pricefield, $db, $user;
	$uid = $user->uid;
	if($orderid == 0) {
		$order = $db->get_by('*', 'orders', "pay=0 AND uid=$uid");//当前登录用户的订单
	} else {
		$order = $db->get_by('*', 'orders', "id='$orderid'");
	}
	if(empty($order) || !empty($order['pay'])) return false;
	$orderid = $order['id'];
	$data = unserialize($order['data']);
	$goods = $data['goods'];
	$amount = 0;
	foreach($goods as $k => $v) {
		$goodsinfo = $db->get_by('*', 'items', "id='$k'");
		$amount += $goodsinfo['price'] * $v;
	}
	$db->update('orders', array('pay' => 1), "id='$orderid'");
	addmoney($uid, $amount * -1);
}

function getcart() {
	global $db, $user;
	if(empty($user)) return false;
	$uid = $user->uid;
	$order = $db->get_by('*', 'orders', "uid='$uid' AND pay=0");
	if(empty($order)) return false;
	$data = unserialize($order['data']);
	$keys = array_keys($data['goods']);
	if(!empty($keys)) {
		$keys = implode(',', $keys);
		$query = $db->query_by('*', 'items', "id IN ($keys)");
		$goods = array();
		while($g = $db->fetch_array($query)) {
			$g['num'] = $data['goods'][$g['id']];
			$goods[$g['id']] = $g;
		}
	}
	unset($order['data']);
	$order['goods'] = $goods;
	return $order;
}

function addmoney($uid, $money) {
	global $db;
	$user = $db->get_by('*', 'users', "id='$uid'");
	$db->update('users', array('money' => $user['money'] + $money), "id='$uid'");
}

function calorderamount($id, $order = array()) {
	global $db;
	if(empty($order)) $order = $db->get_by('*', 'orders', "id='$id'");
	debug($order);
}

function addtoorder($goodsdata, $uid = 0) {
	global $db, $user;
	$setting_nocart = 1;
	if($uid == 0 && empty($user->uid)) return false;
	if(empty($uid)) $uid = $user->uid;
	$order = $db->get_by('*', 'orders', "uid='$uid' AND pay='0'");
	if($order === false) {
		$goods = $goodsdata;
		$data = array('goods' => $goods);
		$value = array('uid' => $uid, 'data' => serialize($data));
		$db->insert('orders', $value);
	} else {
		$id = $order['id'];
		if(empty($setting_nocart)) {
			$data = unserialize($order['data']);
			$goods = $data['goods'];
		} else {
			$goods = array();
		}
		foreach($goodsdata as $k => $v) {
			if(isset($goods[$k])) {
				$goods[$k] += $v;
			} else {
				$goods[$k] = $v;
			}
		}
		$data['goods'] = $goods;
		$value = array('data' => serialize($data));
		$db->update('orders', $value, "id='$id'");
	}
}
?>