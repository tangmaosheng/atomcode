<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * Hooks Class
 *
 * Hook 机制实现一个扩展基础功能的方法，你可以在系统执行的不同的阶段获取系统信息
 * 并拦截系统执行方法进行你定义的个性化执行流程。
 * 
 * 依赖于配置文件  hooks.php
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

class Hooks {
	
	private $enabled = FALSE;
	private $hooks = array();
	private $in_progress = FALSE;
	private static $instance;

	/**
	 * Constructor
	 *
	 */
	private function __construct() {
		$this->_initialize();
		log_message('debug', "Hooks Class Initialized");
	}

	/**
	 * 
	 * @return Hooks
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Hooks();
		}
		
		return self::$instance;
	}

	// --------------------------------------------------------------------
	

	/**
	 * Initialize the Hooks Preferences
	 *
	 * @access	private
	 * @return	void
	 */
	private function _initialize() {
		$CFG = & get_config();
		
		// If hooks are not enabled in the config file
		// there is nothing else to do
		if ($CFG['enable_hooks'] == FALSE) {
			return;
		}
		
		// Grab the "hooks" definition file.
		// If there are no hooks, we're done.
		$config = load_config('hooks', array());
		
		if (!isset($config) || !is_array($config)) {
			return;
		}
		
		$this->hooks = & $config;
		$this->enabled = TRUE;
	}

	/**
	 * Call Hooks
	 *
	 * Calls a particular hook
	 *
	 * @access	public
	 * @param	string	the hook name
	 * @return	mixed
	 */
	public function _call_hook($which = '') {
		if (!$this->enabled || $which == '' || !isset($this->hooks[$which])) {
			return FALSE;
		}
		
		if (isset($this->hooks[$which][0]) && is_array($this->hooks[$which][0])) {
			foreach ($this->hooks[$which] as $val) {
				$this->_run_hook($val);
			}
		} else {
			$this->_run_hook($this->hooks[$which]);
		}
		
		return TRUE;
	}

	// --------------------------------------------------------------------
	

	/**
	 * Run Hooks
	 *
	 * Runs a particular hook
	 *
	 * @access	private
	 * @param	array	the hook details
	 * @return	bool
	 */
	private function _run_hook($data) {
		if (!is_array($data)) {
			return FALSE;
		}
		
		// If the script being called happens to have the same
		// hook call within it a loop can happen
		if ($this->in_progress == TRUE) {
			return;
		}
		
		// Set class/function name
		$class = FALSE;
		$function = FALSE;
		$params = '';
		
		if (isset($data['class']) && $data['class'] != '') {
			$class = $data['class'];
		}
		
		if (isset($data['function'])) {
			$function = $data['function'];
		}
		
		if (isset($data['params'])) {
			$params = $data['params'];
		}
		
		if ($class === FALSE || $function === FALSE) {
			return FALSE;
		}
		
		// Set file path
		$filepath = APP_PATH . '/hook/' . $class . '.php';
		if (!file_exists($filepath)) {
			$filepath = BASE_PATH . '/hook/' . $class . '.php';
		}
		
		if (!file_exists($filepath)) {
			return FALSE;
		}
		
		// -----------------------------------
		// Set the in_progress flag
		// -----------------------------------
		

		$this->in_progress = TRUE;
		
		// -----------------------------------
		// Call the requested class and/or function
		// -----------------------------------
		

		if (!class_exists($class)) {
			require ($filepath);
		}
		
		$HOOK = new $class();
		call_user_func_array(array($HOOK, $function), $params);
		
		$this->in_progress = FALSE;
		return TRUE;
	}

}
// END Hooks class

/* End of file Hooks.php */
/* Location: ./system/library/Hooks.php */