<?php
require_once CORE_ROOT.'include/smarty/libs/Smarty.class.php';
class tpl {
	function tpl($paths, $cachepath = '', $variables = array()) {
		global $smarty;
		if(!isset($smarty)) {
			$smarty = new Smarty;
			$smarty->config_dir = AK_ROOT.'configs/';
			$smarty->cache_dir = AK_ROOT.'cache/';
			$smarty->left_delimiter = '<{';
			$smarty->right_delimiter = '}>';
		} else {
			$smarty->clear_all_assign();
		}
		
		$smarty->compile_dir = $cachepath;
		$smarty->trusted_dir = $paths;
		foreach($variables as $k => $v) {
			$smarty->assign($k, $v);
		}
		$this->defaulttemplatepath = $this->customtemplatepath = '';
		$this->smarty = $smarty;
	}
	function assign($variables) {
		foreach($variables as $k => $v) {
			$this->smarty->assign($k, $v);
		}
	}
	function regfunction($functions) {
		$functions = explode(',', $functions);
		foreach($functions as $f) {
			$this->smarty->register_function($f, $f);
		}
	}
	function functionexists($function) {
		if(isset($this->smarty->_plugins['function'][$function])) return true;
		return false;
	}
	function render($template) {
		$html = $this->smarty->text($template);
		return $html;
	}
}
?>