<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * Sms Class
 *
 * 短信下发类通过金山短信接口对外发送短信
 * 需要 iconv 支持；否则GBK之外的编码不能发送
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource	$Id$
 */
class Sms {
	private $server;
	private static $instance;
	
	public function __construct($server = '') {
		if (!$server) {
			$server = get_config('sms_server');
		}
		
		$this->server($server);
	}

	/**
	 * 
	 * @return Sms
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Sms();
		}
		
		return self::$instance;
	}
	
	public function server($server) {
		$this->server = $server;
	}
	
	/**
	 * 返回值格式 array(error_code, error_message)
	 * error_code 为0表示成功，否则为不同的错误
	 * @param string $phone
	 * @param string $msg
	 * @param int $type 由短信服务提供方提供
	 * @param string $charset
	 * @throws SmsException
	 */
	public function send($phone, $msg, $type, $charset = 'utf-8') {
		try {
			if (!$this->server) {
				throw new SmsException('Invalid Server', -1);
			}
			if ($charset != 'gbk' && $charset != 'gb2312') {
				$msg = iconv($charset, 'gbk', $msg);
			}
			
			$result = $this->doSend($phone, $msg, $type);
		} catch (SmsException $e) {
			$result = array($e->getCode(), $e->getMessage());
		}
		
		return $result;
	}
	
	private function doSend($phone, $msg, $type) {
		$data = array();
		$data['phone'] = $phone;
		$data['command'] = $type;
		$data['content'] = $msg;
		
		$url = rtrim($this->server, '? ');
		$url .= '?' . http_build_query($data);
		$result = file_get_contents($url);
		$return = explode('-', $result);
		if (count($return) != 2) {
			throw new SmsException($result, -2);
		}
		
		return array($return[0], $return[1]);
	}
}

class SmsException extends Exception {}