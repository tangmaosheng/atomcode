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
	private $defaultController = 'welcome';
	private $defaultMethod = 'index';
	private $subDir = '';
	
	public $controller = '';
	public $method = '';
	
	/**
	 * 设置默认页面
	 * @param $index
	 * @return unknown_type
	 */
	public function setDefault($controller,$method)
	{
		$this->defaultController = $controller;
		$this->defaultMethod = $method;
	}
	
	/**
	 * 设置子目录
	 * @param $dir
	 * @return unknown_type
	 */
	public function setSubDir($dir)
	{
		$this->subDir = trim($dir,'/\\');
	}
	
	/**
	 * 初始化默认配置
	 * @return unknown_type
	 */
	public function init()
	{
		global $uri;

		$c = $uri->getController();
		$a = $uri->getMethod();
		$this->controller = empty($c) ? $this->defaultController : $c;
		$this->method = empty($a) ? $this->defaultMethod : $a;
	}
	
	/**
	 * 显示页面
	 * @return unknown_type
	 */
	public function display()
	{
		$this->init();
		
		$c = load_controller($this->controller,$this->subDir);
		
		if (!$c)
		{
			trigger_error("控制器 $this->controller 不存在",E_USER_ERROR);exit;
		}
		
		if (!method_exists($c,$this->method))
		{
			trigger_error("控制器 $this->controller 的方法 $this->method 不存在",E_USER_ERROR);exit;
		}
		$method = $this->method;
		$c->$method();
	}
}