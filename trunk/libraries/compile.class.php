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
 * @version 1.0 2010-05-28
 * @filesource 
 */
if (defined('COMPILE')) return;
# Has this file been loaded? 
define('COMPILE', 1);
# Maxium depth of include file, All the include tags which exceed the limit will be ignored
# If you need more deeper, change this value.
define('COMPILE_INCLUDE_DEPTH', 5);
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
 * Replace tags:	scan the whole file to record all the tags position and other information.
 *				This step check syntax error.
 * Cleanup and save:	Replace the template tags with PHP segments, and write the contents into a 
 * 				cached file
 */
class Compile 
{
	public $sourceFile;
	public $cacheFile;
	public $sourceCode;
	private $destCode;
	
	public $config;
	private $fromFile;
	private $viewExt;
	
	private $tags;
	
	private $useDepth=0;

	public function __construct()
	{
		global $var;
		$this->config	=& $var->config;
		
		$this->viewExt	= empty($this->config['view_ext']) ? '.html' : $this->config['view_ext'];
	}
	
	/**
	 * load content from source file and parse to PHP code
	 * @return null
	 */
	public function parseFile()
	{
		if (empty($this->sourceFile))
		{
			exit('View file have not been given.');
		}
		
		if (!file_exists($this->sourceFile))
		{
			exit('Missing view file, can not compile it:' . $this->sourceFile);
		}
		
		$this->sourceCode = file_get_contents($this->sourceFile);
		
		if ($this->sourceCode === false)
		{
			exit('You may have no privilege to read this view:' . $this->sourceFile);
		}
		
		$this->parse();
		$this->save();
	}
	
	/**
	 * parse sorce code into PHP and HTML code
	 * if you want get the code instead of saving it to file, get contents from Compile::destCode
	 * 
	 * @return php + html
	 */
	public function parse()
	{
		$this->destCode = $this->sourceCode;
		
		if ($this->destCode == '')
		{
			return;
		}
		
		if (empty($this->sourceFile))
		{
			$this->sourceFile = APP_PATH . '/views/';
		}
		# pre-process
		$this->destCode = $this->preProcess($this->destCode, $this->sourceFile);
		# scan tags and replace them with php code
		$this->parseContents($this->destCode);
		# clean up the destination code
		#$this->cleanUp();
	}
	
	/**
	 * process `include` and `use` tags
	 * 
	 * @return unknown_type
	 */
	private function preProcess($code, $reference, $depth=0)
	{
		$depth ++;
		if (COMPILE_INCLUDE_DEPTH && $depth > COMPILE_INCLUDE_DEPTH)
		{
			return $code;
		}
		
		$code = preg_replace('/\{include\s+([^\s\}]+)\s*\/?\}/ies', '$this->parseInclude($depth, $reference, "$1")', $code);
		$code = preg_replace('/\{use((.\w+){2,})\s+([^\}]+)\}/ies', '$this->parseUse($depth, "$1", \'$3\')', $code);

		return $code;
	}
	
	/**
	 * Deal with the `include` tag
	 * @param $depth include depth
	 * @param $reference current file path
	 * @param $file included file path
	 * @return filename
	 */
	private function parseInclude($depth, $reference, $file)
	{
		if ($file{0} == '/')
		{
			$path = APP_PATH . '/views' . $file . $this->viewExt;
		}
		else
		{
			$path = realpath(dirname($reference) . '/' . $file . $this->viewExt);
		}
		
		if (strpos(str_replace('\\', '/', $path), APP_PATH . '/views/') !== 0)
		{
			exit('It\'s not allowed to include foreign file, or this file does not exist:' . $file . $this->viewExt);
		}
		
		if (!file_exists($path))
		{
			exit('Included file is not existed : ' . $path . ', Referenced by : ' . $reference);
		}
		
		$code = file_get_contents($path);
		if ($code === false)
		{
			exit('You may have no privilege to read this view:' . $path);
		}
		
		return $this->preProcess($code, $path, $depth);
	}
	
	/**
	 * Deal with the `use` tags
	 * @param $depth
	 * @param $user_control
	 * @param $param
	 * @return unknown_type
	 */
	private function parseUse($depth, $user_control, $param)
	{
		
		$param = stripslashes($param);
		$user_control = trim($user_control, '. ');
		$param = trim($param, ' /');
		
		$path = APP_PATH . '/uses/' . str_replace('.', '/', $user_control) . $this->viewExt;
	
		if (!file_exists($path))
		{
			exit('User Control file is not existed : ' . $path);
		}
		
		$code = file_get_contents($path);
		if ($code === false)
		{
			exit('You may have no privilege to read this user control:' . $path);
		}
		$code = "{use $param}" . $this->preProcess($code, APP_PATH . '/views/', $depth) . '{/use}';
		
		return $code;
	}
	
	/**
	 * save contents to a cache file
	 * @return unknown_type
	 */
	private function save()
	{
		mk_dir(dirname($this->cacheFile));
		touch($this->cacheFile);
		$suc = file_put_contents($this->cacheFile, $this->destCode);
		
		if ($suc === false)
		{
			exit('Failed to write contents to cache file.');
		}
	}
	
	private function parseContents(&$code)
	{
		$this->getTags($code);
		#$this->parseTags($code);
	}
	
	private function getTags(&$code)
	{
		# match "helo\"helo", or single quote
		$regx_dbquote = '"[^"\\\\\\$]*(?:\\\\.[^"\\\\\\$]*)*"';
		$regx_sgquote = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';
		$regx_quote = '(?:' . $regx_dbquote . '|' . $regx_sgquote . ')';
		
		$regx_noquote = '[^\\{\\}\\[\\]\\"\\\']*';
		$regx_expr = '(?:' . $regx_noquote . '|' . $regx_quote . ')';
		
		$regx_tagname = '\w+(?:\\.\w+)*';
		
		$regx_start_tag = '\{(' . $regx_tagname . ')(\s+' . $regx_expr . '*)?\}';
		$regx_end_tag = '\{/(\w*)\}';
		$regx_tag = '~(?:' . $regx_start_tag . '|' . $regx_end_tag . ')~';

		preg_match_all($regx_tag, $this->destCode, $this->tags, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
	}

	/**
	 * @todo not complete
	 * @param $param
	 * @param $depth
	 * @return unknown_type
	 */
	public function parseParam($param,$depth)
	{
		$param = trim($param);
		if(empty($param))return array();
		$before_equal = true;
		$name = '';
		$value= '';
		$return = array();
		
		$in_quote = false;
		$Slashing = false;
		$Bracket = 0;
		$quote_char = '';
		
		$var = '';
		$func = '';
		$InVar = false;
		$Infunc = false;
		$spliter = '';
		
		for($i = 0; $i < strlen($param); $i ++)
		{
			$char = $param{$i};
			
			if ($before_equal)
			{
				if (preg_match('/[\w\s]/',$char))
				{
					$name .= $char;
				}
				elseif ($char == '=')
				{
					$before_equal = false;
					$name = trim($name);
					if (preg_match('/\s/',$name))
					{
						trigger_error($param . '属性名包含空白字符!',E_USER_ERROR);exit;
					}
				}
				else
				#no special chars in attribute name
				{
					trigger_error($param . '属性名包含非法字符',E_USER_ERROR);exit;
				}
			}
			else
			#Bebore equal
			{
				if ($in_quote)
				{
					if ($Slashing)
					{
						$Slashing = false;
					}
					else
					{
						if ($char == '\\')
						{
							$Slashing = true;
						}
						elseif ($char == $quote_char)
						{
							$in_quote = false;
						}
					}
					$value .= $char;
				}
				else
				#out of quote
				{
					if ($InVar)
					{
						if (preg_match('/[\w]/',$char))
						{
							if (!empty($spliter))
							{
								$var .= $spliter;
								$spliter = '';
							}
							$var .= $char;
						}
						elseif ($char == '.')
						{
							$spliter = '.';
						}
						else
						{
							$InVar = false;
							
							if (empty($var))
							{
								trigger_error($param . '变量格式错误.',E_USER_ERROR);exit;
							}
							$value .= $this->getRealVarible($var,$depth);
							$value .= $spliter;
							
							if ($char == ',')
							{
								if ($Bracket < 1){
									$before_equal = true;
									$return[$name] = $value;
									$name = '';
									$value = '';
								}
								else
								{
									$value .= $char;
								}
							}
							else
							{
								if ($char == ')')$Bracket --;
								if ($char == '(')$Bracket ++;
								$value .= $char;
							}
							
							$var = '';
							$spliter = '';
							
						}
					}#end invar
					elseif ($Infunc)
					# (则结束函数,
					{
						/*if (preg_match('/[\w]/',$char))
						{
							$func .= $char;
						}
						else*/
						if ($char == '(')
						{
							$Infunc = false;
							$Bracket ++;
							$value .= $this->getRealFunction($func);
							$value .= $char;
							$func = '';
						}
						elseif($char == '$')
						{
							$InVar = true;
							$value .= $func;
							$Infunc = false;
							$func = '';
						}
						else
						{
							if ($char == ')')$Bracket --;
							if ($char == '(')$Bracket ++;
							$func .= $char;
						}
					}#end infunc
					else
					#普通字符: $则开始变量, 字符开始则开始函数, 引号开始则开始引用,其他字符一概收入return 
					{
						if ($char == '$')
						{
							$InVar = true;
						}
						elseif (preg_match('/[a-zA-Z]/',$char))
						{
							$Infunc = true;
							$func = $char;
						}
						elseif ($char == '"' || $char == "'")
						{
							$in_quote = true;
							$quote_char = $char;
							$value .= $char;
						}
						elseif ($char == ',')
						{
							if ($Bracket < 1){
								$before_equal = true;
								$return[$name] = $value;
								$name = '';
								$value = '';
							}
							else
							{
								$value .= $char;
							}
						}
						else
						{
							if ($char == ')')$Bracket --;
							if ($char == '(')$Bracket ++;
							$value .= $char;
						}
					}
				}
			}
		}#end for
		
		if($InVar)
		{
			$InVar = false;
			
			if (empty($var)) 
			{
				trigger_error($param . '变量格式错误.',E_USER_ERROR);exit;
			}
			$value .= $this->getRealVarible($var,$depth);
			$var = '';
			$spliter = '';
			$return[$name] = $value;
			$name = '';
			$value = '';
		}
		elseif ($Infunc)
		{
			$value .= $func;
			$return[$name] = $value;
			$name = '';
			$value = '';
		}
		else
		{
			$return[$name] = $value;
		}
		
		return $return;
	}
}