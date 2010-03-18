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
			if ($params['DEPLOY_TYPE'])
			{
				foreach ($params as $key => $value)
				{
					if (!is_array($value)) continue;
				
					$file = 'drivers/' . $value['TYPE'] . '.class.php';
					require_once $file;
					$class = ucfirst($value['TYPE']);
					$instance[$key] = new $class();
					
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
				$file = 'drivers/' . $params['TYPE'] . '.class.php';
				require_once $file;
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