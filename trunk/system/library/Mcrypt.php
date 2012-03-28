<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * Mcrypt Class
 *  
 * 加密类，使用Des算法，主要用于membercenter
 *
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource	$Id$
 */
class Mcrypt {
	private static $instance;
	private $key;

	private function __construct() {
		$this->setKey(get_config('mcrypt_key', ''));
	}
	
	/**
	 * @return Mcrypt
	 */
	public static function & instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Mcrypt();
		}
		
		return self::$instance;
	}

	/**
	 * 设置加解密用的key
	 * @param unknown_type $key
	 */
	public function setKey($key) {
		$this->key = $key;
	}

	/**
	 * 加密字符串
	 * @param string $input
	 * @return String
	 */
	function encrypt($input) {
		$size = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		
		$input = $this->pkcs5_pad($input, $size);
		$iv = @pack('H16', '1234567890ABCDEF');
		@mcrypt_generic_init($td, $this->key, $iv);
		$data = mcrypt_generic($td, $input);
		
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		
		$data = base64_encode($data);
		
		return $data;
	
	}

	/**
	 * 解密字符串
	 * @param unknown_type $encrypted
	 */
	function decrypt($encrypted) {
		$encrypted = base64_decode($encrypted);
		
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, ''); //使用MCRYPT_DES算法,cbc模式
		$ks = mcrypt_enc_get_key_size($td);
		
		$iv = @pack('H16', '1234567890ABCDEF');
		@mcrypt_generic_init($td, $this->key, $iv); //初始处理
		$decrypted = mdecrypt_generic($td, $encrypted); //解密
		
		mcrypt_generic_deinit($td); //结束
		mcrypt_module_close($td);
		$y = $this->pkcs5_unpad($decrypted);
		
		return $y;
	}

	/**
	 * 填充无用字符
	 * @param string $text
	 * @param int $blocksize
	 */
	function pkcs5_pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		
		return $text . str_repeat(chr($pad), $pad);
	}

	/**
	 * 去除填充的无用字符
	 * @param String $text
	 */
	function pkcs5_unpad($text) {
		$pad = ord($text{strlen($text) - 1});
		if ($pad > strlen($text)) {
			return false;
		}
		
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		
		return substr($text, 0, -1 * $pad);
	}
}
// End Mcrypt Class

/* End of file Mcrypt.php */
/* Location: ./system/library/Mcrypt.php */