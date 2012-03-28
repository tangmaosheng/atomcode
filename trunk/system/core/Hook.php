<?php  if ( ! defined('BASE_PATH')) exit('No direct script access allowed');
/**
 * Hook Class
 * 
 * 定义钩子类父类
 *
 * 目的是需要钩子类要提供一个单例模式需要的静态方法： instance()
 * 
 * @package		AtomCode
 * @subpackage	core
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
 abstract class Hook {
 	/**
 	 * 取得钩子类实例
 	 * 
 	 * 必须实现此方法才能被框架调用，否则框架不会主动实例化你的钩子
 	 */
 	public abstract static function & instance();
 }
// END Hook Class

/* End of file Hook.php */
/* Location: ./system/core/Hook.php */