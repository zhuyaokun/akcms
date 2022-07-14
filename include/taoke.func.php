<?php
if(!defined('CORE_ROOT')) exit;

function gettaobaoitems($params) {
	$tc = new taokeclient;
	global $setting_tknick;
	$page = 1;
	$size = 10;
	if(!empty($params['page'])) $page = $params['page'];
	if(!empty($params['num'])) $size = $params['num'];
	$request_params = array(
		'keyword' => $params['keywords'],
		'page_no' => $page,
		'page_size' => $size,
		'nick' => $setting_tknick,
		'fields' => 'num_iid,title,nick,pic_url,price,click_url,commission,commission_rate,commission_num,commission_volume,shop_click_url,seller_credit_score,item_location,volume'
	);
	if(isset($params['orderby'])) $request_params['sort'] = $params['orderby'];
	$result = $tc->request('taobao.taobaoke.items.get', $request_params);
	if(!empty($result['code'])) {
		eventlog($result['msg'].':'.$result['sub_msg'], 'taoke');
	}
	if(!empty($params['bandindex'])) {
		$GLOBALS['index'.$params['bandindex'].'count'] = $result['total_results'];
		$GLOBALS['index'.$params['bandindex'].'ipp'] = $params['num'];
	}
	$items = $result['taobaoke_items']['taobaoke_item'];
	$datas = array();
	$i = 1;
	if(!empty($items)) {
		foreach($items as $item) {
			$item['id'] = $i;
			$item['tid'] = $item['num_iid'];
			$item['url'] = $item['click_url'];
			$item['picture'] = $item['pic_url'];
			$item['credit'] = $item['seller_credit_score'];
			$item['shopurl'] = $item['shop_click_url'];
			unset($item['num_iid'], $item['click_url'], $item['pic_url'], $item['seller_credit_score'], $item['shop_click_url']);
			$datas[] = $item;
			$i ++;
			if($i > $params['num']) break;
		}
		$html = renderdata($datas, $params);
		if(!empty($params['return'])) return $html;
		echo $html;
	} else {
		echo 'error';
	}
}

function gettaobaoitemdata($id) {
	$tc = new taokeclient;
	$_result = $tc->request('taobao.taobaoke.items.detail.get', array('num_iids' => $id, 'fields' => 'num_iid,title,detail_url,click_url,price,nick,pic_url,shop_click_url,taobaoke_cat_click_url,keyword_click_url,prop_imgs,desc,item_imgs,freight_payer,num,post_fee,express_fee,ems_fee,stuff_status'));
	$_result = $_result['taobaoke_item_details']['taobaoke_item_detail'];
	$result = $_result['item'];
	$result['click_url'] = $_result['click_url'];
	$result['shop_click_url'] = $_result['shop_click_url'];
	$_result = $tc->request('taobao.taobaoke.items.convert', array('num_iids' => $id, 'fields' => 'volume,click_url,item_location,seller_credit_score'));
	$result['desc'] = strip_tags($result['desc'], '<img><b><strong>');
	$result['volume'] = $_result['taobaoke_items']['taobaoke_item']['volume'];
	$result['click_url'] = $_result['taobaoke_items']['taobaoke_item']['click_url'];
	$result['location'] = $_result['taobaoke_items']['taobaoke_item']['item_location'];
	$result['seller_credit_score'] = $_result['taobaoke_items']['taobaoke_item']['seller_credit_score'];
	return $result;
}

class taokeclient{
	private $appkey, $secretKey, $gatewayUrl, $format, $signMethod, $apiVersion, $sdkVersion, $nick;
	public function taokeclient() {
		global $setting_tksecrets, $setting_tknick;
		if(empty($setting_tksecrets)) return false;
		$s = explode("\n", $setting_tksecrets);
		$_k = array_rand($s, 1);
		$_s = $s[$_k];
		list($appkey, $secretkey) = explode(',', trim($_s));
		$this->appkey = $appkey;
		$this->secretKey = $secretkey;
		$this->gatewayUrl = 'http://gw.api.taobao.com/router/rest';
		$this->format = 'xml';
		$this->signMethod = 'md5';
		$this->apiVersion = '2.0';
		$this->sdkVersion = 'top-sdk-php-20120429';
		$this->nick = $setting_tknick;
	}
	private function generateSign($params) {
		ksort($params);
		$stringToBeSigned = $this->secretKey;
		foreach($params as $k => $v) {
			if('@' != substr($v, 0, 1)) {
				$stringToBeSigned .= $k.$v;
			}
		}
		unset($k, $v);
		$stringToBeSigned .= $this->secretKey;
		return strtoupper(md5($stringToBeSigned));
	}
	public function request($method, $params, $session = null) {
		$sysParams["app_key"] = $this->appkey;
		$sysParams["v"] = $this->apiVersion;
		$sysParams["format"] = $this->format;
		$sysParams["sign_method"] = $this->signMethod;
		$sysParams["method"] = $method;
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
		$sysParams["partner_id"] = $this->sdkVersion;
		if(!empty($session)) $sysParams["session"] = $session;
		$sysParams = array_map('toutf8', $sysParams);
		$params["nick"] = $this->nick;
		$apiParams = array_map('toutf8', $params);
		$sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams));

		$requestUrl = $this->gatewayUrl.'?';
		foreach ($sysParams as $k => $v) {
			$requestUrl .= "$k=".urlencode($v).'&';
		}
		try {
			foreach ($apiParams as $k => $v) {
				$requestUrl .= "$k=".urlencode($v).'&';
			}
			$requestUrl = substr($requestUrl, 0, -1);
			$resp = readfromurl($requestUrl);
		} catch(Exception $e) {
			$result->code = $e->getCode();
			$result->msg = $e->getMessage();
			return $result;
		}
		if('xml' == $this->format) {
			$respObject = simplexml_load_string($resp);
			$return = obj2array($respObject);
			$return = fromutf8($return);
		}
		return $return;
	}
}
?>