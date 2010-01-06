<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@gmail.com>
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 
 * @filesource /system/core/common.php
 * 本文件列出普通的一些函数.并不做任何处理.
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
	static $objects = array();

	// Does the class exist?  If so, we're done...
	if (isset($objects[$path . '/' . $class]))
	{
		if ($instantiate && !is_object($objects[$path . '/' . $class]))$objects[$path . '/' . $class] = & new $class();
		return $objects[$path . '/' . $class];
	}
	// If the requested class does not exist in the application/libraries
	// folder we'll load the native class from the system/libraries folder.	
	if (file_exists(APP_PATH.'/' . $path . '/'.$class.'.class.php'))
	{
		require_cache(APP_PATH.'/' . $path . '/'.$class.'.class.php');
	}
	else
	{
		if (file_exists(SYS_PATH.'/' . $path . '/'.$class.'.class.php'))
		{
			require_cache(SYS_PATH.'/' . $path . '/'.$class.'.class.php');
		}
		else
		{
			return false;
		}
	}
	
	if ($instantiate == FALSE)
	{
		$objects[$path . '/' . $class] = TRUE;
		return $objects[$path . '/' . $class];
	}
	
	$objects[$path . '/' . $class] =& new $class();
	return $objects[$path . '/' . $class];
}

/**
 * Loads container from user's application folder
 * 
 * @param $container
 * @return unknown_type
 */
function &load_container($container)
{
	static $objects = array();

	// Does the class exist?  If so, we're done...
	if (isset($objects[$container]))
	{
		return $objects[$container];
	}
	
	$real_container = end(explode('/', $container));
	
	//find containers in application path or system path.
	if (file_exists(APP_PATH . '/containers/' . $container . '.php'))
	{
		require_cache(APP_PATH . '/containers/' . $container . '.php');
	}
	else
	{
		if (file_exists(SYS_PATH.'/containers/' . $container . '.php'))
		{
			require_cache(SYS_PATH.'/containers/' . $container . '.php');
		}
		else
		{
			return false;
		}
	}
	
	if (!class_exists($real_container)) return false;
	
	$objects[$container] =& new $real_container();
	return $objects[$container];
}

/**
 * Loads model from user's application folder
 * 
 * @param $model
 * @return unknown_type
 */
function &load_model($model)
{
	global $var;
	static $objects = array();
	
	$_model = $var->config['MODEL_CLASS_PREFIX'] . $model . $var->config['MODEL_CLASS_SUFFIX'];
	// Does the class exist?  If so, we're done...
	if (isset($objects[$model]))
	{
		return $objects[$model];
	}
	
	if (file_exists(APP_PATH . '/models/' . $model . '.php'))
	{
		require_cache(APP_PATH . '/models/' . $model . '.php');
	}
	else
	{
		return false;
	}
	
	$objects[$model] = & new $_model();
	return $objects[$model];
}

/**
 * Loads model from user's application folder
 * 
 * @param $model
 * @return unknown_type
 */
function &load_controller($controller,$controller_dir='')
{
	global $var;
	static $objects = array();
	
	if ($controller_dir)
	{
		$controller_dir = str_replace('.','/',$controller_dir);
		
		$controller_dir = trim($controller_dir,'/') . '/';
	}
	
	// Does the class exist?  If so, we're done...
	if (isset($objects[$controller]))
	{
		return $objects[$controller];
	}
	
	if (file_exists(APP_PATH . '/controllers/' . $controller_dir . $controller . '.php'))
	{
		require_cache(APP_PATH . '/controllers/' . $controller_dir . $controller . '.php');
	}
	else
	{
		return false;
	}
	
	$objects[$controller] = & new $controller();
	return $objects[$controller];
}

/**
 * Loads model from user's application folder
 * 
 * @param $model
 * @return unknown_type
 */
function load_helper($helper)
{
	global $var;
	static $objects = array();

	// Does the class exist?  If so, we're done...
	if (isset($objects[$helper]))
	{
		return $objects[$helper];
	}
	//find helper
	if (file_exists(APP_PATH . '/helpers/' . $helper . '.php'))
	{
		require_cache(APP_PATH . '/helpers/' . $helper . '.php');
		return $objects[$helper]=true;
	}
	elseif (file_exists(SYS_PATH . '/helpers/' . $helper . '.php'))
	{
		require_cache(SYS_PATH . '/helpers/' . $helper . '.php');
		return $objects[$helper]=true;
	}
	
	return $objects[$helper]=false;
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
	load_class($factory,false,'factory/' . $factory);
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
	
	if (file_exists(APP_PATH . '/config/' . $config_name . '.php'))
	{
		require(APP_PATH . '/config/' . $config_name . '.php');
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

	if ( ! $loaded)
	{
		if ( ! file_exists(APP_PATH . '/config/config.php'))
		{
			exit('The configuration file config.php does not exist.');
		}

		require(APP_PATH . '/config/config.php');

		if ( ! isset($config) OR ! is_array($config))
		{
			exit('Your config file does not appear to be formatted correctly.');
		}
		$loaded = true;
	}
	return $config;
}

/**
* Error Logging Interface
*
* We use this as a simple mechanism to access the logging
* class and send messages to be logged.
*
* @access	public
* @return	void
*/
function log_message($level = 'error', $message = '', $php_error = FALSE)
{
	static $LOG;
	
	$config =& get_config();
	if ($config['log'] == 0)
	{
		return;
	}

	$LOG =& load_class('log');
	return $LOG->write_log($level, $message, $php_error);
}

/**
* Error Handler
*
* @access	private
* @return	void
*/
function _error_handler($severity, $message, $filepath, $line)
{
	if ($severity == E_STRICT)
	{
		return;
	}

	$error =& load_class('AC_Exception');

	// Should we display the error?
	// We'll get the current error_reporting level and add its bits
	// with the severity bits to find out.
	
	if (($severity & error_reporting()) == $severity)
	{
		$error->show_php_error($severity, $message, $filepath, $line);
	}
	
	// Should we log the error?  No?  We're done...
	$config =& get_config();
	if ($config['log'] == 0)
	{
		return;
	}

	$error->log_exception($severity, $message, $filepath, $line);
}

/**
 * 为了防止单引号攻击
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
 * 根据PHP各种类型变量生成唯一标识号
 * 
 * @param mixed $mix 变量
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
 * 优化的require_once
 * @param string $filename 文件名
 * @return boolen
 *
 */
function require_cache($filename)
{
	static $_import = array();
	
	if (!isset($_import[$filename])) 
	{
		if(file_exists_case($filename))
		{
			require $filename;
			
			$_import[$filename] = true;
		}
		else
		{
			$_import[$filename] = false;
		}
	}
	return $_import[$filename];
}

/**
 * 区分大小写的文件存在判断
 * 
 */
function file_exists_case($filename) 
{
	if (!defined('IS_WIN')) define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );
	
	if(file_exists($filename)) 
	{
		if(IS_WIN) 
		{
			$files =  scandir(dirname($filename));
			
			if(!in_array(basename($filename),$files)) 
			{
				return false;
			}
		}
		return true;
	}
	return false;
}

/**
 * 判断是否为对象实例
 *
 * @param mixed $object 实例对象
 * @param mixed $className 对象名
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
 * 取得类对象的实例
 * @param $className 类名
 * @param $method 方法名,如果为空仅调用构造函数
 * @param $args
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
            stop('类不存在');
        }
    }
    return $_instance[$identify];
}

/**
 * 自动转换字符集 支持数组转换
 * 需要 iconv 或者 mb_string 模块支持
 * 如果 输出字符集和模板字符集相同则不进行转换
 *
 * @param string $fContents 需要转换的字符串
 * @return string
 */
function auto_charset($contents,$from='',$to='')
{
	if(empty($from)) $from = 'utf-8';
	if(empty($to))  $to =   'utf-8';
	$from   =  strtoupper($from)=='UTF8'? 'utf-8':$from;
	$to	   =  strtoupper($to)=='UTF8'? 'utf-8':$to;
	if( strtoupper($from) === strtoupper($to) || empty($contents) || (is_scalar($contents) && !is_string($contents)))
	{
		//如果编码相同或者非字符串标量则不转换
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
			throw new Exception('没有支持编码转换的扩展!');
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
		//halt('系统不支持对'.gettype($fContents).'类型的编码转换！');
		return $contents;
	}
}
/**
 * 全局缓存设置和读取
 * @param $name 缓存名
 * @param $value 值
 * @param $expire 过期时间 单位:秒
 * @param $type 类型
 * @return unknown_type
 */
function S($name,$value='',$expire='',$type='')
{
    static $_cache = array();
    load_class('cache',false,'factory/cache');
    //取得缓存对象实例
    $cache  = Cache::getInstance($type);

    if('' !== $value)
    {
        if(is_null($value))
        {
            // 删除缓存
            $result =   $cache->rm($name);
            if($result)
            {
                unset($_cache[$type.'_'.$name]);
            }
            
            return $result;
        }
        else
        {
            // 缓存数据
            $cache->set($name,$value,$expire);
            $_cache[$type.'_'.$name]     =   $value;
        }
        return ;
    }
    
    if(isset($_cache[$type.'_'.$name])) {
        return $_cache[$type.'_'.$name];
    }
    // 获取缓存数据
    $value      =  $cache->get($name);
    $_cache[$type.'_'.$name]     =   $value;
    return $value;
}

/**
 * 创建目录
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
 * 程序停止
 * @param $message
 * @return unknown_type
 */
function stop($message = '')
{
	exit($message);
}

/**
 * 判断是否是POST
 * @return unknown_type
 */
function is_post()
{
	return strtolower($_SERVER['REQUEST_METHOD']) == 'post';
}
/**
 * 是否是Ajax提交
 * @return unknown_type
 */
function isAjax() 
{
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) 
	{
		if(strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest')
			return true;
			
	}
	
	if(!empty($_POST[$var->config['VAR_AJAX_SUBMIT']]) || !empty($_GET[$var->config['VAR_AJAX_SUBMIT']]))
	{
		// 判断Ajax方式提交
		return true;
	}
	
	return false;
}

/**
 * 加载文件
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
 * 在APP_PATH下保存缓存,将会创建文件,这些文件不会无限增多.
 * 清理时机:当修改模板时可以清理
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
 * 清理SVN创建的目录
 * @param $path1
 * @return unknown_type
 */
function clean_svn($path1)
{
	global $count;
	if ($count > 200)die('paused');
	$p = opendir($path1);
	while($f = readdir($p))
	{
		if ($f == '.' || $f == '..')continue;
	echo 'checking ' . $path1 . '/' . $f . '...<br>';
		
		if($f == '.svn')
		{
			del_dir($path1 . '/' . $f);
		}elseif (is_dir($path1 . '/' . $f))
		{
			clean_svn($path1 . '/' . $f);
		}
	}
}

/**
 * 递归删除一个目录
 * @param $path
 * @return unknown_type
 */
function del_dir($path)
{
	global $count;
	$count ++;
	if ($count > 200)die('paused');
	echo 'start delete ' . $path . '...<br>';
	$p = opendir($path);
	while($f = readdir($p))
	{
		if ($f == '.' || $f == '..')continue;
		$count ++;
		
		if (is_dir($path . '/' . $f))
		{
			del_dir($path . '/' . $f);
		}
		else
		{
			unlink($path . '/' . $f);
			echo '<font color="red">deleting ' . $path . '/' . $f . '</font>...ok<br>';
		}
	}
	rmdir($path);
	echo '<font color="red">delete ' . $path . '</font>...ok!<br>';
}

/**
 * 从开始到目前的执行时间
 * @return unknown_type
 */
function exec_time()
{
	global $SysStartTime;
	$Time = microtime(true);
	if (!is_float($Time))$Time = array_sum(explode(' ',$Time));
	return $Time - $SysStartTime;
}