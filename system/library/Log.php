<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * Log
 *
 * 日志类，可以定义记录日志的类型
 *
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class Log {
	
	protected $_log_path;
	protected $_threshold = 1;
	protected $_date_fmt = 'Y-m-d H:i:s';
	protected $_enabled = TRUE;
	protected $_levels = array('ERROR' => '1', 'DEBUG' => '2', 'INFO' => '3', 'ALL' => '4');
	private static $instance;

	/**
	 * Constructor
	 */
	private function __construct() {
		$config = & get_config();
		
		$this->_log_path = ($config['log_path'] != '') ? $config['log_path'] : APP_PATH . '/log/';
		
		if (!is_dir($this->_log_path) || !is_really_writable($this->_log_path)) {
			$this->_enabled = FALSE;
		}
		
		if (is_numeric($config['log_threshold'])) {
			$this->_threshold = $config['log_threshold'];
		}
		
		if ($config['log_date_format'] != '') {
			$this->_date_fmt = $config['log_date_format'];
		}
	}

	/**
	 * 
	 * @return Log
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Log();
		}
		
		return self::$instance;
	}
	

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	string	the error level
	 * @param	string	the error message
	 * @param	bool	whether the error is a native PHP error
	 * @return	bool
	 */
	public function write_log($level = 'error', $msg, $type = 'log') {
		if ($this->_enabled === FALSE) {
			return FALSE;
		}
		
		$level = strtoupper($level);
		
		if (!isset($this->_levels[$level]) || ($this->_levels[$level] > $this->_threshold)) {
			return FALSE;
		}
		
		$filepath = $this->_log_path . $type . '-' . $level . '-' . date('Y-m-d') . EXT;
		$message = '';
		
		if (!file_exists($filepath)) {
			$message .= "<" . "?php  if ( ! defined('BASE_PATH')) exit('No direct script access allowed'); ?" . ">\n\n";
		}
		
		if (!$fp = fopen($filepath, 'ab')) {
			return FALSE;
		}
		
		$message .= $level . ' ' . (($level == 'INFO') ? ' -' : '-') . ' ' . date($this->_date_fmt) . ' --> ' . $msg . "\n";
		
		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);
		
		@chmod($filepath, 0777);
		return TRUE;
	}

}
// END Log class

/* End of file Log.php */
/* Location: ./system/library/Log.php */