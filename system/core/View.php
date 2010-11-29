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
 * @version 1.0 2010-05-28
 * @filesource 
 * 
 * This class is the `view` layer in MVC model
 * It render the user interface(UI), decide whether to cache the contents, and where to
 * save it, also write contents to a static file on hard disk if need.
 * 
 */
class View
{
	private $var;
	public $config,$input,$get,$post,$cookie,$session,$request_method, $isPost, $context;
	
	private $static, $static_file;
	
	private $tagValue;
	private $sourceFile;
	private $cacheFile;
	private $viewFolder;
	
	public function __construct()
	{
		$this->config	=& Core::$config;
		$this->input	=& Core::$input;
		$this->get		=& Core::$get;
		$this->post		=& Core::$post;
		$this->cookie	=& Core::$cookie;
		$this->session	=& Core::$session;
		
		$this->viewExt	= empty($this->config['view_ext']) ? '.tpl' : $this->config['view_ext'];
		
		$this->tagValue = array();
	}

	/**
	 * assign value to a template varible
	 * @param $name
	 * @param $value
	 * @return 
	 */
	public function assign($name,$value = null)
	{
		if (is_array($name))
		{
			foreach ($name as $key => $value)
			{
				if ($key !== '')$this->tagValue[$key] = $value;
			}
		}
		else
		{
			if ($name !== '')$this->tagValue[$name] = $value;
		}
	}
	
	/**
	 * erase the point variable
	 * @param $name
	 * @return
	 */
	public function delete($name)
	{
		unset($this->tagValue[$name]);
	}
	
	/**
	 * set sub folder for view
	 * 
	 * @param $folder
	 * @return unknown_type
	 */
	public function setFolder($folder)
	{
		$this->viewFolder = trim($folder, ' /\\');
	}
	
	/**
	 * alias name for render
	 * @param $view
	 * @return
	 */
	public function display($view)
	{
		$this->render($view);
	}
	
	/**
	 * send content to browser
	 * @param $view
	 * @return
	 */
	public function render($view)
	{
		$html = $this->getData($view);
		if ($this->static)
		{
			file_put_contents($this->staic_file);
		}
		
		echo $html;
	}
	
	/**
	 * get file paths
	 * @param $view
	 * @return unknown_type
	 */
	private function getView($view)
	{
		$this->sourceFile = APP_PATH . '/view/' . $view . $this->viewExt;
		$this->cacheFile = APP_PATH . '/cache/view/' . $view . '.php';
	}
	
	/**
	 * Get generated html
	 * @param $view
	 * @return html
	 */
	public function getData($view)
	{
		$this->getView($view);
		
		if ($this->needRefresh())
		{
			$this->refresh();
		}
		
		if ($this->config['gzip'])
		{
			ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}
		
		include $this->cacheFile;
		
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
	}
	
	/**
	 * Does the cache file need to be generated?
	 * @return unknown_type
	 */
	private function needRefresh()
	{
		if (!file_exists($this->cacheFile))
		{
			return true;
		}
		
		if (!DEBUG_MODE)
		{
			return false;
		}
		
		if ($this->config['COMPILE']['AUTO_CHECK'] && filemtime($this->cacheFile) < filemtime($this->sourceFile))
		{
			return true;
		}
		
		if ($this->config['COMPILE']['URL_REFRESH_PARAM'] && $this->get[$this->config['COMPILE']['URL_REFRESH_PARAM']])
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * load compiler and make view cache file
	 * 
	 * @return unknown_type
	 */
	private function refresh()
	{
		$compiler = new Compile();
		$compiler->sourceFile = $this->sourceFile;
		$compiler->cacheFile = $this->cacheFile;
		$compiler->parseFile();
	}
}