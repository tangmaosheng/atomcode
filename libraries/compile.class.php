<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@cncms.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-11
 * @filesource 
 */
if (defined('COMPILE')) return;
# Has this file been loaded? 
define('COMPILE', 1);
# Maxium depth of include file, All the include tags which exceed the limit will be ignored
# If you need more deeper, change this value.
define('COMPILE_INCLUDE_DEPTH', 3);
# Whether the compiler strip the space characters to speed up
define('COMPILE_STRIP_SPACE', 1);

/**
 * Compile Class
 * 
 * This class is used to parse template files into a php file. So that the template file can
 * display dymic contents.
 * Procession:
 * Pre-process:	deal with the include tags and user controls, write contents in all the 
 * 				related files into one file. Strip the line break, continuous white characters.
 * 				This step check if the included files are existed.
 * Read-tags:	scan the whole file to record all the tags position and other information.
 *				This step check syntax error.
 * Generate:	Replace the template tags with PHP segments, and write the contents into a 
 * 				cached file
 */
class Compile 
{
	private $tags;
	private $pairTags;
	private $containerTags;
	private $containerCount;
	private $_depth;
	public  $lineNum;
	
	private $tplvar;
	public $config;
	public $input;
	public $get;
	public $post;
	public $cookie;
	public $session;
	
	private $viewName;
	private $viewPath;
	private $viewExt;
	
	private $sourceFile;
	private $cacheFile;
	private $timeFile;
	
	private $data;
	private $delayData;
	
	private $tagValue;
	private $tagIndex;
	
	private $sourceCode;

	public function __construct()
	{
		global $var;
		$this->config	=& $var->config;
		$this->input	=& $var->input;
		$this->get		=& $var->get;
		$this->post		=& $var->post;
		$this->cookie	=& $var->cookie;
		$this->session	=& $var->session;
		
		$this->viewExt	= empty($var->config['view_ext']) ? '.html' : $var->config['view_ext'];
		
		$this->lineNum	= 0;
		$this->_depth	= 0;
		$this->containerCount = -1;
		$this->data		= '';
		
		$this->tagIndex = $this->tagValue = $this->tags = $this->pairTags = $this->containerTags
		 = array();
	}

	/**
	 * assign value to a template varible
	 * @param $name
	 * @param $value
	 * @return 
	 */
	public function assign($name,$value)
	{
		$this->tagValue[0][$name] = $value;
	}
	
	/**
	 * erase the point variable
	 * @param $name
	 * @return
	 */
	public function delete($name)
	{
		unset($this->tagValue[0][$name]);
	}
	
	/**
	 * send content to browser
	 * @param $view
	 * @return
	 */
	public function show($view)
	{
		echo $this->getData($view);
	}
	
	/**
	 * Get html code from cache or dynamically generated code
	 * @param $view
	 * @return HTML
	 */
	public function getData($view)
	{
		$this->viewPath = $view;
		$this->viewName = end(explode('/',$view));
		
		if ($this->needRefresh($view))
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
	 * Determine whether to make templet cache
	 * @param $view
	 * @return 
	 */
	private function needRefresh($view)
	{
		$this->sourceFile = APP_PATH . '/views/' . $view . $this->viewExt;
		$this->cacheFile	= APP_PATH . '/cache/views/' . $view . '.php';
		$this->timeFile	= APP_PATH . '/cache/datas/' . $view . '.t';
		
		if (!file_exists($this->sourceFile) || !is_file($this->sourceFile))
		{
			$this->noFile();
			return false;
		}
		
		if (!file_exists($this->cacheFile))
		{
			return true;
		}
		
		if ($this->config['COMPILE']['AUTO_CHECK'])
		{
			if (!file_exists($this->timeFile)) return true;
			
			$SourceTime = filemtime($this->sourceFile);
			$DestTime	= file_get_contents($this->timeFile);
			
			if ($SourceTime > $DestTime)
			{
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Tigger FILE NOT EXISTS Error
	 * @return 
	 */
	private function noFile()
	{
			header('HTTP/1.1 404 Not Found');exit;
			trigger_error('Template File Not Exists!<br>Need File:' . APP_PATH . '/views/'
			 . $this->viewPath . $this->viewExt . '<br />Line:' . $this->lineNum,E_ERROR);
	}

	/**
	 * Refresh views.
	 * 3 steps needed.
	 * @return 
	 */
	private function refresh()
	{
		#step 1:
		$this->preProcess();
		#step 2:
		$this->getTags();
		$this->parseTags();
		#step 3:
		$this->refreshTplCache();
	}
	
	/**
	 * Read source code, scan the `include`, `use` tags.
	 * Replace these tags with real content which are pre-processed.
	 * @return 
	 */
	private function preProcess()
	{
		$this->sourceCode = file_get_contents($this->sourceFile);
		
		$this->sourceCode = $this->parseInclude($this->sourceFile);
	}
}

/**
 * Save temporary data for Compile Class.
 *
 */
class TagData
{
	public $data;
	public $Container;
	public $Method;
	
	public function __construct()
	{
		$Expire = 0;
	}
	
	public function getData($param)
	{
		ksort($param);
		$gn = to_guid_string($param);
		$this->Expire = $param['expire'];
		if ($this->Expire == -1)
		{
			$cnt = load_data('datas',$this->Container . '.' . $this->Method,$gn);
			if($cnt)
			{
				$this->Data = unserialize(substr($cnt,12));
				return $this->Data;
			}
		}
		elseif ($this->Expire > 0)
		{
			$cnt = load_data('datas',$this->Container . '.' . $this->Method,$gn);
			if($cnt)
			{
				$LastGen = substr($cnt,0,12);
				
				if (time() - $LastGen < $this->Expire)
				{
					$this->Data = unserialize(substr($cnt,12));
					return $this->Data;
				}
			}
		}
		
		$container = &load_container($this->Container);
		$method = $this->Method;
		$data = $container->$method($param);
		
		$this->Data = $data;
		if ($this->Expire != 0)
		 save_data('datas',$this->Container . '.' . $this->Method,$gn,sprintf('%012d%s',time(),serialize($data)));
		
		return $this->Data;
	}
}