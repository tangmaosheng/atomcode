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
define('TAG_BLOCK',8); //块状标签(自动循环标签)
define('TAG_BLOCK_SELF_END',16); //自动结束的块状标签(自动循环标签)
define('TAG_FUNC',32); //函数调用标签
define('TAG_VARIABLE',64); // 可直接输出的变量标签
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
	}
	
	/**
	 * 刷新视图
	 * 重新生成缓存文件和数据文件
	 * @param $view
	 * @return void
	 */
	private function Refresh()
	{
		header('Content-Type:text/plain;charset=' . $this->config['CHARSET']);
		$this->GetTags();
		print_r($this->Tags);
		//file_put_contents($this->CacheFile,file_get_contents($this->SourceFile));
		//file_put_contents($this->DataFile,'<' . "?php\n?" . '>');
		//file_put_contents($this->TimeFile,time());
		//echo 'compiling...';
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
					if ($char != '"' && $char != "'" && $char != "\\")
					{
						$TagString .= '\\';
					}
					
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
								die('line:' . $NowLine . '<br>char:' . $NowOffset);
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
	 * 需要刷新缓存文件
	 * 此处还应该检查源文件是否存在.如果不存在,直接返回 404 错误;如果为包含文件
	 * @param $view
	 * @return bool
	 */
	private function NeedRefresh($view)
	{
		$this->SourceFile = APP_PATH . '/views/' . $view . $this->ViewExt;
		$this->CacheFile	= APP_PATH . '/cache/views/' . $view . '.php';
		$this->DataFile	= APP_PATH . '/cache/datas/' . $view . '.php';
		$this->TimeFile	= APP_PATH . '/cache/datas/' . $view . '.t';
		
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
			trigger_error('包含文件不存在!<br>源:' . APP_PATH . '/views/' . $this->ViewPath . $this->ViewExt . '<br />行:' . $this->LineNum,E_COMPILE_ERROR);
		}
		elseif ($this->IsUserControl) #用户控件
		{
			trigger_error('用户控件不存在!<br>源:' . APP_PATH . '/views/' . $this->ViewPath . $this->ViewExt . '<br />行:' . $this->LineNum,E_COMPILE_ERROR);
		}
	}
	
	/**
	 * 显示缓存中的文件
	 * @param $view
	 * @return void
	 */
	public function Show($view)
	{
		$this->ViewPath = $view;
		$this->ViewName = end(explode('/',$view));
		
		if ($this->NeedRefresh($view))
		{
			$this->Refresh();
		}
		
		//include $this->DataFile;
		//include $this->CacheFile;
	}
}

/**
 * 存储标签信息
 * 仅仅记录标签的基本信息不做任何处理
 * @author Eachcan
 *
 */
class Tag
{
	/* 标签开始位置 */
	public $Start;
	
	/* 标签结束的位置 */
	public $End;
	
	/* 所在视图 */
	public $ViewName;
	
	/* 标签索引 */
	public $Index;
	
	/* 是否为输出标签 */
	public $Out;
	
	/**
	 * 
	 * @var enum(statement,)
	 */
	public $Type;
	
	/* 标签指令 */
	public $Cmd;
	
	/**
	 *  标签参数
	 */
	public $Param;
	
	/* 标签体 */
	public $Body;
	
	/**
	 * 所在行
	 * @var int
	 */
	public $Line;
}

/**
 * 存储标签数据,并在合适的时候更新数据
 * @author Eachcan
 *
 */
class TagData
{
	public $TimeGen;
	public $Expire;
	public $Container;
	public $Param;
	private $Data;
	
	function GetData()
	{
		if(($this->Expire > -1) && (empty($this->TimeGen) || (time() - $this->TimeGen > $this->Expire)))
		{
			echo $this->TimeGen;
			$this->TimeGen = time();
			$this->Data = date('H:i:s',$this->TimeGen);
		}
		
		return $this->Data;
	}
}

/**
 * 验证是不是一个有效的标签,并返回解析后的标签体
 * @param $Body
 * @return unknown_type
 */
function TagValidater(&$Tag)
{
	
}














