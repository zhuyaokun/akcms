<?php
if(!defined('CORE_ROOT')) exit;
class user{
	var $uid = 0;
	var $username = 'guest';
	function user() {
		global $db, $codekey, $onlineip;
		$this->ck = $ck = ak_md5($codekey.$onlineip, 1);
		$ckvalue = akgetcookie($ck);
		if(empty($ckvalue)) return;
		list($sid, $uid, $username) = explode("\t", authcode($ckvalue, 'DECODE'));
		if(empty($uid)) return;
		$session = $db->get_by('*', 'sessions', "sid='$sid'");
		if($session === false) {
			$session = $this->addsession($sid, $uid);
			if($session === false) return;
		} else {
		}
		if($uid != $session['uid']) {
			aksetcookie($ck, '');
			return;
		}
		$this->sid = $sid;
		$this->uid = $uid;
		$this->username = $username;
		$this->session = $session;
	}
	
	function addsession($sid, $uid) {
		global $db;
		$user = $db->get_by('*', 'users', "id='$uid'");
		if($user === false) return false;
		$session = array(
			'sid' => $sid,
			'uid' => $uid,
			'username' => calusername($user['username']),
			'msg' => 0,
			'newmsg' => 0,
			'orders' => 0,
			'money' => $user['money'],
			'groupid' => $user['groupid']
		);
		if($db->get_by('*', 'sessions', "sid='$sid'")) {
			$db->update('sessions', $session, "sid='$sid'");
		} else {
			$db->insert('sessions', $session);
		}
		return $session;
	}
	function verifypassword($username, $password, $logintype) {
		global $db;
		$username = $db->addslashes($username);
		if($logintype == 'both') {
			if(strpos($username, '@') !== false) {
				$where = "email='$username'";
			} else {
				$where = "username='$username'";
			}
		} elseif($logintype == 'email') {
			$where = "email='$username'";
		} elseif($logintype == 'username') {
			$where = "username='$username'";
		} else {
			return false;
		}
		$user = $db->get_by('*', 'users', $where);
		if(empty($user)) return 2;
		if(!empty($user['freeze'])) return 3;
		if(!empty($user['unverified'])) return 4;
		if($user['password'] != ak_md5($password, 1, 2)) return 1;
		return $this->userinfo($user);
	}
	function changesetting($settings) {
		if(empty($this->uid)) return false;
		global $db;
		$data = $db->get_by('data', 'users', "id='".$this->uid."'");
		$data = unserialize($data);
		foreach($settings as $k => $v) {
			if(in_array($k, array('city', 'qq', 'a1', 'a2', 'a3'))) continue;
			$data[$k] = $v;
			unset($settings[$k]);
		}
		$settings['data'] = serialize($data);
		$db->update('users', $settings, "id=".$this->uid);
	}
	function changepassword($password, $uid = 0) {
		if(empty($this->uid) && empty($uid)) return false;
		if(empty($uid)) $uid = $this->uid;
		changeuserpassword($uid, $password);
		$this->login();
	}
	function userinfo($user = array()) {
		if(empty($user)) {
			global $db;
			$user = $db->get_by('*', 'users', "username='".$this->username."'");
			$data = unserialize($user['data']);
			foreach($data as $k => $v) {
				if(!isset($user[$k])) $user[$k] = $v;
			}
		}
		$user['originalusername'] = $user['username'];
		$user['username'] = calusername($user['username']);
		return $user;
	}
	function login($uid = 0, $username = '', $expire = 0) {
		global $thetime, $db, $onlineip;
		if(empty($uid) && empty($this->uid)) return false;
		if($username == '' && !isset($this->username)) return false;
		if(empty($uid)) $uid = $this->uid;
		if($username == '') $username = $this->username;
		$db->update('users', array('logintime' => $thetime, 'ip' => $onlineip), "id='$uid'");
		$sid = random(6);
		$cookie = authcode($sid."\t".$uid."\t".$username, 'ENCODE');
		if($expire > 0) {
			aksetcookie($this->ck, $cookie, $thetime + 24 * 3600 * $expire);
		} else {
			aksetcookie($this->ck, $cookie);
		}
		$this->username = $username;
	}
	function logout() {
		global $db;
		aksetcookie($this->ck, '');
		if(isset($this->sid)) $db->delete('sessions', "sid='".$this->sid."'");
	}
	function register($username, $email, $password, $data = array(), $fields = array()) {
		global $db, $thetime, $onlineip;
		if($db->get_by('*', 'users', "username='".$db->addslashes($username)."'")) return array('errno' => 1);
		if($db->get_by('*', 'users', "email='".$db->addslashes($email)."'")) return array('errno' => 2);
		$value = array(
			'username' => $username,
			'email' => $email,
			'password' => ak_md5($password, 1, 2),
			'ip' => $onlineip,
			'createtime' => $thetime
			);
		if(isset($data['qq'])) $value['qq'] = $data['qq'];
		if(isset($data['city'])) $value['city'] = $data['city'];
		unset($data['qq'], $data['city']);
		$value['data'] = serialize($data);
		foreach($fields as $k => $v) {
			$value[$k] = $v;
		}
		$db->insert('users', $value);
		return $db->insert_id();
	}
	function usernameexist($username) {
		global $db;
		if($db->get_by('*', 'users', "username='".$db->addslashes($username)."'")) return 1;
	}
	function getreseturl($email) {
		global $db, $thetime, $codekey;
		$user = $db->get_by('*', 'users', "email='$email'");
		if(empty($user)) return false;
		$expire = $thetime + 86400;
		$verify = md5($codekey."\t".$email."\t".$expire);
		return "?action=verifyresetpassword&expire=$expire&email=$email&verify=$verify";
	}
	function verifyreseturl($email, $expire, $verify) {
		global $codekey, $thetime;
		if($thetime >= $expire) return false;
		if($verify != md5($codekey."\t".$email."\t".$expire)) return false;
		return true;
	}
	function resetpasswordbyemail($password, $email) {
		global $db;
		return $db->update('users', array('password' => md5($password)), "email='".$db->addslashes($email)."'");
	}
}

function calusername($username) {
	$p = strpos($username, '@');
	if($p !== false) return substr($username, 0, $p);
	return $username;
}
?>