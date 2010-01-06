<?php
class Controller
{
	protected $config,$input,$get,$post,$cookie,$session,$request_method;
	
	protected $tpl;
	
	public function __construct()
	{
		global $var;
		$this->config			= $var->config;
		$this->get				= $var->get;
		$this->post				= $var->post;
		$this->input			= $var->input;
		$this->cookie			= $var->cookie;
		$this->session			= $var->session;
		$this->request_method	= $var->request_method;
		
		$this->tpl				= new Compile();
	}
	
	protected function _assign($name,$value)
	{
		$this->tpl->assign($name,$value);
	}
	
	protected function _display($view)
	{
		$this->tpl->show($view);
	}
}