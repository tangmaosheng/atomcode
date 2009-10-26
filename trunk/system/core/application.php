<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-6
 * @filesource 
 */

class Application
{
	var $cache = false;
	var $view_file = '';
	var $_cfg_view = '';
	var $default_view = 'index';
	var $ext = '.html';
	
	public function __construct()
	{
		global $URI,$var;
		$this->view_file = $URI->get_view();
		$this->cfg_view = $var->config['default_document'];
		$this->ext = $var->config['view_ext'] ? $var->config['view_ext'] : '.html';
	}
	
	/**
	 * 设置默认页面
	 * @param $index
	 * @return unknown_type
	 */
	public function set_default($index)
	{
		if (!empty($index))
		{
			$this->default_view = $index;
		}
	}
	
	/**
	 * 显示页面
	 * @return unknown_type
	 */
	public function display()
	{
		if (empty($this->view_file))
		{
			$this->view_file = $this->default_view;
		}
		
		if (empty($this->view_file) || !$this->is_tpl_exists($this->view_file))
		{
			$this->view_file = $this->cfg_view;
		}
		
		if (empty($this->view_file) || !$this->is_tpl_exists($this->view_file))
		{
			stop('No view is set.');
		}
		header('Content-Type:text/html;charset=' . $this->config['CHARSET']);
		$Engine = load_class('compile');
		$Engine->Show($this->view_file);
	}
	
	/**
	 * 获取视图路径
	 * @param $view
	 * @return unknown_type
	 */
	private function get_tpl_path($view)
	{
		return APP_PATH . '/views/' . $view . $this->ext;
	}
	
	/**
	 * 视图是否存在
	 * @param $view
	 * @return bool
	 */
	private function is_tpl_exists($view)
	{
		$ViewPath = $this->get_tpl_path($view);
		
		return file_exists($ViewPath);
	}
}