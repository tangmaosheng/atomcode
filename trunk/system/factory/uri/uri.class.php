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

/**
 * Parsing url
 * 
 * Containers get parameters of url from here,and also used for
 * <application> class , it gets segment of url, to find out 
 * which file to be load.
 *
 */
class Uri
{
	public $segments = array();
	public $uri;
	public $uriName;
	public $config = array();
	
	public function __construct()
	{
		$this->uri = $_SERVER['REQUEST_URI'];
		
		$config = & get_config();
		$this->uriName = $config['uri']['scheme'];
		
		if (empty($this->uriName)) $this->uriName = 'query';
		if(is_array($config['uri'][$this->uriName])) $this->config = & $config['uri'][$this->uriName];
	}
	
	public static function getInstance()
	{
		$args = func_get_args();
		return get_instance_of(__CLASS__,'factory',$args);
	}
	
	public function &factory($config = array())
	{
		if (is_array($config))$this->config = array_merge($this->config,$config);
		
		$uriClass = 'uri_' . $this->uriName;
		$uriDriverPath = str_replace('\\','/',dirname(__FILE__)).'/driver/';
		require_cache($uriDriverPath . $this->uriName . '.class.php');
		
		if (class_exists($uriClass))
		{
			return new $uriClass();
		}
		else
		{
			trigger_error('URI子类' . $uriClass . '不存在!',E_USER_ERROR);exit;
		}
	}
	
	public function getController()
	{
		return $this->segments['controller'];
	}
	
	public function getMethod()
	{
		return $this->segments['method'];
	}
	
	public function getGet()
	{
		return $this->segments['get'];
	}
}