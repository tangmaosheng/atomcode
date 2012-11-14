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
		if (!$charset)
			$charset = get_config('charset', 'utf-8');
		
		return mb_substr($val, $begin, $len, $charset);
	}

	/**
	 * 截短字符串，超过长度后显示附加字符串
	 * 
	 * @param string $val
	 * @param int $len
	 * @param string $ellipse
	 */
	public static function shorten($val, $len, $ellipse = '...') {
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

	public static function mkoptions($option, $selected_value) {
		$html = '';
		if (!$option || !is_array($option))
			return '';
		
		$first_item = reset($option);
		$complex = is_array($first_item);
		if ($complex) {
			if (isset($first_item[0]) && isset($first_item[1])) {
				$value_key = 0;
				$text_key = 1;
			} else {
				$free_key = array();
				foreach (array_keys($first_item) as $value) {
					if (strpos($value, 'id') !== FALSE) {
						if (!isset($value_key))
							$value_key = $value;
					} elseif (strpos($value, 'name')) {
						if (!isset($text_key))
							$text_key = $value;
					} else {
						if (count($free_key) < 2)
							array_push($free_key, $value);
					}
				}
				
				if (!isset($value_key))
					$value_key = array_shift($free_key);
				if (!isset($text_key))
					$text_key = array_shift($free_key);
				if (!$value_key || !$text_key)
					return '';
				
				foreach ($option as $item) {
					$html .= '<option value="' . $item[$value_key] . '"' . ($item[$value_key] == $selected_value ? ' selected="selected"' : '') . '>' . $item[$text_key] . '</option>';
				}
			}
		
		} else {
			foreach ($option as $value => $text) {
				$html .= '<option value="' . $value . '"' . ($value == $selected_value ? ' selected="selected"' : '') . '>' . $text . '</option>';
			}
		}
		
		return $html;
	}

	/**
	 * 取得JSON化后的字符串
	 * @param mixed $value
	 */
	public static function json($value) {
		return Json::encode($value);
	}

	public static function readable($value, $type = 'var') {
		if ($type == 'json') {
			self::json($value);
		} elseif ($type == 'html') {
			return highlight_string($value, TRUE);
		} else {
			return var_export($value, TRUE);
		}
	
	}
}