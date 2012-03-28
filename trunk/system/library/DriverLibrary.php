<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * DriverLibrary
 *
 * 驱动库类，通过继承本类你可以建立一个工厂模式的管理角色，可以由它来加载所需的相应的驱动类
 *
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */

class DriverLibrary {
	
	protected $validDrivers = array();
	protected static $libName;

	/**
	 * 
	 * 首次使用一个驱动，它并不存在，此时我们要加载它，下次则可以使用
	 * 子类规则，工厂名＋子类名，工厂名首字母大写，子类名首字母大写。
	 * 存在目录 BASE_PATH/library/driver/小写工厂名/子类名.php
	 * @param mixed $child
	 */
	function __get($child) {
		if (!isset($this->libName)) {
			$this->libName = get_class($this);
		}
		
		// Remove the CI_ prefix and lowercase
		$lib_name = strtolower($this->libName);
		$driver_name = strtolower($lib_name . $child);
		$find_driver = FALSE;
		
		// 取得大小写正确的驱动名
		foreach (array_map('strtolower', $this->validDrivers) as $k => $driver) {
			if ($driver_name == $driver) {
				$driver_name = $this->validDrivers[$k];
				$find_driver = TRUE;
				break;
			}
		}
		
		if ($find_driver) {
			// 看是否已经在其他文件中已经加载了
			if (!class_exists($driver_name)) {
				foreach (array(APP_PATH, BASE_PATH) as $path) {
					$filepath = $path . '/library/driver/' . $lib_name . '/' . $driver_name . EXT;
					
					if (file_exists($filepath)) {
						include $filepath;
						break;
					}
				}
				
				// 检查驱动文件是否正确找到了
				if (!class_exists($driver_name)) {
					log_message('error', "Unable to load the requested driver: " . $driver_name);
					show_error("Unable to load the requested driver: " . $driver_name);
				}
			}
			
			$obj = new $driver_name();
			$obj->decorate($this);
			$this->$child = $obj;
			return $this->$child;
		}
		// The requested driver isn't valid!
		log_message('error', "Invalid driver requested: " . $driver_name);
		show_error("Invalid driver requested: " . $driver_name);
	
	}

	// --------------------------------------------------------------------


}
// END DriverLibrary CLASS

/* End of file DriverLibrary.php */
/* Location: ./system/library/DriverLibrary.php */