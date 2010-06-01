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
class Controller
{
	protected $config,$input,$get,$post,$cookie,$session,$request_method, $isPost;
	
	protected $_view,$viewPath;
	
	public function __construct()
	{
		global $var;
		$this->config			=& $var->config;
		$this->get				=& $var->get;
		$this->post				=& $var->post;
		$this->input			=& $var->input;
		$this->cookie			=& $var->cookie;
		$this->session			=& $var->session;
		$this->request_method	= $var->request_method;
		
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