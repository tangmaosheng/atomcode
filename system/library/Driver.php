<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * Driver Class
 *
 * 本类定义了一个通用驱动类，你可以基于此类直接创建驱动
 * 它可以让外部类安全的调用驱动的方法和属性
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

class Driver {
	protected $parent;
	
	private $methods = array();
	private $properties = array();
	
	private static $reflections = array();

	/**
	 * Decorate
	 * 
	 * 修饰方法，将父驱动的方法和属性应用到子类
	 * Decorates the child with the parent driver lib's methods and properties
	 *
	 * @param	object
	 * @return	void
	 */
	public function decorate($parent) {
		$this->parent = $parent;
		
		// Lock down attributes to what is defined in the class
		// and speed up references in magic methods
		$class_name = get_class($parent);
		
		if (!isset(self::$reflections[$class_name])) {
			$r = new ReflectionObject($parent);
			
			foreach ($r->getMethods() as $method) {
				if ($method->isPublic()) {
					$this->methods[] = $method->getName();
				}
			}
			
			foreach ($r->getProperties() as $prop) {
				if ($prop->isPublic()) {
					$this->properties[] = $prop->getName();
				}
			}
			
			self::$reflections[$class_name] = array($this->methods, $this->properties);
		} else {
			list($this->methods, $this->properties) = self::$reflections[$class_name];
		}
	}

	// --------------------------------------------------------------------
	

	/**
	 * __call magic method
	 *
	 * 调用父类中存在的方法
	 *
	 * @access	public
	 * @param	string
	 * @param	array
	 * @return	mixed
	 */
	public function __call($method, $args = array()) {
		if (in_array($method, $this->methods)) {
			return call_user_func_array(array($this->parent, $method), $args);
		}
		
		$trace = debug_backtrace();
		_error_handler(E_ERROR, "No such method '{$method}'", $trace[1]['file'], $trace[1]['line']);
		exit();
	}

	// --------------------------------------------------------------------
	

	/**
	 * __get magic method
	 *
	 * 读取父类中存在的属性
	 *
	 * @param	string
	 * @return	mixed
	 */
	public function __get($var) {
		if (in_array($var, $this->properties)) {
			return $this->parent->$var;
		}
		
		return null;
	}

	// --------------------------------------------------------------------
	

	/**
	 * __set magic method
	 *
	 * 设置父类中存在的属性
	 *
	 * @param	string
	 * @param	array
	 * @return	mixed
	 */
	public function __set($var, $val) {
		if (in_array($var, $this->properties)) {
			$this->parent->$var = $val;
		}
	}

}
// END Driver CLASS

/* End of file Driver.php */
/* Location: ./system/library/Driver.php */