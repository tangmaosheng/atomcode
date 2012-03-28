<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

/**
 * HtmlRender Class
 *
 * HTML 渲染引擎，给出自动
 *
 * @package		AtomCode
 * @subpackage	render
 * @category	render
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */

class HtmlRender extends Render {

	private $tpl_base_path, $tpl_ext, $cache_file, $isGot;

	public function __construct() {
		$this->tpl_base_path = trim(get_config('view_path', ''), ' /\\');
		$this->tpl_ext = trim(get_config('view_ext', '.php'));
		$this->isGot = FALSE;
		
		if (strlen($this->tpl_base_path)) {
			$this->tpl_base_path .= '/';
		}
		if (!$this->tpl_ext) {
			$this->tpl_ext = '.php';
		}
	}

	public function getContents($view = null) {
		if (!$this->isGot) {
			if ($view) {
				$this->prepare($view->view);
				extract($view->values);
			} else {
				extract($this->values);
			}
			
			ob_start();
			include $this->cache_file;
			$this->contents = ob_get_contents();
			ob_end_clean();

			$this->isGot = TRUE;
		}
		
		return $this->contents;
	}

	public function setEnv($view) {
		parent::setEnv($view);
		
		$this->prepare($this->view);
		$this->_preAssign();
	}

	/**
	 * 为模板变量预先赋值
	 */
	private function _preAssign() {
		global $RTR;
		if (!array_key_exists('get', $this->values))	$this->values['get'] = $_GET;
		if (!array_key_exists('post', $this->values))	$this->values['post'] = $_POST;
		if (!array_key_exists('class', $this->values))	$this->values['class'] = strtolower(substr($RTR->fetch_class(), 0, -strlen(Router::controller_suffix)));
		if (!array_key_exists('method', $this->values))	$this->values['method'] = $RTR->fetch_method();
		if (!array_key_exists('session', $this->values))	$this->values['session'] = $_SESSION;
		if (!array_key_exists('request', $this->values))	$this->values['request'] = array_merge($_GET, $_POST);
		if (!array_key_exists('base_url', $this->values))	$this->values['base_url'] = get_config('base_url');
		if (!array_key_exists('charset', $this->values))	$this->values['charset'] = get_config('charset');
		if (!array_key_exists('directory', $this->values))	$this->values['directory'] = strtolower(substr($RTR->fetch_directory(), 0, -1));
		if (!array_key_exists('current_act', $this->values))	$this->values['current_act'] = current_act();
		if (!array_key_exists('current_url', $this->values))	$this->values['current_url'] = current_url();
	}

	private function getView($view) {
		return APP_PATH . '/view/' . $this->tpl_base_path . $view . $this->tpl_ext;
	}

	private function getCacheFile($view) {
		return APP_PATH . '/cache/view/' . $this->tpl_base_path . $view . $this->tpl_ext;
	}

	/**
	 * 准备视图缓存文件
	 * @param $view
	 * @return null
	 */
	private function prepare($view) {
		$this->view = $view;
		$this->cache_file = $this->getCacheFile($view);
		$view_file = $this->getView($view);
		$regen = false;
		
		if (!file_exists($this->cache_file)) {
			$regen = true;
		}
		
		// 测试模式时要检查是否需要重新生成，而非测试模式不检查，使用清空模板缓存的方法
		if (!$regen && TEST_MODEL) {
			if (filemtime($this->cache_file) < filemtime($view_file)) {
				$regen = true;
			}
		}
		
		if ($regen) {
			$tpl_engine = Template::instance($view);
			$tpl_engine->compile();
			$tpl_engine->save($this->cache_file);
		}
		
		$this->isGot = FALSE;
	}

	public function display($view = null) {
		echo $this->getContents($view);
	}
}
// END HtmlRender CLASS

/* location: ./system/render/HtmlRender.php */