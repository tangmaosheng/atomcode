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
class Container
{
	protected $get;
	protected $post;
	protected $input;
	protected $cookie;
	protected $session;
	protected $request_method;
	
	public function __construct()
	{
		global $var;
		
		$this->config	= $var->config;
		$this->get		= $var->get;
		$this->post		= $var->post;
		$this->input	= $var->input;
		$this->cookie	= $var->cookie;
		$this->session	= $var->session;
		$this->request_method	= $var->request_method;
	}
}