<?php
class user extends container {
	
	function __construct() {
		parent::__construct ();
	
	}
	
	function NewsList($param)
	{
		
		return array(array('id'=>4,'name'=>'eachcan','year'=>'2009'),array('id'=>5,'name'=>'asdfsdfsd','year'=>'2009'));
	}
}

?>