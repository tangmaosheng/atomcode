<?php
/**
 * connection to a mysql database
 * @abstract
 * @author      jerry  <jerryjiang15@163.com>
 * @package     database
 */
class MySQL extends DB
{
	var $connection;
	var $fields = array();
	var $EOF = 0;
	var $FetchMode = 'assoc';
	var $result;
	
	/**
	 * connect to the database
	 * 
     * @param bool $selectdb select the database now?
     * @return bool successful?
	 */
	function connect($params)
	{
		$GLOBALS['EXECS'] = 0;
		
		$this->connection = @mysql_connect($params['HOST'],$params['USER'],$params['PWD']);
		
		if (!$this->connection) 
		{
			return false;
		}
		
		if(!empty($params['NAME'])) 
		{
			if(!empty($params['CHARSET']))
			{
				@mysql_query("set names '" . $params['CHARSET'] . "'");
			} 
			
			if(!$this->selectDB($params['NAME']))
			{
				return false;
			}
		}
		
		return true;

	}
	
	/**
	 * 选择数据库
	 *
	 * @param string $db
	 * @return boolean
	 */
	function selectDB($db)
	{
		//echo $db;
		return mysql_select_db($db);
	}


	/**
	 * Close MySQL connection
	 * 
	 */
	function close() 
	{
		@mysql_close($this->connection);
	}



    /**
     * perform a query on the database
     * @return resource query result or FALSE if successful
     * or TRUE if successful and no result
     */
	function query($query)
	{

		$GLOBALS['EXECS']++;

		$result =& mysql_query($query, $this->connection);
		return $result;

	}


	function Execute($query)
	{

		$this->result = $this->query($query);

 		if($this->result) 
 		{
			if($this->FetchMode == 'num') 
			{
				if($this->fields = @mysql_fetch_array($this->result, MYSQL_NUM))

					$this->EOF = 0;

				else

					$this->EOF = 1;

			} 
			elseif($this->FetchMode == 'assoc') 
			{

				if($this->fields = @mysql_fetch_array($this->result, MYSQL_ASSOC))

					$this->EOF = 0;

				else

					$this->EOF = 1;
					
			} 
			else 
			{
				if($this->fields = mysql_fetch_array($this->result))

					$this->EOF = 0;

				else

					$this->EOF = 1;
			}
			
		} 
		else 
		{
			$this->EOF = 1;
		}

		return $this;

	}



	function moveNext()
	{

		if($this->FetchMode == 'num') 
		{

			if($this->fields = mysql_fetch_array($this->result, MYSQL_NUM))

				$this->EOF = 0;

			else

				$this->EOF = 1;

		} 
		elseif($this->FetchMode == 'assoc') 
		{

			if($this->fields = mysql_fetch_array($this->result, MYSQL_ASSOC))

				$this->EOF = 0;

			else

				$this->EOF = 1;

		} 
		else 
		{

			if($this->fields = mysql_fetch_array($this->result))

				$this->EOF = 0;

			else

				$this->EOF = 1;

		}
	}



	function getRow($query)
	{
		$Query = $this->query($query);

		$GLOBALS[EXECS]++;

		$Query = mysql_fetch_array($Query, MYSQL_ASSOC);

		return $Query;
	}



	function fetchRow()
	{

		return mysql_fetch_array($this->result, MYSQL_ASSOC);

	}



	function fetch_array($query) 
	{
		$Query = mysql_fetch_array($query);

		return $Query;
	}



	function selectLimit($query, $start = NULL, $offset = NULL)
	{

		if(empty($offset) && empty($start))

			$query = $query;

		elseif(empty($offset) && !empty($start))

			$query = $query." LIMIT $start";

		elseif(!empty($offset) && !empty($start))

			$query = $query." LIMIT $start, $offset";



		$this->result =  $this->query($query);

		//$GLOBALS[EXECS]++;
		
		if($this->result) 
		{
			$this->fields = mysql_fetch_array($this->result, MYSQL_ASSOC);

			$this->EOF = 0;

		} else {

			$this->EOF = 1;
		}



		return $this;

	}



    function freeResult($query) 
    {

    	mysql_free_result($query);

    }



	function getInsertID() 
	{

		return mysql_insert_id();

	}



	function errormsg()
	{

		$result["message"] = mysql_error($this->connection);

		$result["code"] = mysql_errno($this->connection);

		return $result;

	}



	function error() {

		return mysql_error();

	}



	function errno() {

		return mysql_errno();

	}



	function escape_string($string)

	{

		return  mysql_real_escape_string($string);

	}



	function setFetchMode($mode)
	{
		$this->FetchMode = $mode;
	}



	function FieldCount()
	{
		return mysql_num_fields($this->result);
	}



	function info()
	{

		return mysql_get_server_info();

	}

	function recordCount($query)
	{
		$query	= $this->query($query);

		return mysql_num_rows($query);
	}

}

?>