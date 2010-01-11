<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 * New theory to develop PHP project.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@gmail.com>
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 
 * @filesource /system/core/core.php
 */

#错误报告
error_reporting(E_ALL & ~E_NOTICE);
#引号转义
set_magic_quotes_runtime(0);
#版本支持
if(version_compare(PHP_VERSION,'5.0.0','<') )
{
	exit('Your PHP version is too old,please upgrade to 5.0.0 or newer!');
}

$system_start_time = microtime(true);
if (!is_float($system_start_time))$system_start_time = array_sum(explode(' ',$system_start_time));

#常量定义
define('SYS_PATH', str_replace('\\','/',substr(__FILE__,0,-14) ) );
define('BASE_PATH', str_replace('\\','/',dirname(SCRIPTFILE) ) );

require 'config.php';
require 'common.php';
#默认错误处理
set_error_handler('_error_handler');

$var = new stdClass();
$var->config = & get_config();
#时区设置
date_default_timezone_set($var->config['time_zone']);

$uri = load_factory('uri');

$var->get = $uri->getGet();
$var->input = $var->get;
$var->request_method = strtolower($_SERVER['REQUEST_METHOD']);

if ($var->request_method == 'post')
{
	foreach ($_POST as $k => $v)
	{
		$var->post[$k] = xaddslashes($v);
		$var->input[$k] = $var->post[$k];
	}
}
load_class('Cookie',0);
foreach ($_COOKIE as $k => $v)
{
	$var->cookie[$k] = Cookie::get($v);
}

load_class('Session',0);
Session::start();
$var->session = &$_SESSION;
load_class('compile',false);

require 'controller.php';
require 'container.php';
require 'model.php';
require 'application.php';







