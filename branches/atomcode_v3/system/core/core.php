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
	static $start_time;
	static $config;  // 配置信息，一般为固定信息
	static $context; // 上下文信息，即程序本身的一些信息，比如URL和路径等
	static $setting; // 用户的设置
	
	/**
	 * 初始化工作
	 * 1. 配置工作环境
	 * 2. 初始化环境变量
	 * 3. 加载一些类
	 */
	public function initialize() {
		self::$config = include SYS_PATH . '/core/config.php';
		self::$context['path']['sys'] = SYS_PATH;
		self::$context['path']['app'] = APP_PATH;
	}
}