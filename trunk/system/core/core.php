<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 * New theory to develop PHP project.
 *
 * @copyright (c)  2009 http://www.atomcode.cn
 * @link http://www.atomcode.cn
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
	echo 'Your PHP version is too old,please upgrade to 5.0.0 or newer!';exit;
}

#常量定义
$CorePath = __FILE__;
define('SYS_PATH', str_replace('\\','/',substr($CorePath,0,-14) ) );
define('BASE_PATH', str_replace('\\','/',dirname(SCRIPTFILE) ) );
define('APP_PATH', BASE_PATH . '/' . APP);
define('APP_NAME','app');

require 'common.php';
require 'ac_base.class.php';
#默认错误处理
set_error_handler('_error_handler');
set_exception_handler('_exception_handler');

$var = new stdClass();
$var->config = get_config();
#时区设置
date_default_timezone_set($var->config['time_zone']);

$URI = load_class('uri');
$var->get = $URI->get_get();
$var->input = $var->get;
$var->method = strtolower($_SERVER['REQUEST_METHOD']);

if ($var->method == 'post')
{
	foreach ($_POST as $k => $v)
	{
		$var->post[$k] = xaddslashes($v);
		$var->input[$k] = $var->post[$k];
	}
}
else
{
	$var->post = false;
}

load_class('Cookie',0);
foreach ($_COOKIE as $k => $v)
{
	$var->cookie[$k] = Cookie::get($v);
}

load_class('Session',0);
Session::start();
$var->session = &$_SESSION;

require 'application.php';
require 'model.php';
require 'container.php';












