<?php

/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://atomcn.cncms.com
 * @link http://atomcn.cncms.com
 * @author Eachcan <eachcan@cncms.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0
 * Cookie management
 * 
 * Need Config: cookie.key, cookie.expire, cookie.path, cookie.domain
 */

class Cookie
{
	/**
	 * check if exists
	 * @param $name
	 * @return unknown_type
	 */
	public static function exists($name)
	{
		return isset($_COOKIE[$name]);
	}
	
	/**
	 * get the cookie named $name
	 * @param $name
	 * @return unknown_type
	 */
	public static function get($name)
	{
		global $var;
		
		$value = $_COOKIE[$name];
		if ($var->config['cookie']['key'])
		{
			$value = self::_decrypt($value, $var->config['cookie']['key']);
		}
		
		return $value;
	}
	
	/**
	 * set cookie to browser,update global varible at the same time.
	 * @param $name
	 * @param $value
	 * @param $expire
	 * @param $path
	 * @param $domain
	 * @return unknown_type
	 */
	public static function set($name, $value, $expire='', $path='', $domain='')
	{
		global $var;
		
		$expire	= isset($var->config['cookie']['expire']) && empty($expire) ? $var->config['cookie']['expire'] : $expire;
		$path	= isset($var->config['cookie']['path']) && empty($path) ? $var->config['cookie']['path'] : $path;
		$domain	= isset($var->config['cookie']['domain']) && empty($domain) ? $var->config['cookie']['domain'] : $domain;

		if ($var->config['cookie']['key'])
		{
			$value	= self::_encrypt($value, $var->config['cookie']['key']);
		}
		
		setcookie($name, $value, $expire, $path, $domain);
		$_COOKIE[$name]	 = $value;
	}
	
	/**
	 * delete a cookie
	 * @param $name
	 * @return unknown_type
	 */
	public static function delete($name)
	{
		self::set($name, '', time() - 3600);
		unset($_COOKIE[$name]);
	}
	
	/**
	 * alias of self::clear
	 * @return unknown_type
	 */
	public static function deleteAll()
	{
		self::clear();
	}
	
	/**
	 * delete all the cookies
	 * @return unknown_type
	 */
	public static function clear()
	{
		foreach ($_COOKIE as $k => $v)
		{
			self::delete($k);
		}
	}
	
	/**
	 * encode the value
	 * @todo without implement encrypt
	 * @param $value
	 * @param $key
	 * @return unknown_type
	 */
	private static function _encrypt($value, $key)
	{
		return $value;
	}
	
	/**
	 * decode the value
	 * @param $value
	 * @param $key
	 * @return unknown_type
	 */
	private static function _decrypt($value, $key)
	{
		return $value;
	}
}