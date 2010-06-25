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
	private $preContent;
	
	public $config;
	private $fromFile;
	private $viewExt;
	
	private $tags;
	private $helpers;
	private $tagInfo;
	private $stack;
	
	private $regx;
	
	private $useLevel=0;

	public function __construct()
	{
		global $var;
		$this->config	=& $var->config;

		$this->tags = array();
		$this->stack = array();
		$this->viewExt	= empty($this->config['VIEW_EXT']) ? '.html' : $this->config['VIEW_EXT'];
		#resource
		$this->helpers[] = 'common';
		$this->containers = array();
		$this->ctnId = 0;
		$this->tagLevel = 0; 
		$this->useLevel = 0; 
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
		if ($this->sourceCode == '')
		{
			return;
		}
		
		if (empty($this->sourceFile))
		{
			$this->sourceFile = APP_PATH . '/views/';
		}
		# pre-process
		$this->sourceCode = $this->preProcess($this->sourceCode, $this->sourceFile);
		# scan tags and replace them with php code
		$this->getTags();
		$this->parseTags();
		$this->preLoad();
		$this->parseContents();
		# clean up the destination code
		$this->cleanUp();
	}
	
	/**
	 * process `include` and `use` tags
	 * 
	 * @return string
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
	 * @return string
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
	 * @return void
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
	
	public function getCleanPHP(&$code)
	{
		$comment = '~\/\*.*?\*\/~s';
		$code = preg_replace($comment, '', $code);
		$comment = '/([\r\n](?:"[^"\\\\\\$]*(?:\\\\.[^"\\\\\\$]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|[^\r\n\'\"])*)\/\/.*?([\r\n])/';
		$code = preg_replace($comment, '$1$2', $code);
		$comment = '/([\r\n](?:"[^"\\\\\\$]*(?:\\\\.[^"\\\\\\$]*)*"|\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'|[^\r\n\'\"])*)#.*?([\r\n])/';
		$code = preg_replace($comment, '$1$2', $code);
		$comment = '/\s{2,}/';
		if (COMPILE_STRIP_SPACE)$code = preg_replace($comment, ' ', $code);
		return $code;
	}
	
	private function cleanUp()
	{
		$this->destCode = preg_replace('~\?\>\s*\<\?php~', '', $this->destCode);
		if (COMPILE_STRIP_SPACE)
		{
			$this->destCode = preg_replace('/\s{2,}/', ' ', $this->destCode);
		}
	}
	
	private function preLoad()
	{
		$this->destCode = '';
		if (is_array($this->helpers))
		{
			$this->helpers = array_unique($this->helpers);
			
			foreach ($this->helpers as $helper)
			{
				$h = load_helper($helper, 1);
				$this->destCode .= $this->getCleanPHP($h);
			}
		}
		if (is_array($this->containers))
		{
			$this->containers = array_unique($this->containers);
			foreach ($this->containers as $container)
			{
				$pieces	= explode('.', $container);
				array_pop($pieces);
				$class = array_pop($pieces);
				$class = $this->config['CONTAINER_PREFIX'] . $class . $this->config['CONTAINER_SUFFIX'];
				
				if (file_exists(APP_PATH . strtolower(DIRECTORY_SEPARATOR . 'containers' . DIRECTORY_SEPARATOR . $class . '.class.php')))
				{
					$h = file_get_contents(APP_PATH . strtolower(DIRECTORY_SEPARATOR . 'containers' . DIRECTORY_SEPARATOR . $class . '.class.php'));
				}
				elseif (file_exists(SYS_PATH . strtolower(DIRECTORY_SEPARATOR . 'containers' . DIRECTORY_SEPARATOR . $class . '.class.php')))
				{
					$h = file_get_contents(SYS_PATH . strtolower(DIRECTORY_SEPARATOR . 'containers' . DIRECTORY_SEPARATOR . $class . '.class.php'));
				}
				else
				{
					$h = '';echo $class;
				}
				$this->destCode .= $this->getCleanPHP($h);
			}
		}
	}
	
	/**
	 * translate templet language to php code
	 * @param $code
	 * @return void
	 */
	private function parseContents()
	{
		$last = 0;

		foreach ($this->tagInfo as &$tagInfo)
		{
			$this->destCode .= substr($this->sourceCode, $last, $tagInfo['start'] - $last);
			$this->destCode .= $tagInfo['code'];
			
			$last = $tagInfo['end'];
		}
		$this->destCode .= substr($this->sourceCode, $last);
	}
	
	/**
	 * get the effective templet tags
	 * @param $code
	 * @return void
	 */
	private function getTags()
	{
		# match "helo\"helo", or single quote
		$this->regx['dbquote'] = '"[^"\\\\\\$]*(?:\\\\.[^"\\\\\\$]*)*"';
		$this->regx['sgquote'] = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';
		$this->regx['quote'] = '(?:' . $this->regx['dbquote'] . '|' . $this->regx['sgquote'] . ')';
		
		# match bracket portion of variable
		$this->regx['bracket_var'] = '\[\$?[\w\.]+\]|\.\$?[\w\.]+';
		# match $var.var[num]
		$this->regx['var'] = '\$\w+(?:' . $this->regx['bracket_var'] . ')*';
		
		# match number
		$this->regx['number'] = '(?:\-?\d+(?:\.\d+)?)';
		# operator + - * / %
        $this->regx['num_op'] = '(?:[\+\*\/\%]|(?:-(?!>)))';
        # match > < >= <= ==
        $this->regx['comp'] = '(?:<|>|==|>=|<=)';
        # match $ab.c[e].f
        $this->regx['complex_var'] = $this->regx['var'] . '(?:\s*' . $this->regx['num_op'] . '\s*(?:' . $this->regx['number'] . '|' . $this->regx['var'] . '))*';
        # match |func:param
        $this->regx['modifier'] = '(?:\|\w+(?::(?:' . $this->regx['number'] . '|' . $this->regx['var'] . '|' . $this->regx['quote'] . '))*)';
        # match $var.var or 123 or "string"
        $this->regx['value'] = '(?:' . $this->regx['complex_var'] . '|' . $this->regx['number'] . '|' . $this->regx['quote'] . ')';
        # match a.b.c
        $this->regx['cnt'] = '\w+(?:\.\w+)+';
        # match abc=$var, efg=0, hig="string"
        $this->regx['param'] = '(?:\s+\w+\s*=\s*' . $this->regx['value'] . '(?:\s*,\s*\w+\s*=\s*' . $this->regx['value'] . ')*\s*)?';
        
        #variable for perl regular
        $this->preg['var'] = "~\{" . $this->regx['complex_var'] . $this->regx['modifier'] . "*\}~";
        $this->preg['helper'] = "~\{helper\s+\w+\}~";
        $this->preg['lang'] = "~\{lang\s+\w+\}~";
        $this->preg['mid'] = "~\{(?:else|continue|break)\}~";
        $this->preg['end'] = "~\{\/(?:if|loop|each|use)?\}~";
        $this->preg['if'] = '~\{(?:if|elseif)\s+' . $this->regx['value'] . '(?:\s*' . $this->regx['comp'] . '\s*' . $this->regx['value'] . ')?\}~';
        $this->preg['loop'] = '~\{loop\s+\$\w+\s+\$\w+(?:\s+\$\w+)?\}~';
        $this->preg['each'] = '~\{each(?:\s+\$\w+)?\}~';
        $this->preg['cnt'] = '~\{' . $this->regx['cnt']  . $this->regx['param'] . '\}~';
        $this->preg['use'] = '~\{use'  . $this->regx['param'] . '\}~';
        $this->preg['comment'] = '~\{\*.*?\*\}~';
        
        foreach ($this->preg as $key => $value)
        {
        	preg_match_all($value, $this->sourceCode, $$key, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        }
        $this->tags = array_merge($this->tags, $var, $lang, $helper, $mid, $end, $if, $loop, $each, $cnt, $comment, $use);
	}

	/**
	 * process the tags
	 * @return void
	 */
	private function parseTags()
	{
		foreach ($this->tags as $tag)
		{
			$this->tagInfo[$tag[0][1]]['tag'] = $tag[0][0];
			$this->tagInfo[$tag[0][1]]['len'] = strlen($tag[0][0]);
			$this->tagInfo[$tag[0][1]]['start'] = $tag[0][1];
			$this->tagInfo[$tag[0][1]]['end'] = $tag[0][1] + $this->tagInfo[$tag[0][1]]['len'];
		}
		
		ksort($this->tagInfo);
		
		foreach ($this->tagInfo as &$tag_info)
		{
			$tag_info['code'] = $this->getPHP($this->getCode($tag_info['tag']));
		}
	}
	
	/**
	 * get php code for tags
	 * @param $tag
	 * @return unknown_type
	 */
	private function getCode($tag)
	{
		$regx = '~^\{((?:\/|\$|\*)?\w*)~';
		preg_match($regx, $tag, $matches);

		switch($matches[1])
		{
			case 'use':
				return $this->compileUse($tag);
				break;
				
			case 'lang':
				return $this->compileLang($tag);
				break;
				
			case 'helper':
				return $this->compileHelper($tag);
				break;
				
			case 'if':
			case 'elseif':
				return $this->compileIf($tag, $matches[1]);
				break;
				
			case 'loop':
				return $this->compileLoop($tag);
				break;
				
			case 'each':
				return $this->compileEach($tag);
				break;
				
			case 'continue':
			case 'break':
			case 'else':
				return $this->compileMid($tag);
			break;
			
			default:
				if (substr($matches[1], 0, 1) == '$')
				{
					return $this->compileExp($tag);
				}
				elseif (substr($matches[1], 0, 1) == '/')
				{
					return $this->compileEnd($tag);
				}
				elseif (substr($matches[1], 0, 1) == '*')
				{
					return '';
				}
				else
				{
					return $this->compileContainer($tag);
				}
		}
	}
	/**
	 * get php code around by php tags;
	 * @param $code
	 * @return unknown_type
	 */
	public function getPHP($code)
	{
		if (!empty($code))return "<" . "?php " . $code . "?" . ">";
		return '';
	}
	
	private function compileEnd($tag)
	{
		$regx_end = "~\{\/(if|loop|each|use)?\}~";
		preg_match($regx_end, $tag, $matches);
		
		if ($matches[1] == 'if')
		{
			if (array_pop($this->stack) != 'if')
			{
				die('Missing a if');
			}
			
			return '}';
		}
		elseif ($matches[1] == 'loop')
		{
			return '}';
		}
		elseif ($matches[1] == 'use')
		{
			return 'unset($this->var[' . $this->useLevel . ']);';
		}
		elseif ($matches[1] == 'each')
		{
			$this->tagLevel --;
			return '}';
		}
		elseif ($matches[1] == '')
		{
			$this->tagLevel --;
			return 'unset($this->containers[' . $this->useLevel . ']);';
		}
	}
	
	/**
	 * compile expression
	 * @param $tag
	 * @return unknown_type
	 */
	private function compileExp($tag)
	{
		$complex_var = '~^{(' . $this->regx['complex_var'] . ')~';
		preg_match($complex_var, $tag, $matches);
		$cmd = $matches[1];
		$modifier = '~' . $this->regx['modifier'] . '~';
		preg_match_all($modifier, $tag, $funcs);
		
		return 'echo ' . $this->compileModifier($this->compileVar($cmd), $funcs) . ';';
	}
	
	private function compileVar($str)
	{
		$str = preg_replace('~\.(\$?\w+)~e', 'preg_match(\'~^(?:\$\w+|\d+)$~\', \'$1\') ? "[$1]" : "[\'$1\']"', $str);
		$str = preg_replace('~\$(\d*)(\w+)~e', '$this->compileViewVar("$1", "$2")', $str);
		
		return $str;
	}
	
	private function compileViewVar($level, $var_name)
	{
		if ($level === '')
		{
			if (in_array($var_name, array('config', 'get', 'post', 'input', 'cookie', 'session', 'lang')))
			{
				return '$this->' . $var_name;
			}
			elseif ($var_name == 'var')
			{
				return '$this->var[' . $this->useLevel . ']'; 
			}
			else
			{
				return '$this->tagValue[' . $this->tagLevel .']["' . $var_name . '"]'; 
			}			
		}
		else
		{
			if ($var_name == 'var')
			{
				return '$this->var[' . $level . ']'; 
			}
			else
			{
				if ($level < 0) $level = $this->tagLevel + $level;
				return '$this->tagValue[' . $level . ']["' . $var_name . '"]'; 	
			}
		}
	}
	
	private function compileModifier($var, $funcs)
	{
		$regx_func = '~\|(\w+)(?::(' . $this->regx['number'] . '|' . $this->regx['var'] . '|' . $this->regx['quote'] . '))*~';
		foreach ($funcs[0] as $func)
		{
			preg_match($regx_func, $func, $matches);
			$func_name = $matches[1];
			$matches = array_slice($matches, 2);
			foreach ($matches as &$m)
			{
				$mstring .= ',' . $this->compileVar($m);
			}
			$var = "ac_" . $func_name . "($var $mstring)";
		}
		return $var;
	}
	
	private function compileHelper($tag)
	{
		$regx_helper = '~\{helper\s*(\w+)\}~';
		preg_match($regx_helper, $tag, $matches);
		
		$this->helpers[] = $matches[1];
		return '';
	}
	
	private function compileLang($tag)
	{
		$regx_helper = '~\{lang\s*(\w+)\}~';
		preg_match($regx_helper, $tag, $matches);
		
		return 'load_lang("' . $matches[1] . '");';
	}
	
	private function compileUse($tag)
	{
		$regx_use = '~(\w+)\s*=\s*(' . $this->regx['value'] . ')~';
		preg_match_all($regx_use, $tag, $matches);
		
		$this->useLevel++;
		for ($i = 0; $i < count($matches[1]); $i ++)
		{
			$string .= '$this->var[' . $this->useLevel . ']["' . $matches[1][$i] . '"]=' . $this->compileVar($matches[2][$i]) . ';';
		}
		
		return $string;
	}
	
	private function compileIf($tag, $name)
	{
		$name == 'if' && $this->stack[] = 'if';
		
		if ($name == 'elseif' && end($this->stack) != 'if')
		{
			die('Missing if tag');
		}
		
		$regx_if = '~if\s+(' . $this->regx['value'] . ')(?:\s*(' . $this->regx['comp'] . ')\s*(' . $this->regx['value'] . '))?~';
		preg_match($regx_if, $tag, $matches);
		
		$string = '';
		if ($name == 'elseif')$string = '}';

		return $string . $name . '(' . $this->compileVar($matches[1]) . $matches[2] . $this->compileVar($matches[3]) . '){';
	}
	
	private function compileLoop($tag)
	{
		$regx_loop = '~\{loop\s+(\$\w+)\s+(\$\w+)(?:\s+(\$\w+))?\}~';
		
		preg_match($regx_loop, $tag, $matches);
		$string = 'foreach (' . $this->compileVar($matches[1]) . ' as ' . $this->compileVar($matches[2]);
		if (!empty($matches[3]))
		{
			$string .= ' => ' . $this->compileVar($matches[3]);
		}
		
		$string .= '){';
		return $string;
	}
	
	private function compileMid($tag)
	{
		$tag = trim(substr($tag, 1, -1));
		
		if ($tag == 'else')
		{
			return '}else{';
		}
		return $tag . ';';
	}
	
	private function compileContainer($tag)
	{
		$regx_ctn = '~\{(' . $this->regx['cnt'] . ')('  . $this->regx['param'] . ')\}~';
		preg_match($regx_ctn, $tag, $matches);
		
		$this->ctnId++;
		$this->tagLevel ++;
		$this->containers[] = $matches[1];
		$param_string = $matches[2];
		$cn = array_slice(explode('.', $matches[1]), -2);
		$cn[0] = $this->config['CONTAINER_PREFIX'] . $cn[0] . $this->config['CONTAINER_SUFFIX'];

		$regx_param = '~(\w+)\s*=\s*(' . $this->regx['value'] . ')~';
		preg_match_all($regx_param, $param_string, $matches);
		
		$string = '$param=array();';
		for ($i = 0; $i < count($matches[1]); $i ++)
		{
			$string .= '$param["' . $matches[1][$i] . '"]=' . $this->compileVar($matches[2][$i]) . ';';
		}
		$string .= '$this->containers[' . $this->ctnId . ']=new ' . $cn[0] . '();';
		$string .= '$this->tagValue[' . $this->tagLevel . ']=$this->containers[' . $this->ctnId . ']->' . $cn[1] . '($param);';
		
		return $string;
	}
	
	private function compileEach($tag)
	{
		$regx_each = '~\{each(?:\s+\$(\w+))\s*}~';
		preg_match($regx_each, $tag, $matches);

		if (empty($matches[1]))
		{
			$this->tagLevel ++;
			return 'foreach ($this->tagValue[' . ($this->tagLevel - 1) . '] as $this->tagValue[' . $this->tagLevel . ']){';
		}
		else
		{
			$this->tagLevel ++;
			return 'foreach ($this->tagValue[' . ($this->tagLevel - 1) . ']["' . $matches[1] . '"] as $this->tagValue[' . $this->tagLevel . ']){';
		}
	}
}