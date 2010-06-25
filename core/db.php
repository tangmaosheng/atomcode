<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2010-6-7
 * @filesource 
 */
class Db 
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
	 * get factory instance
	 * @return unknown_type
	 */
	static function &getInstance()
	{
		global $var;
		return DbFactory::getDBConnection($var->config['DB']);
	}
}
class DbFactory
{
	/**
	 * Get a reference to the only instance of database class and connects to DB
     * 
     * if the class has not been instantiated yet, this will also take 
     * care of that
	 * 
     * @static
     * @staticvar   object  The only instance of database class
     * @return      object  Reference to the only instance of database class
	 */
	function &getDBConnection($params)
	{
		static $instance;
		
		if (!isset($instance)) 
		{
			if ($params['DEPLOY_TYPE'])
			{
				foreach ($params as $key => $value)
				{
					if (!is_array($value)) continue;
				
					$file = SYS_PATH . '/factories/db/drivers/db' . $value['TYPE'] . '.class.php';
					$class = 'Db' . ucfirst($value['TYPE']);
					if (!class_exists($class))require $file;
					$instance[$key] = &new $class();
					
					try
					{
						$instance[$key]->init($value);
					}
					catch (MySqlException $e)
					{
						$e->showError();
					}
				}
			}
			else
			{
				$file = SYS_PATH . '/factories/db/drivers/db' . $params['TYPE'] . '.class.php';
				$class = 'Db' . ucfirst($params['TYPE']);
				if (!class_exists($class))require $file;
				$class = ucfirst($params['TYPE']);
				$instance = new $class();
				
				try
				{
					$instance->init($params);
				}
				catch (MySqlException $e)
				{
					$e->showError();
				}
			}
		}
		return $instance;
	}
}