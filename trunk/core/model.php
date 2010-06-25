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
 * @version 1.0 2010-5-30
 * @filesource 
 */
abstract class Model 
{
	protected $config;
	protected $input;
	protected $get;
	protected $post;
	protected $cookie;
	protected $session;
	protected $lang;
	
	function __construct()
	{
		global $var, $lang;
		$this->config	=& $var->config;
		$this->input	=& $var->input;
		$this->get		=& $var->get;
		$this->post		=& $var->post;
		$this->cookie	=& $var->cookie;
		$this->session	=& $var->session;
		$this->lang		=& $lang;
	}
}