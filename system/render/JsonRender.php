<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

/**
 * HtmlRender
 *
 * JSON 渲染引擎，用于输出为JSON格式数据
 *
 * @package		AtomCode
 * @subpackage	render
 * @category	render
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class JsonRender extends Render {
	public function __construct() {
	}

	/**
	 * 输出JSON内容
	 * @see iRender::display()
	 */
	public function display() {
		echo json_encode($this->values);
	}
}

?>