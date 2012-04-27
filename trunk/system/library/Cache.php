<?php

class Cache {

	private static $config = array();

	private static $instances;

	/**
	 * @return Cache
	 */
	public static function &instance($driver = '') {
		if (!self::$config) {
			self::$config = load_config('cache');
		}
		
		if (!self::$config) {
			throw new ValidateException("Configure for cache is not found", 0);
		}
		
		if (!$driver) {
			$driver = self::$config['default'];
		}
		
		if (!is_array(self::$config['drivers']) || !in_array($driver, self::$config['drivers'])) {
			$driver = 'file';
		}
		
		if (!self::$instances[$driver]) {
			$class = 'Cache' . ucfirst($driver) . 'Driver';
			self::$instances[$driver] = new $class();
			self::$instances[$driver]->setOptions(self::$config[$driver]);
		}
		
		return self::$instances[$driver];
	}

	/**
	 * @return CacheFileDriver
	 */
	public static function file() {
		return self::instance('file');
	}
}

interface CacheDriver {

	/**
	 * 取得缓存
	 * @param string $id
	 */
	public function get($id);

	/**
	 * 保存缓存
	 * @param string $id
	 * @param string $content
	 * @param string $ttl
	 */
	public function set($id, $content, $ttl);

	/**
	 * 删除缓存
	 * @param string $id
	 */
	public function delete($id);

	/**
	 * 设置选项
	 * @param string $name
	 * @param string $value
	 */
	public function setOption($name, $value);

	/**
	 * 批量设置选项
	 * @param Array $arr
	 */
	public function setOptions($arr);
}