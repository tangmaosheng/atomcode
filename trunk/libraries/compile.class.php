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
 * @version 1.0 2009-10-11
 * @filesource 
 */
/**
 * 编译类
 * * * * * * * * * * * * * * * * * * * * * * * *
 * 1.词法分析
 * 1.1 扫描整个文件,将全部的标签信息读出并存入类内数组
 * 1.2 检查所获取到的标签是否合法,如果格式合法即继续,否则产生一个致命错误,不允许继续
 * * * * * * * * * * * * * * * * * * * * * * * *
 * 2.翻译为PHP
 * 2.1 获取每个标签的PHP代码
 * 2.2 获取数据生成代码(值输出标签无数据生成代码)
 * * * * * * * * * * * * * * * * * * * * * * * *
 * 3.完成编译
 * 3.1 将标签替换为PHP代码后的内容写入缓存
 * 3.2 将数据代码存入数据缓存
 * * * * * * * * * * * * * * * * * * * * * * * *
 * 一些默认的规则:
 * PHP包含文件缓存路径 APP_PATH/cache/views/VIEW_NAME.php
 * 数据文件缓存路径 APP_PATH/cache/datas/VIEW_NAME.php
 * 数据文件生成时间 APP_PATH/cache/datas/VIEW_NAME.t
 * 
 * @author Eachcan
 *
 */
class Compile
{
	private $tags;
	private $pairTags;
	private $containerTags;
	private $containerCount;
	private $_depth;
	public  $isUserControl;
	public  $isIncluded;
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
	private $dataFile;
	private $timeFile;
	
	private $data;
	private $delayData;
	
	private $tagValue;
	private $tagIndex;
	/**
	 * 做好初始化工作.
	 * @return unknown_type
	 */
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
		$this->isUserControl	= false;
		$this->isIncluded	= false;
		
		$this->lineNum	= 0;
		$this->_depth	= 0;
		$this->containerCount = -1;
		$this->data		= '';
		
		$this->tagIndex = $this->tagValue = $this->tags = $this->pairTags = $this->containerTags = array();
	}
	
	/**
	 * 为模板变量赋值
	 * @param $name
	 * @param $value
	 * @return unknown_type
	 */
	public function assign($name,$value)
	{
		$this->tagValue[0][$name] = $value;
	}
	
	/**
	 * 显示缓存中的文件
	 * @param $view
	 * @return void
	 */
	public function show($view)
	{
		echo $this->getData($view);
	}
	
	/**
	 * 加载用控件
	 * @param $control
	 * @param $param
	 * @return unknown_type
	 */
	public function showUserControl($control,$param)
	{
		$this->isUserControl = true;
		$this->tplvar = $param;
		$control = str_replace('.','/',$control);
		
		$this->show($control);
	}
	
	/**
	 * 刷新视图
	 * 重新生成缓存文件和数据文件
	 * @param $view
	 * @return void
	 */
	private function refresh()
	{
		$this->getTags();
		$this->parseTags();
		$this->refreshTplCache();
	}
	
	/**
	 * 取得每个可用的Tag
	 * 规则:
	 * 标签内引号中只有引号及 "\" 本身需要转义
	 * 外部则需要将 { [ 转义
	 * 
	 * @return unknown_type
	 */
	private function getTags()
	{
		$tags = array();
		$tag = array();
		$tag_string = ''; #标签内容
		$tag_ready = ''; #标签起始开始
		$start_pos = -1; #标签开始位置
		
		$now_pos = 0; #当前程序运行位置
		
		$now_line = 1;
		$now_offset = 0;
		
		$in_quote = false; #在引号内,仅能在标签内,外面不计
		$tag_start = false;#标签正常开始
		$slashing = false;#正在转义,仅能在引号内,外面不计
		
		$quote_char = ''; #引号符号
		
		$fp	= fopen($this->sourceFile,'r');
		
		while(false !== ($char = fgetc($fp)))
		{
			if ($char == "\n")
			{
				$now_line ++;
				$now_offset =0;
			}
			else
			{
				$now_offset ++;
			}
			
			if ($slashing)
			{
				$tag_string .= $char;
				$slashing = false;
			}
			else #not slashing
			{
				if ($in_quote)
				{
					$tag_string .= $char;
					if ($char == '"' || $char == "'") #special chars
					{
						if ($quote_char == $char)
						{
							$in_quote = false;
						}
					}
					elseif ($char == '\\')
					{
						$slashing = true;
					}#end special chars
				}
				else #not in quote && not slashing
				{
					/*
					 * 在标签内只判断 引号,\,结束符号 ]}
					 * all I know right now is end or continue.
					 */
					if ($tag_start)
					{
						if($char == '"' || $char == "'")
						{
							$in_quote = true;
							$quote_char = $char;
						}
						elseif($char == ']' || $char == '}')
						{
							if (($tag_ready{0} == '{' && $char == '}') || ($tag_ready{0} == '[' && $char == ']'))
							{
								$tag['Start']	= $start_pos;
								$tag['End']		= $now_pos;
								$tag['NameSpace'] = $tag_ready;
								$tag['Body']	= $tag_string;
								$tag['Line']	= $now_line;
								
								$tags[] = $tag;
								
								$tag_ready = '';
								$tag_string = '';
								$tag_start = false;
								$tag_ready = '';
							}
						}
						
						if ($tag_ready)$tag_string .= $char;
					}
					/*
					 * Now,I am looking for Tag Start flag,'[', '{', '$', '@', '?' and my aids
					 */
					else
					{
						if ($char == '[' || $char == '{')
						{
							$tag_ready = $char;
							$start_pos = $now_pos;
							$tag_string = '';
						}
						elseif ($char == '@' || $char == '$' || $char == '?')
						{
							if ((($tag_ready == '[' && ($char == '$' || $char == '@'))
							   ||($tag_ready == '{' && $char == '?')) && $start_pos == $now_pos - 1)
							{
								$tag_start = true;
								$tag_ready .= $char;
							}
						}
					}
				} #end in quote
			} #end slashing
			
			$now_pos ++;
		}
		
		fclose($fp);
		
		$this->tags = $tags;
	}
	
	/**
	 * 解析标签
	 * 标签分为输出和控制标签两大类,要转换之前,先认证各标签是否正确.
	 * @return void
	 */
	private function parseTags()
	{
		
		foreach ($this->tags as &$tag)
		{
			if ($tag['NameSpace'] == '[$')
			{
				$tag['php'] = $this->getPHP('echo ' . $this->parseValue('$' . $tag['Body'],$this->_depth) . ';');
			}
			elseif($tag['NameSpace'] == '[@')
			{
				$tag['php'] = $this->getPHP('echo ' . $this->parseValue($tag['Body'],$this->_depth) . ';');
			}
			else
			{
				preg_match('/^[\w\.]+/',$tag['Body'],$segment);
				preg_match('/[^\w\.].*/',$tag['Body'],$segment1);
				
				$segment[1] = $segment1[0];
				unset($segment1);
				$segment[0] = strtolower($segment[0]);
				$segment[1] = trim($segment[1]);
				$self_end	= (bool)(substr($segment[1], -1) == '/');
				
				if (in_array($segment[0],array('if','for','foreach')))
				{
					$tag['php'] = $this->getPHP($segment[0] . ' (' . $this->parseValue($segment[1],$this->_depth) . ') {');
					array_push($this->pairTags,$segment[0]);
				}
				elseif (in_array($segment[0],array('break','continue')))
				{
					$tag['php'] = $this->getPHP($segment[0] . ';');
				}
				elseif($segment[0] == 'elseif')
				{
					if('if' != end($this->pairTags)) trigger_error('elseif处于if之外,行:' . $tag['Line'], E_USER_ERROR);
					$tag['php'] = $this->getPHP('}elseif (' . $this->parseValue($segment[1],$this->_depth) . ') {');
				}
				elseif($segment[0] == 'else')
				{
					if('if' != end($this->pairTags)) trigger_error('elseif处于if之外,行:' . $tag['Line'],E_USER_ERROR);
					$tag['php'] = $this->getPHP('}else{');
				}
				elseif (in_array($segment[0], array('endif', 'endfor', 'endforeach')))
				{
					if (substr($segment[0],3) != end($this->pairTags))
					{
						trigger_error($segment[0] . '与前一个未闭合的标签不匹配!!,行:' . $tag['Line'],E_USER_ERROR);
					}
					
					array_pop($this->pairTags);
					$tag['php'] = $this->getPHP('}');
				}
				elseif(strpos($segment[0],'use.') === 0)
				{
					$segment[0] = substr($segment[0],4);
					$tag['php'] = $this->getUserControlPHP($segment[0],$segment[1],$this->_depth);
				}
				elseif($segment[0]=='loop')
				{
					$this->_depth ++;
					array_push($this->pairTags,$segment[0]);
					$tag['php'] = $this->getLoopPHP($segment[1],$this->_depth);
				}
				elseif ($segment[0] == 'endloop')
				{
					if (empty($this->pairTags) || array_pop($this->pairTags) != 'loop')
					{
						trigger_error($segment[0] . '与前一个未闭合的标签不匹配!!,行:' . $tag['Line'],E_USER_ERROR);
					}
					
					$tag['php'] = $this->getPHP('}');
					$this->_depth --;
				}
				elseif($segment[0] == 'include')
				{
					$tag['php'] = $this->getIncludePHP($segment[1],$this->_depth);
				}
				elseif($segment[0] == 'end')
				{
					if (empty($this->pairTags) || in_array(array_pop($this->pairTags),array('for','foreach','if','loop')))
					{
						trigger_error($segment[0] . '与前一个未闭合的标签不匹配!!,行:' . $tag['Line'],E_USER_ERROR);
					}
					$tag['php'] = $this->getPHP('}');
					$this->_depth --;
				}
				else
				{
					if($self_end)
					{
					}
					else
					{
						$this->_depth ++;
						$this->containerCount ++;
						array_push($this->pairTags,$segment[0]);
					}
					
					$tag['php'] = $this->getBlockPHP($segment[0],$segment[1],$this->_depth);
				}
			}
		}
	}
	/**
	 * 需要刷新缓存文件
	 * 此处还应该检查源文件是否存在.如果不存在,直接返回 404 错误;如果为包含文件
	 * @param $view
	 * @return bool
	 */
	private function needRefresh($view)
	{
		if ($this->isUserControl)
		{
			$this->sourceFile = APP_PATH . '/usercontrols/' . $view . $this->viewExt;
			$this->cacheFile	= APP_PATH . '/cache/usercontrols/' . $view . '.php';
			$this->dataFile	= APP_PATH . '/cache/ucdatas/' . $view . '.php';
			$this->timeFile	= APP_PATH . '/cache/ucdatas/' . $view . '.t';
		}
		else
		{
			$this->sourceFile = APP_PATH . '/views/' . $view . $this->viewExt;
			$this->cacheFile	= APP_PATH . '/cache/views/' . $view . '.php';
			$this->dataFile	= APP_PATH . '/cache/datas/' . $view . '.php';
			$this->timeFile	= APP_PATH . '/cache/datas/' . $view . '.t';
		}
		if (!file_exists($this->sourceFile) || !is_file($this->sourceFile))
		{
			$this->noFile();
			return false;
		}
		
		if (!file_exists($this->cacheFile) || !file_exists($this->dataFile) || !file_exists($this->timeFile))
		{
			return true;
		}
		
		$SourceTime = filemtime($this->sourceFile);
		$DestTime	= file_get_contents($this->timeFile);
		
		if ($SourceTime > $DestTime)
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * 文件不存在
	 * @return unknown_type
	 */
	private function noFile()
	{
		if (!($this->isIncluded || $this->isUserControl)) #正常文件
		{
			header('HTTP/1.1 404 Not Found');exit;
		}
		elseif ($this->isIncluded) #包含文件
		{
			trigger_error('包含文件不存在!<br>源:' . APP_PATH . '/views/' . $this->viewPath . $this->viewExt . '<br />行:' . $this->lineNum,E_ERROR);
		}
		elseif ($this->isUserControl) #用户控件
		{
			trigger_error('用户控件不存在!<br>源:' . APP_PATH . '/views/' . $this->viewPath . $this->viewExt . '<br />行:' . $this->lineNum,E_ERROR);
		}
	}
	
	/**
	 * 格式化名值对.
	 * 把属性解析成成对的名值对.
	 * 如:id=1+$config.offset , category = $name
	 * @param $param
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
	
	/**
	 * 格式化字符串为相应的PHP代码
	 * 如:$name . substr($content,0,10) . $config.filename . '...'
	 * 要解析为 $this->getRealVarible('name',$depth) . ' . ' . $this->getRealFunction('substr') . '(' . $this->getRealVarible('content',$depth) . ',0,10)'
	 *  .  $this->getRealVarible('config.filename',$depth) . ' . \'...\''
	 * @param $value
	 * @return unknown_type
	 */
	public function parseValue($value, $depth)
	{
		$InQuote = false;
		$Slashing = false;
		$QuoteChar = '';
		
		$var = '';
		$func = '';
		$InVar = false;
		$Infunc = false;
		$spliter = '';
		
		$return = '';
		
		for($i = 0; $i < strlen($value); $i ++)
		{
			$char = $value{$i};
			
			if ($InQuote)
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
					elseif ($char == $QuoteChar)
					{
						$InQuote = false;
					}
				}
				$return .= $char;
			}
			else
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
							trigger_error('变量格式错误.',E_USER_ERROR);exit;
						}
						$return .= $this->getRealVarible($var,$depth);
						$return .= $spliter;
						$return .= $char;
						$var = '';
						$spliter = '';
					}
				}#end invar
				elseif ($Infunc)
				# (则结束函数,
				{
					if ($char == '(')
					{
						$Infunc = false;
						
						$return .= $this->getRealFunction($func);
						$return .= $char;
						$func = '';
					}
					elseif($char == '$')
					{
						$InVar = true;
						$return .= $func;
						$Infunc = false;
						$func = '';
					}
					else
					{
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
						$InQuote = true;
						$QuoteChar = $char;
						$return .= $char;
					}
					else
					{
						$return .= $char;
					}
				}
			}
		}#end for
		
		if($InVar)
		{
			$InVar = false;
			
			if (empty($var))
			{
				trigger_error($value . '变量格式错误.',E_USER_ERROR);exit;
			}
			$return .= $this->getRealVarible($var,$depth);
			$var = '';
			$spliter = '';
		}
		elseif ($Infunc)
		{
			$return .= $func;
		}
		
		return $return;
	}
	
	/**
	 * 取得PHP标签围绕的代码
	 * @param $code
	 * @return unknown_type
	 */
	public function getPHP($code)
	{
		return "<" . "?php " . $code . "?" . ">";
	}
	
	/**
	 * 取得格式化的用户控件
	 * @param $userControl
	 * @param $param
	 * @param $depth
	 * @return unknown_type
	 */
	public function getUserControlPHP($userControl,$param,$depth)
	{
		$param = trim($param,' /');
		$param = $this->parseParam($param,$depth);
		$ParamStr = "\$param=array();\n";
		
		foreach($param as $k => $v)
		{
			$ParamStr .= "\$param['$k']=$v;\n";
		}
		
		$ParamStr .=  "\$uc=new Compile();\n\$uc->tagValue=&\$this->tagValue;\n\$uc->showUserControl('$userControl',\$param);";
		return $this->getPHP($ParamStr);
	}
	
	public function getIncludePHP($param,$depth)
	{
		$param = trim($param,' /');
		$param = str_replace('.', '/', $param);
		$param = str_replace('\\', '/', $param);
		
		$param = $this->parseValue($param,$depth);
		
		if ($param{0} != '/')
		{
			$ns = explode('/',$this->viewPath);
			array_pop($ns);
			$param = implode('/',$ns) . '/' . $param;			
		}
		
		$param = trim($param,' /');
	
		$ParamStr =  "\$uc=new Compile();\n\$uc->tagValue=&\$this->tagValue;\n\$uc->IsIncluded=true;\n\$uc->show('$param');";
		return $this->getPHP($ParamStr);
	}
	
	/**
	 * 取得容器循环部分标签的PHP代码
	 * 一般用于容器标签内部
	 * 如果当前的深度为5,则会自动循环 $this->tagValue[5],将值保存在$this->tagValue[6]中
	 * 也可以指定$depth,可以指定循环哪一个值
	 * 为正数时则直接循环那个级别的值,如:2 此时会自动循环 $this->tagValue[2],仍然会将值保存在$this->tagValue[6]中
	 * 为负数时会与当前的深度相加,  如:-2  此时会自动循环 $this->tagValue[4],仍然会将值保存在$this->tagValue[6]中
	 * @param $container
	 * @param $param
	 * @param $depth
	 * @return unknown_type
	 */
	public function getLoopPHP($param,$depth)
	{
		$param = trim($param,' /');
		
		if (empty($param))
		{
			$pre_depth = $depth - 1;
		}
		else
		{
			$params = explode(',',$param,2);
			$_depth_offset = (is_numeric($params[0]) ? $params[0] : "-0");
			
			if ($_depth_offset{0} == '-')
			{
				$pre_depth = $depth - 1 - abs($_depth_offset);
			}
			else
			{
				$pre_depth = intval($_depth_offset);
			}
			$pre_depth = max(min($pre_depth, $depth - 1),0);
			
			if (is_numeric($params[0]))
			{
				$var = $params[1];
			}
			else
			{
				$var = $params[0];
			}
		}
		
		if ($var)
		{
			$param_str =  "if(\$this->tagValue[$pre_depth]['$var'])\nforeach(\$this->tagValue[$pre_depth]['$var'] as \$this->tagIndex[$this->_depth] => \$this->tagValue[$this->_depth]){";
		}
		else
		{
			$param_str =  "if(\$this->tagValue[$pre_depth])\nforeach(\$this->tagValue[$pre_depth] as \$this->tagIndex[$this->_depth] => \$this->tagValue[$this->_depth]){";
		}
		
		return $this->getPHP($param_str);
	}
	
	/**
	 * 取得容器返回的数组
	 * @param $container
	 * @param $param
	 * @param $depth
	 * @return unknown_type
	 */
	public function getBlockPHP($container,$param,$depth)
	{
		$param = trim($param,' /');
		$param = $this->parseParam($param,$depth);
		$this->data .= "\n\$param=array();\n";
		
		foreach($param as $k => $v)
		{
			$this->data .= "\$param['$k']=$v;\n";
		}
		
		$this->data .= "\$this->containerTags[$this->containerCount] = new TagData();\n";
		$container = explode('.',$container);
		$this->data .= "\$this->containerTags[$this->containerCount]->Method = '" . array_pop($container) . "';\n";
		$this->data .= "\$this->containerTags[$this->containerCount]->Container = '" . implode('.',$container) . "';\n";
		$this->data .=  "\$this->containerTags[$this->containerCount]->getData(\$param);\n";
		
		$ParamStr .=  "if(\$this->containerTags[$this->containerCount]->Data){\n\$this->tagValue[$this->_depth] = \$this->containerTags[$this->containerCount]->Data;";
		return $this->getPHP($ParamStr);
	}
	
	/**
	 * 取得变量的PHP形式
	 * @param $name
	 * @param $depth
	 * @return unknown_type
	 */
	public function getRealVarible($name,$depth=0)
	{
		if (strpos($name,'.') !== false)
		{
			$NameArray = explode('.',$name);
		}
		else
		{
			$NameArray [0] = $name;
		}
		
		if (in_array(strtolower($NameArray[0]),array('config','input','get','post','cookie','session','tplvar')))
		{
			return '$this->' . array_shift($NameArray) . (count($NameArray) > 0 ? "['" . implode("']['",$NameArray) . "']" : '');
		}
		else
		{
			if(preg_match('/^([\d]+)(.*)/',$NameArray[0],$array))
			{
				$depth = min($array[1],$depth);
				$NameArray[0] = $array[2];
			}
			
			if (count($NameArray) == 1 && in_array($NameArray[0],array('_','')))
			{
				return '$this->tagIndex[' . $depth . ']';
			}
			
			return '$this->tagValue[' . $depth . ']' .  "['" . implode("']['",$NameArray) . "']";
		}
	}
	
	/**
	 * 取得函数的PHP形式
	 */
	public function getRealFunction($name)
	{
		if (strpos($name, '.'))
		{
			$names = explode('.', $name);
			$name = array_pop($names);
			$names = implode('/',$names);
			
			$this->data .= "load_helper('$names');\n";
			
			return $name;
		}
		elseif (strpos($name, '::'))
		{
			$names = substr($name, 0, strpos($name, '::'));
			
			$this->data .= "load_class('$names', false);\n";
			
			return $name;
		}
		elseif (strpos($name,'->'))
		{
			$names = explode('->', $name,2);
			$name = end($names);
			$class = $names[0];
			
			$this->data .= "if (!(\$temp = load_container('$class'))){
	\$$class = load_controller('$class');
}";
			return '$' . $class . '->' . $name;
		}
		return $name;
	}
	
	/**
	 * 取出执行完成后的内容
	 * @param $view
	 * @return unknown_type
	 */
	public function getData($view)
	{
		$this->viewPath = $view;
		$this->viewName = end(explode('/',$view));
		
		if ($this->needRefresh($view))
		{
			$this->refresh();
		}
		
		if (!$this->isIncluded && !$this->isUserControl)
		{
			if ($this->config['gzip'])
			{
				ob_start('ob_gzhandler');
			}
			else
			{
				ob_start();
			}
		}
		
		include $this->dataFile;
		include $this->cacheFile;
		
		if (!$this->isIncluded && !$this->isUserControl)
		{
			$content = ob_get_contents();
			ob_end_clean();
		}
		
		return $content;
	}
	
	/**
	 * 更新模板缓存
	 * @return unknown_type
	 */
	public function refreshTplCache()
	{
		$now = 0;
		
		$source = file_get_contents($this->sourceFile);
		$dest = '';
		foreach ($this->tags as $tag)
		{
			$dest .= substr($source,$now,$tag['Start'] - $now);
			$dest .= $tag['php'];
			$now = $tag['End'] + 1;
		}
		$dest .= substr($source,$now);
		
		mk_dir(dirname($this->cacheFile));
		file_put_contents($this->cacheFile,$dest);
		mk_dir(dirname($this->dataFile));
		file_put_contents($this->dataFile,$this->getPHP($this->data . $this->delayData));
		mk_dir(dirname($this->timeFile));
		file_put_contents($this->timeFile,time());
	}
}

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
		if ($this->Expire != 0) save_data('datas',$this->Container . '.' . $this->Method,$gn,sprintf('%012d%s',time(),serialize($data)));
		
		return $this->Data;
	}
}