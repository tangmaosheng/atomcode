<?php

class IndexController extends Controller {

	public function index() {
		$name = TestModel::instance()->getName();
		$this->_assign('name', "Nick");
		return $this->_display('index');
	}
}