<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.atomcode.cn
 * @link http://www.atomcode.cn
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
	var $uri = array();
	
	public function __construct()
	{
		$uri = $_SERVER['REQUEST_URI'];
		
		$this->config = get_config();
		
		if (stripos($uri,$_SERVER['SCRIPT_NAME']) !== false)
		{
			if($this->config['disguise'])
			{
				header('location:' . str_ireplace($_SERVER['SCRIPT_NAME'], '', $uri));exit;
			}
			else
			{
				$uri = str_ireplace($_SERVER['SCRIPT_NAME'], '', $uri);
			}
		}
		
		$this->_parse_url($uri);
	}
	
	private function _parse_url($uri)
	{
		if(!$this->config['disguise'])
		{
			foreach($_GET as $k => $v)
			{
				$this->uri['get'][$k] = xaddslashes($v);
			}
			
			$this->uri['view'] = $this->uri['get'][$this->config['get']['view']];
			return;
		}
		
		$uri = trim($uri,'/');
		
		$segment = explode(
			$this->config['query_start'] ? $this->config['query_start'] : '?',
			$uri, 2);
		
		$this->uri['view'] = trim($segment[0],'/');
		
		if (empty($segment[1]))
		{
			$this->uri['get'] = array();
		}
		else
		{
			$delimeter = $this->config['query_delimeter'] ? $this->config['query_delimeter'] : '&';
			
			$pairs = explode($delimeter,$segment[1]);
			
			foreach($pairs as $pair)
			{
				if (empty($pair)) continue;
				
				$nv = explode('=',$pair,2); #name/value pair array.
				$this->uri['get'][$nv[0]] = $nv[1];
			}
		}
	}
	
	public function get_view()
	{
		return $this->uri['view'];
	}
	
	public function get_get()
	{
		return $this->uri['get'];
	}
}