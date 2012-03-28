<?php

class AdminModel extends Model {
    
	public static function &instance() {
		return parent::getInstance(__CLASS__);
	}

	public function getName() {
		return "World";
	}
}