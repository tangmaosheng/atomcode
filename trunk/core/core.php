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
 * @version 1.0 2010-05-28
 */
// Not support for PHP older than PHP v5
if(version_compare(PHP_VERSION, '5.0.0','<'))
{
	exit('Your PHP version is too old,please upgrade to 5.0.0 or newer!');
}
# First PHP5 feature, PHP4 don't support argument for function `microtime`
$system_start_time = microtime(true);

define('DEBUG_MODE', file_exists(APP_PATH . '/debug.lock'));
DEBUG_MODE ? error_reporting(E_ALL & ~E_NOTICE) : error_reporting(0);
define('SYS_PATH', implode('/', array_slice(explode('/', str_replace('\\', '/', __FILE__)), 0, -2)));
if (!defined('DOCUMENT_ROOT')) define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

require 'config.php';
require 'common.php';
# global varible $var
$var = new stdClass();
$var->config = & get_config();

date_default_timezone_set($var->config['time_zone']);
#router begin
$var->controller = $var->method = '';
require 'router.php';
# router end
$var->input = $var->get;
$var->request_method = strtolower($_SERVER['REQUEST_METHOD']);

if (is_post())
{
	foreach ($_POST as $k => $v)
	{
		$var->post[$k] = xaddslashes($v);
		$var->input[$k] = $var->post[$k];
	}
}

require 'cookie.php';
require 'session.php';

foreach ($_COOKIE as $k => $v)
{
	$var->cookie[$k] = Cookie::get($k);
}
Session::start();
$var->session = &$_SESSION;

require 'application.php';
require 'model.php';
require 'dbmodel.php';
require 'db.php';
require 'dbmysql.php';
require 'view.php';
require 'controller.php';
require 'container.php';