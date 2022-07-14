<?php
if(!defined('CORE_ROOT')) exit;
class oauth {
	var $key;
	var $secret;
	var $requesttoken;
	var $requestsecret;
	var $accesstoken;
	var $accesssecret;
	var $callback;
	function oauth($config) {
		$this->key = $config['key'];
		$this->secret = $config['secret'];
		$this->requesttoken = $config['requesttoken'];
		$this->requestsecret = $config['requestsecret'];
		$this->accesstoken = $config['accesstoken'];
		$this->accesssecret = $config['accesssecret'];
		$this->callback = $config['callback'];
	}
	function iniparams($params) {
		global $thetime;
		$params['oauth_consumer_key'] = $this->key;
		$params['oauth_signature_method'] = 'HMAC-SHA1';
		$params['oauth_timestamp'] = $thetime;
		$params['oauth_nonce'] = md5($thetime);
		$params['oauth_version'] = '1.0';
		if(!empty($this->accesstoken)) {
			$params['oauth_token'] = $this->accesstoken;
		} elseif(!empty($this->requesttoken)) {
			$params['oauth_token'] = $this->requesttoken;
		}
		$params['oauth_signature'] = $this->calsignature($params);
		return $params;
	}
	function calsignature($params) {
		$basestring = $this->calbasestring($params);
		if(empty($this->accesssecret)) {
			$key_parts = array($this->secret, $this->requestsecret);
		} else {
			$key_parts = array($this->secret, $this->accesssecret);
		}
		$key_parts = urlencode_rfc3986($key_parts);
		$key = implode('&', $key_parts);
		$hmac = ak_hmac('sha1', $basestring, $key, true);
		return base64_encode($hmac);
	}
	function calbasestring($params) {
		$method = $params['method'];
		$url = urlencode_rfc3986($params['url']);
		$pairs = array();
		uksort($params, 'strcmp');
		foreach($params as $k => $v) {
			if($k == 'url') continue;
			if($k == 'method') continue;
			$v = urlencode_rfc3986($v);
			if(is_array($v)) {
				natsort($v);
				foreach ($v as $duplicate_value) { 
					$pairs[] = $parameter . '=' . $duplicate_value; 
				}
			} else {
				$pairs[] = $k . '=' . $v; 
			} 
		}
		$paramstring = urlencode_rfc3986(implode('&', $pairs));
		return $method.'&'.$url.'&'.$paramstring;
	}
	function calurl($params) {
		$url = $params['url'];
		unset($params['url']);
		unset($params['method']);
		$parts = array();
		foreach($params as $k => $v) {
			$parts[] = "$k=".urlencode_rfc3986($v);
		}
		return $url.'?'.implode('&', $parts);
	} 
}
?>