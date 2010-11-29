<?php
abstract class Controller  {
	protected $config,$input,$get,$post,$cookie,$session,$request_method, $isPost, $context;
	protected $_view,$viewPath;
	
	public function __construct()
	{
		$this->config			=& Core::$config;
		$this->get				=& Core::$context;
		$this->viewPath			= strtolower(substr(get_class($this), 0, -10));
		
		$this->isPost			= is_post();
		
		$this->_view				= new View();
		
		if (method_exists($this,'_init'))
		{
			$this->_init();
		}
	}
	
	protected function _assign($name,$value)
	{
		$this->_view->assign($name,$value);
	}
	
	protected function _display($view)
	{
		$this->_render($view);
	}
	
	protected function _render($view)
	{
		if ($this->viewPath) $view = trim($this->viewPath,'/\\') . '/' . $view;
		$this->_view->render($view);
	}
	
	public function __call($name,$args)
	{
		$name = ltrim($name, '_');
		$this->_display($name);
	}
	
}
