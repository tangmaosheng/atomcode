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
define('DEBUG_MODE', file_exists(APP_PATH . '/debug.lock'));
DEBUG_MODE ? error_reporting(E_ALL & ~E_NOTICE) : error_reporting(0);
# Not support for PHP older than PHP v5
if(version_compare(PHP_VERSION, '5.0.0','<'))
{
	exit('Your PHP version is too old,please upgrade to 5.0.0 or newer!');
}

# First PHP5 feature, PHP4 don't support argument for function `microtime`
$system_start_time = microtime(true);

define('SYS_PATH', implode('/', array_slice(explode('/', str_replace('\\', '/', __FILE__)), 0, -2)));

require SYS_PATH . '/core/config.php';
require SYS_PATH . '/core/common.php';
# global varible $var
$var = new stdClass();
$var->config = & get_config();

date_default_timezone_set($var->config['time_zone']);
#router is working.
$var->controller = $var->method = '';
if ($var->config['ROUTER']['MODE'] == 0)
{
	$var->get = xaddslashes($_GET);
	$var->controller = $var->get[$var->config['ROUTER']['CONTROLLER']];
	$var->method = $var->get[$var->config['ROUTER']['METHOD']];
}
elseif ($var->config['ROUTER']['MODE'] == 1)
{
	$tmp = ($_SERVER['REDIRECT_STATUS'] == 404) ? trim($_SERVER['REQUEST_URI'], ' /?') : trim($_SERVER['PATH_INFO'], ' /?');
	$tmp_arr = explode('/', $tmp);
	$var->controller = $tmp_arr[0];
	$var->method = $tmp_arr[1];
	$str = '';
	
	for($i = 0; $i < intval((count($tmp_arr) - 1) / 2); $i ++)
	{
		$str .= '&' . $tmp_arr[$i * 2 + 2] . '=' . $tmp_arr[$i * 2 + 3];
	}
	parse_str($str, $var->get);
	$var->get = xaddslashes($var->get);
	
	$var->get[$var->config['ROUTER']['CONTROLLER']] = $var->controller;
	$var->get[$var->config['ROUTER']['METHOD']] = $var->method;
	
	unset($tmp);
	unset($tmp_arr);
	unset($i);
	unset($str);
}
elseif ($var->config['ROUTER']['MODE'] == 2)
{
	$tmp = trim($_SERVER['REQUEST_URI']);
	
	if ($tmp)
	{
		$request = '';
		foreach ($var->config['ROUTER']['PATTERN'] as $pattern => $replacement)
		{
			if (preg_match("|$pattern|", $tmp))
			{
				$request = preg_replace("|$pattern|", $replacement[0], $tmp);
				break;
			}
		}
		
		if (!$request)
		{
			exit('<h1>404 Not Found</h1>');
		}
		parse_str($request, $var->get);
	}
	$var->controller = $var->get[$var->config['ROUTER']['CONTROLLER']];
	$var->method = $var->get[$var->config['ROUTER']['METHOD']];
	
}
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
require 'view.php';
require 'controller.php';
require 'container.php';