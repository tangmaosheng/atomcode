<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.atomcode.cn
 * @link http://www.atomcode.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-6
 * @filesource
 * 
 */
/**
 *--------------------
 * Cookie管理类
 *--------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author	liu21st <liu21st@gmail.com>
 * @version   $Id$
 *--------------------
 */
class Cookie
{
	// 判断Cookie是否存在
	static function is_set($name) 
	{
		global $var;
		return isset($_COOKIE[$var->config['COOKIE']['PREFIX'].$name]);
	}

	// 获取某个Cookie值
	static function get($name) 
	{
		global $var;
		$value   = $_COOKIE[$var->config['COOKIE']['PREFIX'].$name];
		
		if($var->config['COOKIE']['SECRET_KEY']) 
		{
			$value   =  self::_decrypt($value,$var->config['COOKIE']['SECRET_KEY']);
		}
		
		return $value;
	}

	// 设置某个Cookie值
	static function set($name,$value,$expire='',$path='',$domain='') 
	{
		global $var;
		if($expire=='') 
		{
			$expire =   $var->config['COOKIE']['EXPIRE'];
		}
		
		if(empty($path)) 
		{
			$path = $var->config['COOKIE']['PATH'];
		}
		
		if(empty($domain)) 
		{
			$domain =   $var->config['COOKIE']['DOMAIN'];
		}
		
		$expire =   !empty($expire)?	time()+$expire   :  0;
		
		if($var->config['COOKIE']['SECRET_KEY']) 
		{
			$value   =  self::_encrypt($value,$var->config['COOKIE']['SECRET_KEY']);
		}
		
		setcookie($var->config['COOKIE']['PREFIX'].$name, $value,$expire,$path,$domain);
		$_COOKIE[$var->config['COOKIE']['PREFIX'].$name]  =   $value;
	}

	// 删除某个Cookie值
	static function delete($name) 
	{
		global $var;
		Cookie::set($name,'',time()-3600);
		unset($_COOKIE[$var->config['COOKIE']['PREFIX'].$name]);
	}

	// 清空Cookie值
	static function clear() 
	{
		unset($_COOKIE);
	}

	static private function _encrypt($value,$key)
	{
	   $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	   $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	   $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $value, MCRYPT_MODE_ECB, $iv);
	   return trim(base64_encode($crypttext));
	}

	static private function _decrypt($value,$key)
	{
	   $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	   $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	   $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($value), MCRYPT_MODE_ECB, $iv);
	   return trim($decrypttext);
	}
}
?>