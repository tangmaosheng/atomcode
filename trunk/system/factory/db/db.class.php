<?php
/**
 * 数据库接口类。
 *
 * 一个关于数据库接口类，可以通过这个类来驱动不同的数据库。
 * @package     db  (所属的模块名称)
 * @author      Jerry <jerryjiang15@163.com>
 * @version     $ID$
 */
require_once('dbfactory.php');
class DB 
{
	/**
	 * Prefix for tables in the database
	 * @var string
	 */
	var $prefix = '';
	/**
	 * reference to a {@link Logger} object
         * @see Logger
	 * @var object Logger
	 */
	var $logger;

	/**
	 * constructor
         * 
         * will always fail, because this is an abstract class!
	 */
	function DB()
	{
		// exit("Cannot instantiate this class directly");
	}
	
	/**
	 * 获得工厂对象
         * 
         * will always fail, because this is an abstract class!
	 */
	static function &getInstance()
	{
		global $var;
		return DBFactory::getDBConnection($var->config['DB']);
	}

	/**
	 * assign a {@link Logger} object to the database
	 * 
         * @see Logger
         * @param object $logger reference to a {@link Logger} object
	 */
	function setLogger(&$logger)
	{
		$this->logger =& $logger;
	}

}