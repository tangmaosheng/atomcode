<?php

class TestModel extends Model {
    
	public static function &instance() {
		return parent::getInstance(__CLASS__);
	}

	public function getName() {
		$s = microtime(TRUE);
		$this->limit(1);
		$r = $this->delete();
		echo  microtime(TRUE) - $s;
		return $r;
	}
}