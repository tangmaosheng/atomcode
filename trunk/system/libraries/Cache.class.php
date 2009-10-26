<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.atomcode.cn
 * @link http://www.atomcode.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-6
 * @filesource /system/libraries/cache.class.php
 * 
 */
if (!defined('TEMP_PATH')) define('TEMP_PATH', APP_PATH . '/cache/file_cache');
/**
 * 缓存管理类
 * 本类为管理类,可以直接使用,根据配置自动调用
 */
class Cache extends ac_Base
{//类定义开始

	/**
	 *
	 * 是否连接
	 *
	 * @var string
	 * @access protected
	 *
	 */
	protected $connected  ;

	/**
	 *
	 * 操作句柄
	 *
	 * @var string
	 * @access protected
	 *
	 */
	protected $handler	;

	/**
	 *
	 * 缓存存储前缀
	 *
	 * @var string
	 * @access protected
	 *
	 */
	protected $prefix='~@';

	/**
	 *
	 * 缓存连接参数
	 *
	 * @var integer
	 * @access protected
	 *
	 */
	protected $options = array();

	/**
	 *
	 * 缓存类型
	 *
	 * @var integer
	 * @access protected
	 *
	 */
	protected $type	   ;

	/**
	 *
	 * 缓存过期时间
	 *
	 * @var integer
	 * @access protected
	 *
	 */
	protected $expire	 ;

	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 *
	 * 连接缓存
	 *
	 * @access public
	 *
	 * @param string $type 缓存类型
	 * @param array $options  配置数组
	 *
	 * @return object
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function connect($type='',$options=array())
	{
		if(empty($type))
		{
			$type = $this->config['CACHE']['TYPE'];
		}
		
		load_class('Session',0);
		
		if(Session::is_set('CACHE_'.strtoupper($type))) 
		{
			$cacheClass   = Session::get('CACHE_'.strtoupper($type));
		}
		else 
		{
			$cachePath = dirname(__FILE__).'/Cache/';
			$cacheClass = 'Cache'.ucwords(strtolower(trim($type)));
			require_cache($cachePath.$cacheClass.'.class.php');
		}
		
		if(class_exists($cacheClass))
		{
			$cache = new $cacheClass($options);
		}
		else 
		{
			throw new Exception('缓存类型错误'.':'.$type);
		}
		
		return $cache;
	}

	public function __get($name) 
	{
		return $this->get($name);
	}

	public function __set($name,$value) 
	{
		return $this->set($name,$value);
	}

	public function setOptions($name,$value) 
	{
		$this->options[$name]   =   $value;
	}

	public function getOptions($name) 
	{
		return $this->options[$name];
	}
	/**
	 *
	 * 取得缓存类实例
	 *
	 * @static
	 * @access public
	 *
	 * @return mixed
	 *
	 * @throws ThinkExecption
	 *
	 */
	static function getInstance()
	{
		$param = func_get_args();
		
		$class = __CLASS__;
		$me = new $class();
		
		return $me->connect($param[0]);
		//return call_user_func_array(array(&$me,'connect'),$param);
	}

	// 读取缓存次数
	public function Q($times='')
	{
		static $_times = 0;
		
		if(empty($times))
		{
			return $_times;
		}
		else
		{
			$_times++;
		}
	}

	// 写入缓存次数
	public  function W($times='')
	{
		static $_times = 0;
		
		if(empty($times)) 
		{
			return $_times;
		}
		else
		{
			$_times++;
		}
	}
}//类定义结束
?>