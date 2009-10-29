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
define('TAG_STATEMENT',1);//流程控制,如if, for, foreach, ...
define('TAG_MID_STAT',2);//中间流程控制,如 break, else, elseif, continue
define('TAG_END_STAT',4);//流程控制结束,如 endif, endfor, endforeach
define('TAG_BLOCK',8); //块状标签(自动循环标签) {?news.listnews}
define('TAG_BLOCK_SELF_END',16); //自动结束的块状标签(自动循环标签){?news.listnews /}
define('TAG_FUNC',32); //函数调用标签[@substr($sdf)]
define('TAG_VARIABLE',64); // 可直接输出的变量标签[$name]
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
	private $Tags;
	private $PairTags;
	private $ContainerTags;
	private $ContainerCount;
	private $Depth;
	public  $IsUserControl;
	public  $IsIncluded;
	public  $LineNum;
	
	private $TplVar;
	public $config;
	public $input;
	public $get;
	public $post;
	public $cookie;
	public $session;
	
	private $ViewName;
	private $ViewPath;
	private $ViewExt;
	
	private $SourceFile;
	private $CacheFile;
	private $DataFile;
	private $TimeFile;
	
	private $Data;
	
	/**
	 * 做好初始化工作.
	 * @return unknown_type
	 */
	public function __construct()
	{
		global $var;
		$this->config	= $var->config;
		$this->input	= $var->input;
		$this->get		= $var->get;
		$this->post		= $var->post;
		$this->cookie	= $var->cookie;
		$this->session	= $var->session;
		$this->ViewExt	= $var->config['view_ext'] ? $var->config['view_ext'] : '.html';
		$this->IsUserControl	= false;
		$this->IsIncluded	= false;
		$this->LineNum	= 0;
		$this->Depth	= -1;
		$this->ContainerCount = -1;
		
		$this->Tags = $this->PairTags = $this->ContainerTags = array();
	}
	
	/**
	 * 刷新视图
	 * 重新生成缓存文件和数据文件
	 * @param $view
	 * @return void
	 */
	private function Refresh()
	{
		header('Content-Type:text/html;charset=' . $this->config['CHARSET']);
		$this->GetTags();
		$this->ParseTags();
		$this->RefreshTplCache();
	}
	
	/**
	 * 取得每个可用的Tag
	 * 规则:
	 * 标签内引号中只有引号及 "\" 本身需要转义
	 * 外部则需要将 { [ 转义
	 * 
	 * @return unknown_type
	 */
	private function GetTags()
	{
		$Tags = array();
		$Tag = array();
		$TagString = ''; #标签内容
		$TagReady = ''; #标签起始开始
		$StartPos = -1; #标签开始位置
		
		$NowPos = 0; #当前程序运行位置
		
		$NowLine = 1;
		$NowOffset = 0;
		
		$InQuote = false; #在引号内
		$TagStart = false;#标签正常开始
		$Slashing = false;#正在转义
		
		$QuoteChar = ''; #引号符号
		
		$fp	= fopen($this->SourceFile,'r');
		
		while(false !== ($char = fgetc($fp)))
		{
			if ($char == "\n")
			{
				$NowLine ++;
				$NowOffset =0;
			}
			else
			{
				$NowOffset ++;
			}
			
			if ($Slashing)
			{
				if ($TagStart)
				{
					/*
					if ($char != '"' && $char != "'" && $char != "\\")
					{
						$TagString .= '\\';
					}*/
					$TagString .= '\\';
					$TagString .= $char;
				}
				else
				{
					//Pass.in html
				}
				$Slashing = false;
			}
			else #not slashing
			{
				if ($InQuote)
				{
					if ($char == '"' || $char == "'") #special chars
					{
						$TagString .= $char;
						if ($QuoteChar == $char)
						{
							$InQuote = false;
						}
					}
					elseif ($char == '\\')
					{
						$Slashing = true;
					}
					else
					{
						$TagString .= $char;
					} #end special chars
				}
				else #not in quote
				{
					if ($char == '\\') #special chars
					{
						if ($TagStart)
						{
							#error!!!!!!!!!!
							die('line:' . $NowLine . '<br>char:' . $NowOffset);
						}
						else
						{
							$Slashing = true;
						}
					}
					elseif($char == '"' || $char == "'")
					{
						if ($TagStart)
						{
							$InQuote = true;
							$TagString .= $char;
							$QuoteChar = $char;
						}
						else
						{
							#ignore it.
						}
					}
					elseif ($char == '[' || $char == '{')
					{
						if ($TagStart)
						{
							#error!!!!!!!!!!
							die('line:' . $NowLine . '<br>char:' . $NowOffset);
						}
						$TagReady = $char;
						$StartPos = $NowPos;
						$TagStart = false;
						$TagString = '';
					}
					elseif ($char == '@' || $char == '$' || $char == '?')
					{
						if ($TagReady == '[' && ($char == '$' || $char == '@') && $StartPos == $NowPos - 1)
						{
							$TagStart = true;
							$TagReady .= $char;
						}
						elseif ($TagReady == '{' && $char == '?' && $StartPos == $NowPos - 1)
						{
							$TagStart = true;
							$TagReady .= $char;
						}
						else
						{
							if ($TagStart)
							{
								#error!!!!!!!!!!
								$TagString .= $char;
							}
							else
							{
								$TagReady = '';
								//normal chars,no need to 
							}
						}
					}
					elseif($char == ']' || $char == '}')
					{
						if ($TagStart)
						{
							if (($TagReady{0} == '{' && $char == '}') || ($TagReady{0} == '[' && $char == ']'))
							{
								$Tag['Start']	= $StartPos;
								$Tag['End']		= $NowPos;
								$Tag['NameSpace'] = $TagReady;
								$Tag['Body']	= $TagString;
								$Tag['Index']	= count($Tags);
								
								$Tags[] = $Tag;
								
								$TagReady = '';
								$TagString = '';
								$TagStart = false;
								$TagReady = '';
							}
							else
							{
								#error!!!!!!!!!!
								die('line:' . $NowLine . '<br>char:' . $NowOffset);
							}
						}
					}
					else
					{
						if ($TagStart)
						{
							$TagString .= $char;
						}
					}
				} #end in quote
			} #end slashing
			
			$NowPos ++;
		}
		
		fclose($fp);
		
		$this->Tags = $Tags;
	}
	
	/**
	 * 解析标签
	 * 标签分为输出和控制标签两大类,要转换之前,先认证各标签是否正确.
	 * ***
	 * 控制标签 if/for/foreach
	 * 格式: {?if $name == 'dd' && $0index == 0}
	 * 读出为:
	 * TAG_STATEMENT
	 * if
	 * $name == 'dd' && $0index == 0
	 * 解析为:<?php if( $this->TagValue[5]['name] == 'dd' && $this->TagIndex[0] == 0 ) { ?>
	 * 数据部分支持:no
	 * ***
	 * 自动循环标签 news.newslist ...(自动调取容器)
	 * 格式(例): {?news.newslist cid=0,order="pubtime desc,id desc"}
	 * 读出为:
	 * TAG_BLOCK
	 * news.newslist
	 * $param['cid']=0,$param['order']="pubtime desc,id desc"
	 * 解析为:<?php if(!empty($this->TagGroup[0]->GetData()))foreach($this->TagGroup[0]->Data as $this->TagIndex[0] => $this->TagValue[0]){ ?>
	 * 如果不是二维数组则解析为:<?php if(!empty($this->TagGroup[0]->GetData())){ $this->TagIndex[0] = 0;$this->TagValue[0]=$this->TagGroup[0]->Data;?>
	 * 数据部分支持:$this->TagGroup = unserial($DataString);//read from *.data
	 * ***
	 * 用户控件标签UserControl.ad.top.flash ...
	 * 格式(例): {?UserControl.ad.top.flash size="360,40" /}
	 * 读出为:
	 * TAG_BLOCK_SELF_END
	 * UserControl.ad.top.flash
	 * $param['size'] = "360,40"
	 * 解析为:<?php $uc=new compile();$uc->ShowUserControl('ad.top.flash',$param); ?>
	 * 数据部分支持:no
	 * ***
	 * 超级变量调用:config/cookie/session/get/post/input/TplVar
	 * 格式:[$get.id+$config.offset + 1]
	 * 读出为:
	 * TAG_VARIBLE
	 * get.id
	 * $param = '+$config.offset + 1'
	 * 解析为:<?php echo $this->get['id'] + $this->config['offset'] + 1; ?>
	 * ***
	 * 普通变量调用,用于调取自动循环标签中每一项的属性值.
	 * 格式:[$name] 或 [$5name]
	 * 读出为:
	 * TAG_VARIBLE
	 * name
	 * $param['depth']=5 //深度,默认为当前;
	 * 解析为:<?php echo $this->TagValue[5]['name']; ?>
	 * ***
	 * 公用函数调用
	 * 格式:[@substr($name,0,4)]
	 * 读出为
	 * TAG_BLOCK_FUNC
	 * $param = "$name,0,4";
	 * 解析为:<?php echo substr($this->TagValue[5]['name'],0,4); ?>
	 * @return void
	 */
	private function ParseTags()
	{
		
		foreach ($this->Tags as &$tag)
		{
			if ($tag['NameSpace'] == '[$')
			{
				$tag['type'] = TAG_VARIABLE;
				$tag['php'] = $this->GetPHP('echo ' . $this->ParseValue('$' . $tag['Body'],$this->Depth) . ';');
			}
			elseif($tag['NameSpace'] == '[@')
			{
				$tag['type'] = TAG_FUNC;
				$tag['php'] = $this->GetPHP('echo ' . $this->ParseValue($tag['Body'],$this->Depth) . ';');
			}
			else
			{
				preg_match('/^[\w.]+/',$tag['Body'],$Segment);
				preg_match('/[^\w.].*/',$tag['Body'],$Segment1);
				$Segment[1] = $Segment1[0];
				$Segment[0] = strtolower($Segment[0]);
				$Segment[1] = trim($Segment[1]);
				$SelfEnd	= (bool)(substr($Segment[1], -1) == '/');
				
				if (in_array($Segment[0],array('if','for','foreach')))
				{
					$tag['type'] = TAG_STATEMENT;
					$tag['php'] = $this->GetPHP($Segment[0] . ' (' . $this->ParseValue($Segment[1],$this->Depth) . ') {');
					$this->PairTags[] = $Segment[0];
				}
				elseif (in_array($Segment[0],array('break','continue')))
				{
					$tag['type'] = TAG_MID_STAT;
					$tag['php'] = $this->GetPHP($Segment[0] . ';');
				}
				elseif($Segment[0] == 'elseif')
				{
					if(!in_array('if',$this->PairTags)) die('elseif处于if之外');
					$tag['type'] = TAG_MID_STAT;
					$tag['php'] = $this->GetPHP('}elseif (' . $this->ParseValue($Segment[1],$this->Depth) . ') {');
				}
				elseif($Segment[0] == 'else')
				{
					if(!in_array('if',$this->PairTags)) die('elseif处于if之外');
					$tag['type'] = TAG_MID_STAT;
					$tag['php'] = $this->GetPHP('}else{');
				}
				elseif (in_array($Segment[0],array('endif','endfor','endforeach')))
				{
					if (substr($Segment[0],3) != array_pop($this->PairTags))
					{
						die($Segment[0] . '与前一个未闭合的标签不匹配!!');
					}
					
					$tag['type'] = TAG_END_STAT;
					$tag['php'] = $this->GetPHP('}');
				}
				elseif(strpos($Segment[0],'usercontrol.') === 0)
				{
					$tag['type'] = TAG_BLOCK_SELF_END;
					$Segment[0] = substr($Segment[0],12);
					$tag['php'] = $this->GetUserControlPHP($Segment[0],$Segment[1],$this->Depth);
				}
				elseif(strpos($Segment[0],'array.') === 0)
				{
					$tag['type'] = TAG_BLOCK;
					$this->Depth ++;
					$this->ContainerCount ++;
					$Segment[0] = substr($Segment[0],6);
					$tag['php'] = $this->GetArrayPHP($Segment[0],$Segment[1],$this->Depth);
				}
				elseif($Segment[0] == 'include')
				{
					$tag['type'] = TAG_BLOCK_SELF_END;
					$tag['php'] = $this->GetIncludePHP($Segment[1],$this->Depth);
				}
				elseif($Segment[0] == 'end')
				{
					if (empty($this->Tags) || in_array(array_shift($this->PairTags),array('for','foreach','if')))
					{
						die($Segment[0] . '与前一个未闭合的标签不匹配!!');
					}
					$tag['type'] = TAG_END_STAT;
					$tag['php'] = $this->GetPHP('}');
					$this->Depth --;
				}
				else
				{
					if(substr($Segment[0],-1) == '/')
					{
						$tag['type'] = TAG_BLOCK_SELF_END;
					}
					else
					{
						$this->Depth ++;
						$this->ContainerCount ++;
						$tag['type'] = TAG_BLOCK;
					}
					
					$tag['php'] = $this->GetBlockPHP($Segment[0],$Segment[1],$this->Depth);
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
	private function NeedRefresh($view)
	{
		if ($this->IsUserControl)
		{
			$this->SourceFile = APP_PATH . '/usercontrols/' . $view . $this->ViewExt;
			$this->CacheFile	= APP_PATH . '/cache/usercontrols/' . $view . '.php';
			$this->DataFile	= APP_PATH . '/cache/ucdatas/' . $view . '.php';
			$this->TimeFile	= APP_PATH . '/cache/ucdatas/' . $view . '.t';
		}
		else
		{
			$this->SourceFile = APP_PATH . '/views/' . $view . $this->ViewExt;
			$this->CacheFile	= APP_PATH . '/cache/views/' . $view . '.php';
			$this->DataFile	= APP_PATH . '/cache/datas/' . $view . '.php';
			$this->TimeFile	= APP_PATH . '/cache/datas/' . $view . '.t';
		}
		if (!file_exists($this->SourceFile) || !is_file($this->SourceFile))
		{
			$this->NoFile();
			return false;
		}
		
		if (!file_exists($this->CacheFile) || !file_exists($this->DataFile) || !file_exists($this->TimeFile))
		{
			return true;
		}
		
		$SourceTime = filemtime($this->SourceFile);
		$DestTime	= file_get_contents($this->TimeFile);
		
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
	private function NoFile()
	{
		if (!($this->IsIncluded || $this->IsUserControl)) #正常文件
		{
			header('HTTP/1.1 404 Not Found');exit;
		}
		elseif ($this->IsIncluded) #包含文件
		{
			trigger_error('包含文件不存在!<br>源:' . APP_PATH . '/views/' . $this->ViewPath . $this->ViewExt . '<br />行:' . $this->LineNum,E_ERROR);
		}
		elseif ($this->IsUserControl) #用户控件
		{
			trigger_error('用户控件不存在!<br>源:' . APP_PATH . '/views/' . $this->ViewPath . $this->ViewExt . '<br />行:' . $this->LineNum,E_ERROR);
		}
	}
	
	/**
	 * 显示缓存中的文件
	 * @param $view
	 * @return void
	 */
	public function Show($view)
	{
		echo $this->GetData($view);
	}
	
	/**
	 * 加载用控件
	 * @param $control
	 * @param $param
	 * @return unknown_type
	 */
	public function ShowUserControl($control,$param)
	{
		$this->IsUserControl = true;
		$this->TplVar = $param;
		$control = str_replace('.','/',$control);
		
		$this->Show($control);
	}
	
	public function GetData($view)
	{
		$this->ViewPath = $view;
		$this->ViewName = end(explode('/',$view));
		
		if ($this->NeedRefresh($view))
		{
			$this->Refresh();
		}
		if (!$this->IsIncluded && !$this->IsUserControl)
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
		
		include $this->DataFile;
		include $this->CacheFile;
		
		if (!$this->IsIncluded && !$this->IsUserControl)
		{
			$content = ob_get_contents();
			ob_end_flush();
		}
		
		return $content;
	}
	
	/**
	 * 取得变量的PHP形式
	 * @param $name
	 * @param $depth
	 * @return unknown_type
	 */
	public function GetRealVarible($name,$depth=0)
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
			if(preg_match('/^([\d]+)(.+)/',$NameArray[0],$array))
			{
				$depth = min($array[1],$depth);
				$NameArray[0] = $array[2];
			}
			
			return '$this->TagValue[' . $depth . ']' .  "['" . implode("']['",$NameArray) . "']";
		}
	}
	
	/**
	 * 取得函数的PHP形式
	 */
	public function GetRealFunction($name)
	{
		return str_replace('.','::',$name);
	}
	
	/**
	 * 格式化名值对.
	 * 把属性解析成成对的名值对.
	 * 如:id=1+$config.offset , category = $name
	 * @param $param
	 * @return unknown_type
	 */
	public function ParseParam($param,$depth)
	{
		$param = trim($param);
		if(empty($param))return array();
		$BeforeEqual = true;
		$name = '';
		$value= '';
		$return = array();
		
		$InQuote = false;
		$Slashing = false;
		$QuoteChar = '';
		
		$var = '';
		$func = '';
		$InVar = false;
		$Infunc = false;
		$spliter = '';
		
		for($i = 0; $i < strlen($param); $i ++)
		{
			$char = $param{$i};
			
			if ($BeforeEqual)
			{
				if (preg_match('/[\w\s]/',$char))
				{
					$name .= $char;
				}
				elseif ($char == '=')
				{
					$BeforeEqual = false;
					$name = trim($name);
					if (preg_match('/\s/',$char))
					{
						die('属性名包含空白字符!');
					}
				}
				else
				#no special chars in attribute name
				{
					die($name . $char . '非法字符');
				}
			}
			else
			#Bebore equal
			{
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
							
							if (empty($var)) die('变量格式错误.');
							$value .= $this->GetRealVarible($var,$depth);
							$value .= $spliter;
							
							if ($char == ',')
							{
								$return[$name] = $value;
								$name = '';
								$value = '';
								$BeforeEqual = true;
							}else
							{
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
							
							$value .= $this->GetRealFunction($func);
							$value .= $char;
							$func = '';
						}
						elseif($char == '$')
						{
							$InVar = true;
							$value .= $func;
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
							$value .= $char;
						}
						elseif ($char == ',')
						{
							$return[$name] = $value;
							$name = '';
							$value = '';
							$BeforeEqual = true;
						}
						else
						{
							$value .= $char;
						}
					}
				}
			}
		}#end for
		
		if($InVar)
		{
			$InVar = false;
			
			if (empty($var)) die('变量格式错误.');
			$value .= $this->GetRealVarible($var,$depth);
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
	 * 要解析为 $this->GetRealVarible('name',$depth) . ' . ' . $this->GetRealFunction('substr') . '(' . $this->GetRealVarible('content',$depth) . ',0,10)'
	 *  .  $this->GetRealVarible('config.filename',$depth) . ' . \'...\''
	 * @param $value
	 * @return unknown_type
	 */
	public function ParseValue($value, $depth)
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
						
						if (empty($var)) die('变量格式错误.');
						$return .= $this->GetRealVarible($var,$depth);
						$return .= $spliter;
						$return .= $char;
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
						
						$return .= $this->GetRealFunction($func);
						$return .= $char;
						$func = '';
					}
					elseif($char == '$')
					{
						$InVar = true;
						$return .= $func;
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
			
			if (empty($var)) die('变量格式错误.');
			$return .= $this->GetRealVarible($var,$depth);
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
	public function GetPHP($code)
	{
		return '<' . "?php\n" . $code . "\n?" . ">";
	}
	
	/**
	 * 取得格式化的用户控件
	 * @param $userControl
	 * @param $param
	 * @param $depth
	 * @return unknown_type
	 */
	public function GetUserControlPHP($userControl,$param,$depth)
	{
		$param = trim($param,'/');
		$param = $this->ParseParam($param,$depth);
		$ParamStr = "\$param=array();\n";
		
		foreach($param as $k => $v)
		{
			$ParamStr .= "\$param['$k']=$v;\n";
		}
		
		$ParamStr .=  "\$uc=new Compile();\n\$uc->ShowUserControl('$userControl',\$param);";
		return $this->GetPHP($ParamStr);
	}
	
	public function GetIncludePHP($param,$depth)
	{
		$param = trim($param,'/');
		$param = $this->ParseValue($param,$depth);
	
		$ParamStr .=  "\$uc=new Compile();\n\$uc->IsIncluded=true;\n\$uc->Show('$param');";
		return $this->GetPHP($ParamStr);
	}
	
	/**
	 * 取得自动循环标签的PHP代码
	 * @param $container
	 * @param $param
	 * @param $depth
	 * @return unknown_type
	 */
	public function GetBlockPHP($container,$param,$depth)
	{
		$param = trim($param,'/');
		$param = $this->ParseParam($param,$depth);
		$ParamStr = "\$param=array();\n";
		
		foreach($param as $k => $v)
		{
			$ParamStr .= "\$param['$k']=$v;\n";
		}
		
		$this->Data .= "\$this->ContainerTags[$this->ContainerCount] = new TagData();\n";
		$container = explode('.',$container);
		$this->Data .= "\$this->ContainerTags[$this->ContainerCount]->Method = '" . array_pop($container) . "';\n";
		$this->Data .= "\$this->ContainerTags[$this->ContainerCount]->Container = '" . implode('.',$container) . "';\n";
		
		$ParamStr .=  "\$v=\$this->ContainerTags[$this->ContainerCount]->GetData(\$param);\n";
		$ParamStr .=  "if(\$v)\nforeach(\$this->ContainerTags[$this->ContainerCount]->Data as \$this->TagIndex[$this->Depth] => \$this->TagValue[$this->Depth]){";
		return $this->GetPHP($ParamStr);
	}
	
	/**
	 * 取得窗口返回的数组
	 * @param $container
	 * @param $param
	 * @param $depth
	 * @return unknown_type
	 */
	public function GetArrayPHP($container,$param,$depth)
	{
		$param = trim($param,'/');
		$param = $this->ParseParam($param,$depth);
		$ParamStr = "\$param=array();\n";
		
		foreach($param as $k => $v)
		{
			$ParamStr .= "\$param['$k']=$v;\n";
		}
		
		$this->Data .= "\$this->ContainerTags[$this->ContainerCount] = new TagData();\n";
		$container = explode('.',$container);
		$this->Data .= "\$this->ContainerTags[$this->ContainerCount]->Method = '" . array_pop($container) . "';\n";
		$this->Data .= "\$this->ContainerTags[$this->ContainerCount]->Container = '" . implode('.',$container) . "';\n";
		
		$ParamStr .=  "\$v=\$this->ContainerTags[$this->ContainerCount]->GetData(\$param);\n";
		$ParamStr .=  "if(\$v){\n\$this->TagValue[$this->Depth] = \$this->ContainerTags[$this->ContainerCount]->Data;";
		return $this->GetPHP($ParamStr);
	}
	
	public function RefreshTplCache()
	{
		$now = 0;
		
		$source = file_get_contents($this->SourceFile);
		$dest = '';
		foreach ($this->Tags as $tag)
		{
			$dest .= substr($source,$now,$tag['Start'] - $now);
			$dest .= $tag['php'];
			$now = $tag['End'] + 1;
		}
		$dest .= substr($source,$now);
		
		mk_dir(dirname($this->CacheFile));
		file_put_contents($this->CacheFile,$dest);
		mk_dir(dirname($this->DataFile));
		file_put_contents($this->DataFile,$this->GetPHP($this->Data));
		mk_dir(dirname($this->TimeFile));
		file_put_contents($this->TimeFile,time());
	}
}

class TagData
{
	public $Data;
	public $Container;
	public $Method;
	
	public function __construct()
	{
		$Expire = 0;
	}
	
	public function GetData($param)
	{
		ksort($param);
		$gn = to_guid_string($param);
		$this->Expire = $param['expire'];
		
		$cnt = load_data('datas',$this->Container . '.' . $this->Method,$gn);
		if($cnt)
		{
			$LastGen = substr($cnt,0,12);
			
			if ($this->Expire > 0 && time() - $LastGen < $this->Expire)
			{
				$this->Data = unserialize(substr($cnt,12));
				return $this->Data;
			}
		}
		$container = load_container($this->Container);
		$method = $this->Method;
		$data = $container->$method($param);
		
		$this->Data = $data;
		save_data('datas',$this->Container . '.' . $this->Method,$gn,sprintf('%012d%s',time(),serialize($data)));
		
		return $this->Data;
	}
}

















