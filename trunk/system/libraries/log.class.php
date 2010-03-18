<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-6
 * @filesource /system/libraries/log.class.php
 */
class log {

	var $log_path;
	var $_threshold	= 1;
	var $_date_fmt	= 'Y-m-d H:i:s';
	var $_enabled	= TRUE;
	var $_levels	= array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		$config =& get_config();
		
		$this->log_path = ($config['log_path'] != '') ? $config['log_path'] : SYS_PATH .'/logs/';
		
		if ( ! is_dir($this->log_path) OR ! is_really_writable($this->log_path))
		{
			$this->_enabled = FALSE;
		}
		
		if (is_numeric($config['log']))
		{
			$this->_threshold = $config['log'];
		}
			
		if ($config['log_date_format'] != '')
		{
			$this->_date_fmt = $config['log_date_format'];
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @access	public
	 * @param	string	the error level
	 * @param	string	the error message
	 * @param	bool	whether the error is a native PHP error
	 * @return	bool
	 */		
	function write_log($level = 'error', $msg, $php_error = FALSE)
	{		
		if ($this->_enabled === FALSE)
		{
			return FALSE;
		}
	
		$level = strtoupper($level);
		
		if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
		{
			return FALSE;
		}
	
		$filepath = $this->log_path.'log-'.date('Y-m-d'). '.php';
		$message  = '';
		
		if ( ! file_exists($filepath))
		{
			$message .= "<"."?php  if ( ! defined('BASE_PATH')) exit('No direct script access allowed'); ?".">\n\n";
		}
			
		if ( ! $fp = @fopen($filepath, 'ab'))
		{
			return FALSE;
		}

		$message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_date_fmt). ' --> '.$msg."\n";
		
		flock($fp, LOCK_EX);	
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);
	
		@chmod($filepath, 0666); 		
		return TRUE;
	}

}
// END Log Class