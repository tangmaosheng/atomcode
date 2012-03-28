<?php  if ( ! defined('BASE_PATH')) exit('No direct script access allowed');
/**
 * AtomCode
 *
 * A open source application,welcome to join us to develop it.
 *
 * @package		AtomCode
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
 $config['routes']['default_controller'] = 'welcome';
 $config['routes']['404_override'] = 'Welcome/error/404';