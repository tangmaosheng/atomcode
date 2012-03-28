<?php
/**
 * iViewHelper Class
 * 
 * 模板解析引擎需要的修饰函数，如果要实现更多的方法，请继承本类，并实现新的方法
 * 
 * @see			ViewHelper
 * @package		AtomCode
 * @subpackage	helper
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
abstract class iViewHelper {
	/**
	 * 截取字符串
	 * @param string $val
	 * @param string $begin
	 * @param string $len
	 */
	public static function substr($val, $begin, $len = null) {
		return substr($val, $begin, $len);
	}
	/**
	 * 截取中文字符串
	 * @param string $val
	 * @param string $begin
	 * @param string $len
	 */
	public static function csubstr($val, $begin, $len = null, $charset = '') {
		if (!$charset) $charset = get_config('charset', 'utf-8');
		
		return mb_substr($val, $begin, $len, $charset);
	}
	
	/**
	 * 截短字符串，超过长度后显示附加字符串
	 * 
	 * @param string $val
	 * @param int $len
	 * @param string $ellipse
	 */
	public static function short($val, $len, $ellipse = '...') {
		if (mb_strlen($val, get_config('charset')) > $len) {
			return mb_substr($val, 0, $len, get_config('charset')) . '...';
		} else {
			return $val;
		}
	}
	
	/**
	 * 格式化日期 
	 * 
	 * @param int $time
	 * @param string $format
	 */
	public static function date($time, $format = 'Y-m-d H:i:s') {
		return date($format, $time);
	}
	
	/**
	 * 全部转换为大写
	 * 
	 * @param string $str
	 */
	public static function upper($str) {
		return strtoupper($str);
	}
	
	/**
	 * 全部转换为小写
	 * 
	 * @param string $string
	 */
	public static function lower($string) {
		return strtolower($string);
	}
	
	/**
	 * 字符串替换
	 * @param string $src
	 * @param string $search
	 * @param string $replace
	 */
	public static function replace($src, $search, $replace) {
		return str_replace($search, $replace, src);
	}
	
	/**
	 * 如果值为空则使用此值
	 * 
	 * 相当于 smarty 中的 default
	 * @param string $str
	 * @param string $instead
	 */
	public static function ifempty($str, $instead) {
		if (empty($str)) {
			return $instead;
		}
		
		return $str;
	}
	
	/**
	 * 将HTML内容以文本方式显示
	 * 
	 * 并不是去队HTML标签，而是以查看源代码方式显示
	 * 
	 * @see iViewHelper::txt2html()
	 * @param string $string
	 * @param boolean $simple 非简单模式将会把特殊字符转换为 &#xxx; 方式
	 */
	public static function html2txt($string, $simple = false) {
		if ($simple) {
			return str_replace(array('<', '>'), array('&lt;', '&gt;'), $string);
		} else {
			return htmlspecialchars($string);
		}
	}
	
	/**
	 * 将文本内容转为HTML
	 * 
	 * 与 {@link iViewHelper::html2txt()} 相反，将原本被转义的 HTML 转换为真正的HTML内容。
	 * @param string $string
	 */
	public static function txt2html($string) {
		return htmlspecialchars_decode($string);
	}
	
	/**
	 * 取得当前时间
	 */
	public static function time() {
		return TIMESTAMP;
	}
}