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
class container extends ac_base
{
	protected $get;
	protected $post;
	protected $input;
	protected $cookie;
	protected $session;
	protected $method;
	
	public function __construct()
	{
		global $var;
		parent::__construct();
		$this->get		= $var->get;
		$this->post		= $var->post;
		$this->input	= $var->input;
		$this->cookie	= $var->cookie;
		$this->session	= $var->session;
		$this->method	= $var->method;
	}
}