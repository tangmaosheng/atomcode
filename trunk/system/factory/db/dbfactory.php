<?php
class DBFactory
{

	function DBFactory()
	{
	}

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
			$file = 'drivers/' . $params['TYPE'] . '.class.php';
			require_once $file;
			
			$class = ucfirst($params['TYPE']);
			
			$instance = new $class();
			
			try
			{
				$instance->connect($params);
			}
			catch (MySqlException $e)
			{
				$e->showError();
			}  
		}
		
		
		return $instance;
	}

	/**
	 * Gets a reference to the only instance of database class. Currently
	 * only being used within the installer.
	 * 
     * @static
     * @staticvar   object  The only instance of database class
     * @return      object  Reference to the only instance of database class
	 */
	function &getDB()
	{
		static $database;
		
		if (!isset($database)) 
		{
			$file = '/drivers/' . $params['db_type'] . '.class.php';
			require_once $file;
			$class = ucfirst($params['db_type']);
			$instance = new $class();
		}
		
		return $database;
	}


}
?>