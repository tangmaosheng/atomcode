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
 * @version 1.0 2009-11-8
 * @filesource 
 */

class uri_query extends Uri
{
	function __construct()
	{
		parent::__construct();
		$this->_parse_uri();
	}
	
	private function _parse_uri()
	{
		$controller = $this->config['controller'];
		$method = $this->config['method'];
		
		$this->segments['controller'] = $_GET[$controller];
		$this->segments['method'] = $_GET[$method];

		foreach ($_GET as $k => $v)
		{
			$this->segments['get'][$k] = xaddslashes($v);
		}
	}
}