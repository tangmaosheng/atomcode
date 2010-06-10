<?php
$var->get = xaddslashes($_GET);
if ($var->config['ROUTER']['MODE'] == 1)
{
	$url = trim($_SERVER['REDIRECT_URL'], ' /');
	$query_string = trim($_SERVER['REDIRECT_QUERY_STRING']);
}
elseif ($var->config['ROUTER']['MODE'] == 2)
{
	$url = trim($_SERVER['REDIRECT_URL'], ' /');
}
elseif ($var->config['ROUTER']['MODE'] == 3)
{
	$var->get = array();
	$url = trim($_SERVER['QUERY_STRING'] . $_SERVER['PATH_INFO'], '/');
}

$unprocessed = true;
#parse query string and path info to array
parse_str($query_string, $query_string_arr);
#in rewrite mode, we depends on pattern, without pattern, use self pattern 
if ($var->config['ROUTER']['MODE'] && is_array($var->config['ROUTER']['PATTERN']))
{
	foreach ($var->config['ROUTER']['PATTERN'] as $pattern => $replace)
	{
		if (preg_match($pattern, $url))
		{
			$unprocessed = false;
			$url = preg_replace($pattern, $replace[0], $url);
			$var->static_file = $url;
			$static  = $replace[1];
			parse_str($url, $url_get);
			$var->get = array_merge($var->get, $url_get, $query_string_arr);
			break;
		}
	}
}
if ($unprocessed && $var->config['ROUTER']['MODE'] && empty($var->config['ROUTER']['PATTERN']))
{
	#remove extension of request
	if ($url && $var->config['STATIC']['EXT'] && preg_match('!^' . preg_quote('index' . $var->config['STATIC']['EXT']) . '$!', $url))
	{
		$static = true;
		$var->static_file = $url;
		$url = substr($url, 0, 0 - strlen($var->config['STATIC']['EXT']) - 5);
	}
	elseif (preg_match('!' . preg_quote($var->config['STATIC']['EXT']) . '$!', $url))
	{
		$static = true;
		$var->static_file = $url;
		$url = preg_replace('!' . preg_quote($var->config['STATIC']['EXT']) . '$!', '', $url);
	}
	
	if ($static)
	{
		$var->static = $var->config['ROUTER']['STATIC'];
	}
	$url_get = explode('/', $url);
	$url_get[0] && $var->get[$var->config['ROUTER']['CONTROLLER']] = $url_get[0];
	$url_get[1] && $var->get[$var->config['ROUTER']['METHOD']] = $url_get[1];
	$get2 = array();
	for($i = 2; $i < count($url_get); $i += 2)
	{
		$get2[$url_get[$i]] = $url_get[$i + 1];
	}
	$var->get = array_merge($var->get, $get2, $query_string_arr);
}
$var->controller = $var->get[$var->config['ROUTER']['CONTROLLER']];
$var->controller = $var->get[$var->config['ROUTER']['METHOD']];

unset($url);
unset($query_string);
unset($query_string_arr);
unset($url_get);
unset($get2);