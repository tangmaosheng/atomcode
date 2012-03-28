<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * ArrayHelper Class
 * 
 * 数组助手类，用于处理数组的一些非常用操作
 *
 * @package		AtomCode
 * @subpackage	helper
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class ArrayHelper {

	/**
	 * 
	 * 取得一项的值，如果不存在则取其默认值
	 * @param mixed $item
	 * @param array $array
	 * @param mixed $default
	 */
	public static function element($item, $array, $default = FALSE) {
		if (!isset($array[$item]) || $array[$item] == "") {
			return $default;
		}
		
		return $array[$item];
	}
	/**
	 * 
	 * 随机取出数组中的一项
	 * @param array $array
	 */
	public static function random_element($array) {
		if (!is_array($array)) {
			return $array;
		}
		
		return $array[array_rand($array)];
	}

	/**
	 * 
	 * 取得以items中项为键的array中的值的集合，如果不存在则返回默认值
	 * @param array $items
	 * @param array $array
	 * @param mixed $default
	 */
	function elements($items, $array, $default = FALSE) {
		$return = array();
		
		if (!is_array($items)) {
			$items = array($items);
		}
		
		foreach ($items as $item) {
			if (isset($array[$item])) {
				$return[$item] = $array[$item];
			} else {
				$return[$item] = $default;
			}
		}
		
		return $return;
	}
}