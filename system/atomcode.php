<?php

/**
 * AtomCode
 *
 * A open source application,welcome to join us to develop it.
 * AtomCode is for PHP 5.1.6 or newer.
 * 
 * 文件自动加载
 * 核心文件:直接加载
 * 控制器：由系统直接加载,类命名 TestController，控制器之间不能互相使用
 * 模型：TEST_MODEL
 * 助手：TestHelper
 * 库：直接写类，例如： Http
 * 块：TestBlock
 * 
 * 系统目录：system 下面所有目录
 * 核心：core
 * 资源：resource
 * 助手：helper
 * 系统语言包：language
 * 库：library
 * 
 * 应用中文件位置：application/
 * cache: cache
 * 配置: config
 * 日志: log
 * 语言包：language
 * 模板：view
 * 系统资源文件:resource
 * 控制器：controller
 * 模型：model
 * 助手：helper
 * 钩子：hooks
 * 
 * @package		AtomCode
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
if (!defined("SELF") || !defined("APP_PATH")) {
	exit('Lost basic defination.');
}
if (!defined("TEST_MODEL")) {
	define('TEST_MODEL', FALSE);
}
if (!defined('RENDER')) {
	define('RENDER', 'Html');
}

if (TEST_MODEL) {
	error_reporting(E_ALL & ~E_NOTICE);
} else {
	error_reporting(0);
}

define('BASE_PATH', pathinfo(__FILE__, PATHINFO_DIRNAME));
define('VERSION', '1.1');
define('TIMESTAMP', time());
define('EXT', '.php');

if (defined('STDIN')) {
	chdir(dirname(SELF));
}

require (BASE_PATH . '/core/common.php');
spl_autoload_register('autoload');
set_error_handler('_error_handler');
set_exception_handler('_exception_handler');
if (!is_php('5.3')) {
	@set_magic_quotes_runtime(0); // Kill magic quotes
}

load_config('config');
$__LANGUAGE_PACKAGE = array(); // Language Support
if (function_exists("set_time_limit") == TRUE && @ini_get("safe_mode") == 0) {
	@set_time_limit(300);
}
if (get_config('time_zone')) {
	date_default_timezone_set(get_config('time_zone'));
}
// Benchmark
if (get_config('enable_benchmark')) {
	$BM = & Benchmark::instance();
	$BM->mark('total_execution_time_start');
	$BM->mark('loading_time:_base_classes_start');
}
// Reset Input
$INPUT = Input::instance();

if (get_config('enable_hooks')) {
	require BASE_PATH . '/core/Hook.php';
	// Hooks
	$HOOK = & Hooks::instance();
	$HOOK->_call_hook('pre_system');
}
// Route
$URI = & Uri::instance();
$RTR = & Router::instance();
$RTR->_set_routing();
if (get_config('enable_hooks')) {
	$HOOK->_call_hook('post_router');
}

if (get_config('enable_hooks') && $HOOK->_call_hook('cache_override')) {
	$OUTPUT = & Output::instance();
	if ($OUTPUT->_display_cache() == TRUE) {
		exit();
	}
}

require BASE_PATH . '/core/Controller.php';
require BASE_PATH . '/core/Model.php';

// Load the local application controller
// Note: The Router class automatically validates the controller path using the router->_validate_request().
// If this include fails it means that the default controller in the Routes.php file is not resolving to something valid.
if (!file_exists(APP_PATH . '/controller/' . $RTR->fetch_directory() . $RTR->fetch_class() . EXT)) {
	show_error('Unable to load your default controller. Please make sure the controller specified in your routes.php file is valid.');
}

include (APP_PATH . '/controller/' . $RTR->fetch_directory() . $RTR->fetch_class() . EXT);

// Set a mark point for benchmarking
if (get_config('enable_benchmark')) {
	$BM->mark('loading_time:_base_classes_end');
}

/*
 * ------------------------------------------------------
 *  Security check
 * ------------------------------------------------------
 *
 *  None of the functions in the app controller or the
 *  loader class can be called via the URI, nor can
 *  controller functions that begin with an underscore
 */
$__CLASS = $RTR->fetch_class();
$__METHOD = $RTR->fetch_method();

if (!class_exists($__CLASS) or strncmp($__METHOD, '_', 1) == 0 or in_array(strtolower($__METHOD), array_map('strtolower', get_class_methods('Controller')))) {
	show_404("{$__CLASS}/{$__METHOD}");
}

if (get_config('enable_hooks')) {
	$HOOK->_call_hook('pre_controller');
}

if (get_config('enable_benchmark')) {
	$BM->mark('controller_execution_time_( ' . $__CLASS . ' / ' . $__METHOD . ' )_start');
}
$__CTRL = new $__CLASS();

if (get_config('enable_hooks')) {
	$HOOK->_call_hook('post_controller_constructor');
}
      
if (method_exists($__CTRL, '_remap')) {
	$__VIEW = $__CTRL->_remap($__METHOD, array_slice($URI->rsegments, 2));
} else {
	// is_callable() returns TRUE on some versions of PHP 5 for private and protected
	// methods, so we'll use this workaround for consistent behavior
	if (!in_array(strtolower($__METHOD), array_map('strtolower', get_class_methods($__CTRL)))) {
		// Check and see if we are using a 404 override and use it.
		if (!empty($RTR->routes['404_override'])) {
			$x = explode('/', $RTR->routes['404_override']);
			$__CLASS = $x[0];
			$__METHOD = (isset($x[1]) ? $x[1] : 'index');
			if (!class_exists($__CLASS)) {
				if (!file_exists(APP_PATH . '/controller/' . $__CLASS . EXT)) {
					show_404("{$__CLASS}/{$__METHOD}");
				}
				
				include_once (APP_PATH . '/controller/' . $__CLASS . EXT);
				unset($__CTRL);
				$__CTRL = new $__CLASS();
			}
		} else {
			show_404("{$__CLASS}/{$__METHOD}");
		}
	}
	
	// Call the requested method.
	// Any URI segments present (besides the class/function) will be passed to the method for convenience
	$__VIEW = call_user_func_array(array(
		&$__CTRL, $__METHOD
	), array_slice($URI->rsegments, 2));
}
if (get_config('enable_benchmark')) {
	$BM->mark('controller_execution_time_( ' . $__CLASS . ' / ' . $__METHOD . ' )_end');
}
 
if (get_config('enable_hooks')) {
	$HOOK->_call_hook('post_controller');
}
if ($__VIEW && $__VIEW instanceof Render) {
	$__VIEW->display();
} elseif ($__VIEW) {
	// 根据渲染器进行渲染输出
	if (RENDER == 'Html') {
		$__RENDER = new HtmlRender();
		$__RENDER->setEnv($__VIEW);
		$__RENDER->display();
	} else {
		$r = RENDER . 'Render';
		$__RENDER = new $r;
		$__RENDER->display($__VIEW);
	}
}

/*
 * Send the final rendered output to the browser
 */
if (get_config('enable_hooks') && $HOOK->_call_hook('display_override') === FALSE) {
	$OUTPUT = & Output::instance();
	$OUTPUT->_display($__RENDER->getContents());
}

if (get_config('enable_hooks')) {
	$HOOK->_call_hook('post_system');
}

/* location ./system/atomcode.php */