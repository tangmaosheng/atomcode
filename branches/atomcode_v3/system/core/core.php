<?php
if(version_compare(PHP_VERSION, '5.0.0','<')) {
	exit('Your PHP version is too old,please upgrade to 5.0.0 or newer!');
}
if (!defined('APP_PATH')) {
	exit('Cound not work!');
}
define('DEBUG_MODE', file_exists(APP_PATH . '/debug.lock'));
DEBUG_MODE ? error_reporting(E_ALL & ~E_NOTICE) : error_reporting(0);
define('SYS_PATH', implode('/', array_slice(explode('/', str_replace('\\', '/', __FILE__)), 0, -2)));
if (!defined('DOCUMENT_ROOT')) define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

class Core {
	public static $start_time;
	public static $config;  // 配置信息，一般为固定信息
	public static $context; // 上下文信息，即程序本身的一些信息，比如URL和路径等
	public static $setting; // 用户的设置
	
	/**
	 * 初始化工作
	 * 1. 配置工作环境
	 * 2. 初始化环境变量
	 * 3. 加载一些类
	 */
	private static function initialize() {
		self::$config = include SYS_PATH . '/core/config.php';
		self::$context['path']['sys'] = SYS_PATH;
		self::$context['path']['app'] = APP_PATH;
		self::$context['document_root'] = DOCUMENT_ROOT;
		self::$context['path']['cache'] = APP_PATH . '/cache';
		self::$start_time = microtime(true);
		
		$user_config = include APP_PATH . '/config/config.php';
		if (is_array($user_config)) {
			self::$config = array_merge($user_config, self::$config);
		}
		
		spl_autoload_register(__CLASS__ . '::load');
		
		if (self::$setting['timezone']) {
			@date_default_timezone_set('Etc/GMT' . (self::$setting['timezone'] > 0 ? '-' : '+') . abs(self::$setting['timezone']));
		} else {
			@date_default_timezone_set('Etc/GMT+8');
		}
		
		session_save_path(self::$context['path']['cache']);
	}
	
	public static function start() {
		self::initialize();
	}
	
	/**
	 * 自动加载机制
	 * @param unknown_type $name
	 */
	public static function load($name) {
		$dir = 'library';
		
		$dirs = array( 'controller' => 'controller', 'model' => 'model', 'helper' => 'helper', 'driver' => 'driver', 'exception' => 'exception', 'interface' => 'interface');
		
		$pieces = explode('_', $name);
		$type = strtolower(end($name));
		
		if (in_array($type, $dirs)) {
			$dir = $dirs[$type];
			
			if ($type == 'driver' && count($pieces) > 2) {
				$dir .= '/' . strtolower(reset($pieces));
			}
		}
		
		$name = strtolower($name);
		$sysfile = SYS_PATH . "/$dir/$name.php";
		$appfile = APP_PATH . "/$dir/$name.php";
		
		if (!file_exists($appfile)) {
			require $sysfile;
		} else {
			require $appfile;
		}
	}
	
	public static function exec_time() {
		$now = microtime(true);
		return $now - self::$start_time;
	}
}