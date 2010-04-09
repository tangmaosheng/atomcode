<?php

/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@cncms.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-6
 * session
 * 
 * @author
 * @version 
 */

class Session
{
	public static function start()
	{
		return session_start();
	}
	
	public static function pause()
	{
		return session_write_close();
	}
	
	public static function exists($name)
	{
		return array_key_exists($name, $_SESSION);
	}
	
	public static function set($name, $value)
	{
		$_SESSION[$name] = $value;
	}
	
	public static function get($name)
	{
		return $_SESSION[$name];
	}
	
	public static function clear()
	{
		
	}
}