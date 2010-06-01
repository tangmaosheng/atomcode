<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@cncms.com>
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2010-05-28
 * @filesource /system/core/common.php
 * this file list some common functions, without actually actions
 */
if ( ! defined('APP_PATH')) die('Connot start application.');

/**
 * Tests for file writability
 *
 * is_writable() returns TRUE on Windows servers when you really can't write to 
 * the file, based on the read-only attribute.  is_writable() is also unreliable
 * on Unix servers if safe_mode is on. 
 *
 * @access	public
 * @return	bool
 */
function is_really_writable($file)
{	
	// If we're on a Unix server with safe_mode off we call is_writable
	if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == FALSE)
	{
		return is_writable($file);
	}

	// For windows servers and safe_mode "on" installations we'll actually
	// write a file then read it.  Bah...
	if (is_dir($file))
	{
		$file = rtrim($file, '/').'/'.md5(rand(1,100));

		if (($fp = @fopen($file, 'ab')) === FALSE)
		{
			return FALSE;
		}

		fclose($fp);
		@unlink($file);
		return TRUE;
	}
	elseif (($fp = @fopen($file, 'ab')) === FALSE)
	{
		return FALSE;
	}

	fclose($fp);
	return TRUE;
}

/**
 * Try to include files in list
 * This function require the first existed file in list, if fail, return false.
 * 
 * file name will be transfer to lowercase,So please keep all files' name is lowercase.
 * @return bool
 */
function try_require()
{
	$success	= false;
	
	foreach (func_get_args() as $file)
	{
		if (file_exists(strtolower($file)))
		{
			require $file;
			$success = true;
			break;
		}
	}
	
	return $success;
}

/**
* Class loader
*
* This function can load a class from user's class libraries,if not exists
* load from system folder.
* if neither,return false; 
*
* @access	public
* @param	string	the class name being requested
* @param	bool	optional flag that lets classes get loaded but not instantiated
* @return	object
*/
function &load_class($class, $instantiate = TRUE,$path = 'libraries')
{
	// If the requested class does not exist in the application/libraries
	// folder we'll load the native class from the system/libraries folder.	
	if (!class_exists($class))
	{
		try_require(
			APP_PATH . strtolower(DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $class . '.class.php'), 
			SYS_PATH . strtolower(DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $class . '.class.php'));
	}
	
	if (class_exists($class))
	{
		if ($instantiate)
		{
			return new $class();
		}
		
		return TRUE;
	}
	
	return FALSE;
}

/**
 * Loads model from user's models folder or Atomcode's models folder
 * 
 * @param $model
 * @return unknown_type
 */
function &load_model($model)
{
	global $var;
	
	$model = $var->config['MODEL_PREFIX'] . $model . $var->config['MODEL_SUFFIX'];

	return load_class($model, TRUE, 'models');
}

/**
 * Loads container from user's application folder
 * 
 * @param $container
 * @return object
 */
function &load_container($container)
{
	global $var;
	
	$pieces	= explode('/', $container);
	$class = array_pop($pieces);
	array_unshift($pieces, 'containers');
	
	$class = $var->config['CONTAINER_PREFIX'] . $class . $var->config['CONTAINER_SUFFIX'];
	
	return load_class($class, TRUE, implode(DIRECTORY_SEPARATOR, $pieces));
}

/**
 * Loads helper from user's helpers folder or Atomcode's helpers folder
 * 
 * @param $helper
 * @return unknown_type
 */
function load_helper($helper)
{
	static $objects = array();

	// Does the class exist?  If so, we're done...
	if (isset($objects[$helper]))
	{
		return $objects[$helper];
	}
	
	return $objects[$helper] = try_require(
								APP_PATH . strtolower(DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $helper . '.php'), 
								SYS_PATH . strtolower(DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $helper . '.php'));
}

/**
 * load factories from system folder.It's recommend to use 'factory' mode to do 
 * complex functions.
 * the factory must has static method getInstance
 * @param $factory
 * @return object
 */
function &load_factory($factory)
{
	load_class($factory,FALSE,'factory/' . $factory);
	eval('$s='.$factory . " :: getInstance();");
	return $s;
}

/**
 * load config from user's folder
 * @param $config
 * @return unknown_type
 */
function load_config($config_name)
{
	static $objects = array();
	global $config;
	// Does the class exist?  If so, we're done...
	if (isset($objects[$config_name]))
	{
		return $objects[$config_name];
	}
	
	return $objects[$config_name] = try_require(APP_PATH . strtolower(DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . $config_name . '.cfg.php'));
}

/**
 * load language from user's folder
 * Need config:$config['lang']
 * @param $lang_name
 * @return unknown_type
 */
function &load_lang($lang_name)
{
	static $objects = array();
	
	global $lang,$var;

	// Does the class exist?  If so, we're done...
	if (isset($objects[$lang_name]))
	{
		return $objects[$lang_name];
	}
	
	if (file_exists(APP_PATH . '/language/' . $var->config['lang'] . '/' . $lang_name . '.lang.php'))
	{
		require(APP_PATH . '/language/' . $var->config['lang'] . '/' . $lang_name . '.lang.php');
		return $lang;
	}
	else
	{
		return false;
	}
}

/**
* Loads the main config.php file
*
* @access	private
* @return	array
*/
function &get_config()
{
	global $config;
	static $loaded = false;

	if (!$loaded)
	{
		if (!file_exists(APP_PATH . '/configs/config.php'))
		{
			exit('The configuration file `config.php` does not exist.');
		}

		require(APP_PATH . '/configs/config.php');

		if ( ! isset($config) OR ! is_array($config))
		{
			exit('Your config file does not appear to be formatted correctly.');
		}
		$loaded = true;
	}
	return $config;
}

/**
 * avoid quotes attack
 * @param $string
 * @param $force
 * @return unknown_type
 */
function xaddslashes($string, $force = 0)
{
	//from now on,we use system setting,although we have set it.
	!defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
	
	if(!MAGIC_QUOTES_GPC || $force) 
	{
		if(is_array($string)) 
		{
			foreach($string as $key => $val) 
			{
				$string[$key] = xaddslashes($val, $force);
			}
		}
		else
		{
			$string = addslashes($string);
		}
	}
	
	return $string;
}

/**
 * geniuse a unique flag
 * 
 * @param mixed $mix variable
 * 
 * @return string
 */
function to_guid_string($mix)
{
	if(is_object($mix) && function_exists('spl_object_hash'))
	{
		return spl_object_hash($mix);
	}
	elseif(is_resource($mix))
	{
		$mix = get_resource_type($mix).strval($mix);
	}
	else
	{
		$mix = serialize($mix);
	}
	
	return md5($mix);
}

/**
 * if the object is a instance of the class
 *
 * @param mixed $object instance
 * @param mixed $className class name
 *
 * @return boolean
 */
function is_instance_of($object, $class_name)
{
	if (!is_object($object) && !is_string($object)) 
	{
		return false;
	}
	return $object instanceof $class_name;
}

/**
 * get a instance of a class
 * @param $className class name
 * @param $method method name
 * @param $args parameters addtion
 * @return unknown_type
 */
function get_instance_of($class_name,$method='',$args=array())
{
    static $_instance = array();
    if(empty($args)) 
    {
        $identify   =   $class_name.$method;
    }
    else
    {
        $identify   =   $class_name.$method.to_guid_string($args);
    }
    
    if (!isset($_instance[$identify])) 
    {
        if(class_exists($class_name))
        {
            $o = new $class_name();
            if(method_exists($o,$method))
            {
                if(!empty($args)) 
                {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                }
                else 
                {
                    $_instance[$identify] = $o->$method();
                }
            }
            else
            {
                $_instance[$identify] = $o;
            }
        }
        else
        {
            trigger_error('class not exists.', E_USER_ERROR);
        }
    }
    
    return $_instance[$identify];
}

/**
 * auto convert encoding
 * models required: iconv or mb_string
 * same encoding will be ignored
 *
 * @param string $fContents string to be converted
 * @return string
 */
function auto_charset($contents,$from='',$to='')
{
	global $var;
	
	if(empty($from)) $from = $var->config['CHARSET'];
	if(empty($to))  $to =   $var->config['CHARSET'];
	
	$from   =  strtoupper($from)=='UTF8'? 'utf-8':$from;
	$to	   =  strtoupper($to)=='UTF8'? 'utf-8':$to;
	
	if( strtoupper($from) === strtoupper($to) || empty($contents) || (is_scalar($contents) && !is_string($contents)))
	{
		//same encoding
		return $contents;
	}
	
	if(is_string($contents))
	{
		if(function_exists('mb_convert_encoding'))
		{
			return mb_convert_encoding ($contents, $to, $from);
		}
		elseif(function_exists('iconv'))
		{
			return iconv($from,$to,$contents);
		}
		else
		{
			throw new Exception('Extension for encoding convert is not exists.');
			return $contents;
		}
	}
	elseif(is_array($contents))
	{
		foreach ( $contents as $key => $val ) 
		{
			$_key =	 auto_charset($key,$from,$to);
			$contents[$_key] = auto_charset($val,$from,$to);
			if($key != $_key ) 
			{
				unset($contents[$key]);
			}
		}
		return $contents;
	}
	elseif(is_object($contents)) 
	{
		$vars = get_object_vars($contents);
		foreach($vars as $key=>$val) 
		{
			$contents->$key = auto_charset($val,$from,$to);
		}
		return $contents;
	}
	else
	{
		return $contents;
	}
}

/**
 * make diretory
 * @param $dir
 * @param $mode
 * @return unknown_type
 */
function mk_dir($dir, $mode = 0755)
{
  if (is_dir($dir) || @mkdir($dir,$mode)) return true;
  if (!mk_dir(dirname($dir),$mode)) return false;
  return @mkdir($dir,$mode);
}
/**
 * if request method is post
 * @return unknown_type
 */
function is_post()
{
	return strtolower($_SERVER['REQUEST_METHOD']) == 'post';
}
/**
 * if request with ajax form
 * @return unknown_type
 */
function is_ajax() 
{
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) 
	{
		if(strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest')
			return true;
	}
	
	if(!empty($_POST[$var->config['VAR_AJAX_SUBMIT']]) || !empty($_GET[$var->config['VAR_AJAX_SUBMIT']]))
	{
		return true;
	}
	
	return false;
}

/**
 * load data from your application cache file
 * @param $base
 * @param $nextUrl
 * @param $fn
 * @return unknown_type
 */
function load_data($base,$next_url,$fn)
{
	$path = APP_PATH . '/cache/' . $base . '/' . str_replace('.','/',$next_url) . '/' . $fn;
	if (!is_file($path))return false;
	return file_get_contents($path);
}

/**
 * save data to your cache file
 * @param $base 子目录
 * @param $nextUrl 下级目录
 * @param $fn 文件名
 * @param $content 内容
 * @return bool
 */
function save_data($base, $next_url, $fn, $content)
{
	$path = APP_PATH . '/cache/' . $base . '/' . str_replace('.','/',$next_url);
	mk_dir($path);
	
	$path .= '/' . $fn;
	return file_put_contents($path,$content);
}

/**
 * execution time
 * @return unknown_type
 */
function exec_time()
{
	global $system_start_time;
	$Time = microtime(true);
	if (!is_float($Time))$Time = array_sum(explode(' ',$Time));
	$exec_time = $Time - $system_start_time;
	
	$system_start_time = $Time;
	return $exec_time;
}

/**
 * an simple encrypt althmoligy
 *
 * @param string $String
 * @param Bool $Operation True encode|False Decode
 * @param string $Key
 * @return string Encoded string
 */
function encrypt($string,$encode=true,$key = '')
{
	global $var;
	$key = md5(($key) ? $key : $var->config['key']['cookie']);
	$key_length = strlen($key);
	
	$string = !$encode ? base64_decode($string):substr(md5($string.$key),0,8).$string;
	$string_length = strlen($string);
	
	$rnd_key = $box = array();
	$result = '';
	
	for($i = 0;$i <= 255;$i++){
		$rnd_key[$i] = ord($key{$i % $key_length});
		$box[$i] = $i;
	}
	for($j = $i = 0;$i < 256;$i++){
		$j = ($j + $box[$i] + $rnd_key[$i])% 256;
		$tmp = $box[$i];
		$box[$i] = $box[$i];
		$box[$j] = $tmp;
	}
	for($a = $j = $i = 0;$i < $string_length;$i++){
		$a = ($a + 1)% 256;
		$j = ($j + $box[$a])% 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	
	if(!$encode){
		if(substr($result,0,8) == substr(md5(substr($result,8).$key),0,8)){
			return substr($result,8);
		}else{
			return '';
		}
	}else{
		return str_replace('=','',base64_encode($result));
	}
}

/**
 * get visitor's IP address
 * @return unknown_type
 */
function get_ip()
{
	if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'),'unknown')){
		$user_ip = getenv('HTTP_CLIENT_IP');
	} elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'),'unknown')){
		$user_ip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'),'unknown')){
		$user_ip = getenv('REMOTE_ADDR');
	} elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'],'unknown')){
		$user_ip = $_SERVER['REMOTE_ADDR'];
	}
	if (!is_ip($user_ip)){
		$user_ip = 'unknown';
	}
	return $user_ip;
}

/**
 * if given string is a IP address
 * @param $ip
 * @return unknown_type
 */
function is_ip($ip)
{
	$ips = explode('.',$ip);
	if(count($ips) && $ips[0] > 0 && $ips[0] < 255 && $ips[1] >= 0 && $ips[1] <= 255 && $ips[2] >= 0 && $ips[2] <= 255 && $ips[3] >= 0 && $ips[3] <= 255)
	{
		return TRUE;
	}
	return FALSE;
}

/**
 * genate random numbers, or string
 *
 * @param int $Length
 * @param int $OnlyNum
 * @return string
 */
function random($length , $only_num = 1){
	if($only_num){
		return sprintf("%0".$length."d",mt_rand(0,pow(10,$length)));
	}else{
		$hash_base = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
		$max_value = strlen($hash_base) - 1;
		$hash = '';
		for($i = 0;$i < $length;$i++){
			$hash .= $hash_base{mt_rand(0,$max_value)};
		}
		return $hash;
	}
}

/**
 * simple record time method.
 *
 * @param string $str
 */
function parse_time_to_second($str)
{
	if (is_numeric($str))
	{
		return intval($str);
	}
	else 
	{
		$str = strtolower($str);
		$total = 0; 
		$num = '';
		$units = array(
			's' => 1,		# secod
			'm' => 60,		# minute
			'h' => 3600,	# hour
			'd' => 86400,	# day
			'w' => 604800,	# week 7d
			'l' => 2592000, # luna 30d
			'y' => 31536000,# year 365d
		);
		
		for ($i = 0; $i < strlen($str); $i ++)
		{
			$char = $str{$i};
			if (is_numeric($char))
			{
				$num .= $char;
			}
			elseif (array_key_exists($char,$units))
			{
				$total += intval($num) * $units[$char];
				$num = '';
			}
		}
		
		if ($num)
		{
			$total += $num;
		}
	}
	
	return $total;
}

/**
 * get extention name of a file, 
 * PHP don't try to parse a file name in some condition,
 * this function will help
 * @param $filename
 * @return string
 */
function get_ext($filename)
{
	return strtolower(trim(substr(strrchr($filename, '.'), 1)));
}

/**
 * Test the given word is a reserved word or not.
 * @param $word
 * @return unknown_type
 */
function is_reserved($word)
{
	$reserved = ',list,new,and,or,xor,array,as,break,case,class,const,continue,default,do,else,empty,exit,for,function,global,if,switch,use,var,while,final,public,extends,private,protected,abstract,clone,try,catch,throw,this,static,';
	return strpos($reserved, ",$word,") !== false;
}
if (!function_exists('lcfirst'))
{
	function &lcfirst(&$string)
	{
		$string{0} = strtolower($string{0});
		return $string;
	}
}