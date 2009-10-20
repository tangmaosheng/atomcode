<?php
class user extends container {
	
	function __construct() {
		parent::__construct ();
	
	}
	
	function NewsList($param)
	{
		$param['name'] = 'NewsList';
		return $param;
	}
}

?>