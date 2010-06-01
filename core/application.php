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
 * @version 1.0 2010-05-28
 * @filesource 
 * 
 * The entrance of the framework, decide which controller will be called
 */

class Application
{
	private $defaultController = 'welcome';
	private $defaultMethod = 'index';
	private $subDir = '';
	
	public $controller = '';
	public $method = '';
	
	/**
	 * set default controller and method
	 * called when not be specifed
	 * 
	 * @param $index
	 * @return unknown_type
	 */
	public function setDefault($controller,$method)
	{
		$this->defaultController = $controller;
		$this->defaultMethod = $method;
	}
	
	/**
	 * set sub folder for controller
	 * @param $dir
	 * @return unknown_type
	 */
	public function setSubDir($dir)
	{
		$this->subDir = trim($dir,'/\\');
	}
	
	/**
	 * initialize the arguments
	 * @return unknown_type
	 */
	private function init()
	{
		global $var;

		$this->controller = empty($var->controller) ? $this->defaultController : $var->controller;
		$this->method = empty($var->method) ? $this->defaultMethod : $var->method;
	}
	
	/**
	 * output html contents
	 * @return unknown_type
	 */
	public function display()
	{
		global $var;
		$this->init();
		
		$a = $this->controller;
		$b = $this->method;
		$a = str_replace(' ', '', ucwords(strtolower(str_replace('-', ' ', $a))));
		$b = str_replace(' ', '', lcfirst(ucwords(strtolower(str_replace('-', ' ', $b)))));
		
		$controller = $var->config['CONTROLLER_CLASS_PREFIX'] . $a . $var->config['CONTROLLER_CLASS_SUFFIX'];
		$filename = (empty($this->subDir) ? '' :  $this->subDir . '/') . $controller . '.class.php';
		
		if (!try_require(APP_PATH . '/controllers/' . $filename) || !class_exists($controller))
		{
			exit("Missing controller `$a`");
		}
		
		$c = new $controller();
		
		if (is_reserved($b))
		{
			$b = '_' . $b;
		}
		
		if (!method_exists($c, $b))
		{
			exit("Missing method $a::$b");
		}
		
		$c->$b();
	}
}