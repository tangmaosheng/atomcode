<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

/**
 * Error Class
 *
 * 错误处理类，可显示错误，并记录错误日志
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
class Error {

	private $action;

	private $severity;

	private $message;

	private $filename;

	private $line;

	private $ob_level;

	private static $instance;

	private $levels = array(E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parsing Error', E_NOTICE => 'Notice', E_CORE_ERROR => 'Core Error', E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning', E_USER_ERROR => 'User Error', E_USER_WARNING => 'User Warning', E_USER_NOTICE => 'User Notice', E_STRICT => 'Runtime Notice');

	/**
	 * error: E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR
	 * debug: E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING
	 * info: E_NOTICE, E_USER_NOTICE
	 * all: E_STRICT
	 */
	private $levelName = array(E_ERROR => 'error', E_WARNING => 'debug', E_PARSE => 'error', E_NOTICE => 'info', E_CORE_ERROR => 'error', E_CORE_WARNING => 'debug', E_COMPILE_ERROR => 'error', E_COMPILE_WARNING => 'debug', E_USER_ERROR => 'error', E_USER_WARNING => 'debug', E_USER_NOTICE => 'info', E_STRICT => 'all');

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->ob_level = ob_get_level();
	
		// Note:  Do not log messages from this constructor.
	}

	/**
	 * 
	 * @return Error
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Error();
		}
		
		return self::$instance;
	}

	/**
	 * Exception Logger
	 *
	 * This function logs PHP generated error messages
	 *
	 * @access	public
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	public function log_exception($severity, $message, $filepath, $line) {
		$levelName = (!isset($this->levelName[$severity])) ? 'all' : $this->levelName[$severity];
		$severity = (!isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		
		log_message($levelName, 'Severity: ' . $severity . '  --> ' . $message . ' ' . $filepath . ' ' . $line, TRUE);
	}

	// --------------------------------------------------------------------
	

	/**
	 * 404 Page Not Found Handler
	 *
	 * @access	public 
	 * @param	string
	 * @return	string
	 */
	public function show_404($page = '', $log_error = TRUE) {
		$heading = "404 Page Not Found";
		$message = "The page you requested was not found.";
		if (defined('STDIN')) {
			$this->show_std_error($heading, $message);
		}
		// By default we log this, but allow a dev to skip it
		if ($log_error) {
			log_message('error', '404 Page Not Found --> ' . $page);
		}
		
		echo $this->show_error($heading, $message, 'error_404', 404);
		exit();
	}

	// --------------------------------------------------------------------
	

	/**
	 * General Error Page
	 *
	 * This function takes an error message as input
	 * (either as a string or an array) and displays
	 * it using the specified template.
	 *
	 * @access	public 
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template name
	 * @return	string
	 */
	public function show_error($heading, $message, $template = 'error_general', $status_code = 500) {
		if (defined('STDIN')) {
			$this->show_std_error($heading, $message);
		}
		set_status_header($status_code);
		
		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		ob_start();
		include (APP_PATH . '/error/' . $template . '.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	public function show_php_error($severity, $message, $filepath, $line) {
		if (defined('STDIN')) {
			$this->show_std_error('PHP Error', "Severity:\t$severity\nMessage:\t$message\nFile:\t\t$filepath\nline:\t\t$line");
		}
		$severity = (!isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		
		$filepath = str_replace("\\", "/", $filepath);
		
		// For safety reasons we do not show the full file path
		if (FALSE !== strpos($filepath, '/')) {
			$x = explode('/', $filepath);
			$filepath = $x[count($x) - 2] . '/' . end($x);
		}
		
		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		ob_start();
		include (APP_PATH . '/error/error_php.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}

	/**
	 * 处理异常及调试信息
	 * @param Exception | Mixed $obj
	 * @param Boolean $is_exception
	 */
	public function debug($obj, $is_exception = FALSE) {
		$is_exception = $is_exception && ($obj instanceof Exception);
		$is_valid_exception = is_object($obj) && ($obj instanceof ValidateException);
		
		if ($is_exception) {
			$trace = $obj->getTrace();
		} else {
			$trace = debug_backtrace();
			
			$trace[1]['file'] = $trace[0]['file'];
			$trace[1]['line'] = $trace[0]['line'];
			array_shift($trace);
		}
		
		if (defined('STDIN')) {
			$t = array();
			$s = array();
			foreach ($trace as $id => $trace1) {
				$s[] = "Exception $id#:\n";
				foreach ($trace1 as $k => $v) {
					$s[] = "$k: $v\n";
				}
				
				$t[] = implode("\n", $s);
			}
			
			$this->show_std_error('PHP Exception', implode("\n", $t));
		}
		
		ob_start();
		include (APP_PATH . '/error/error_debug.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
		
		if ($is_exception) {
			exit();
		}
	}

	public function show_std_error($heading, $message) {
		if (is_array($message)) {
			$msg = '';
			foreach ($message as $k => $v) {
				if ($v == '--') {
					$msg .= "\n";
				}
				$msg .= $k . ":\t" . $v . "\n";
			}
			echo "$heading\n$msg";
		} else {
			echo "$heading\n$message\n";
		}
		exit();
	}
}
// End Error Class

/* End of file Error.php */
/* Location: ./system/library/Error.php */