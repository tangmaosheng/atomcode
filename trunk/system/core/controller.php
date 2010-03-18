<?php
class Controller
{
	protected $config,$input,$get,$post,$cookie,$session,$request_method, $isPost;
	
	protected $tpl,$tplPath;
	
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
		
		$this->isPost			= is_post();
		
		$this->tpl				= new Compile();
		
		if (method_exists($this,'_init'))
		{
			$this->_init();
		}
	}
	
	protected function _assign($name,$value)
	{
		$this->tpl->assign($name,$value);
	}
	
	protected function _display($view)
	{
		if ($this->tplPath) $view = trim($this->tplPath,'/\\') . '/' . $view;
		$this->tpl->show($view);
	}
	
	public function __call($name,$args)
	{
		$this->_display($name);
	}
}