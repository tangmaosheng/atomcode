<?php


class ac_base
{
	var $config;
	public function __construct()
	{
		 global $var;
		 
		 $this->config = &$var->config;
	}
}


?>