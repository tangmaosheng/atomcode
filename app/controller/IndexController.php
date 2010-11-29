<?php

class IndexController extends Controller {
	function index() {
		$this->_assign('a', 'b');
		$this->_assign('info', array('c' => 'd'));
		
		$this->_display('index');
	}
}
