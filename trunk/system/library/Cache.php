<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * Cache
 *
 * 缓存类依赖配置文件 cache.php ，配置项包含： adapter, cachePath, backupDriver
 *
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */

class Cache extends DriverLibrary implements CacheInterface {
	protected $validDrivers = array('CacheApc', 'CacheFile', 'CacheMemcached', 'CacheDb');
	protected $_cachePath = NULL; // Path of cache files (if file-based cache)
	protected $_adapter = 'file';
	protected $_backupDriver;

	/**
	 * Constructor
	 *
	 * @param array
	 */
	public function __construct($config = array()) {
		if (!empty($config)) {
			$this->_initialize($config);
		}
	}

	/**
	 * 取得缓存内容
	 *
	 * @param 	string	
	 * @return 	mixed		value that is stored/FALSE on failure
	 */
	public function get($id) {
		return $this->{$this->_adapter}->get($id);
	}

	/**
	 * Cache Save
	 *
	 * @param 	string		Unique Key
	 * @param 	mixed		Data to store
	 * @param 	int			Length of time (in seconds) to cache the data
	 *
	 * @return 	boolean		true on success/FALSE on failure
	 */
	public function save($id, $data, $ttl = 60) {
		return $this->{$this->_adapter}->save($id, $data, $ttl);
	}

	/**
	 * Delete from Cache
	 *
	 * @param 	mixed		unique identifier of the item in the cache
	 * @return 	boolean		true on success/FALSE on failure
	 */
	public function delete($id) {
		return $this->{$this->_adapter}->delete($id);
	}

	/**
	 * Clean the cache
	 *
	 * @return 	boolean		FALSE on failure/true on success
	 */
	public function clean() {
		return $this->{$this->_adapter}->clean();
	}

	/**
	 * Cache Info
	 *
	 * @param 	string		user/filehits
	 * @return 	mixed		array on success, FALSE on failure	
	 */
	public function cacheInfo($type = 'user') {
		return $this->{$this->_adapter}->cacheInfo($type);
	}

	/**
	 * Get Cache Metadata
	 *
	 * @param 	mixed		key to get cache metadata on
	 * @return 	mixed		return value from child method
	 */
	public function getMetaData($id) {
		return $this->{$this->_adapter}->getMetaData($id);
	}

	/**
	 * Initialize
	 *
	 * Initialize class properties based on the configuration array.
	 *
	 * @param	array 	
	 * @return 	void
	 */
	private function _initialize($config) {
		$default_config = array('adapter', 'memcached');
		
		foreach ($default_config as $key) {
			if (isset($config[$key])) {
				$param = '_' . $key;
				
				$this->{$param} = $config[$key];
			}
		}
		
		if (isset($config['backup'])) {
			if (in_array('Cache' . $config['backup'], $this->validDrivers)) {
				$this->_backupDriver = $config['backup'];
			}
		}
	}

	/**
	 * Is the requested driver supported in this environment?
	 *
	 * @param 	string	The driver to test.
	 * @return 	array
	 */
	public function isSupported($driver) {
		static $support = array();
		
		if (!isset($support[$driver])) {
			$support[$driver] = $this->{$driver}->isSupported($driver);
		}
		
		return $support[$driver];
	}

	/**
	 * __get()
	 *
	 * @param 	child
	 * @return 	object
	 */
	public function __get($child) {
		$obj = parent::__get($child);
		
		if (!$this->isSupported($child)) {
			$this->_adapter = $this->_backupDriver;
		}
		
		return $obj;
	}
}

// End Cache Class


/**
 * 
 * 缓存接口
 */
interface CacheInterface {

	/**
	 * 取得缓存内容
	 * @param string $id
	 * @return mixed
	 */
	public function get($id);

	/**
	 * 保存缓存内容
	 * @param string $id
	 * @param string $data
	 * @param int $ttl  Time To Live 缓存有效期
	 * @return boolean 是否保存成功
	 */
	public function save($id, $data, $ttl = 60);

	/**
	 * 删除缓存
	 * @param string $id
	 * @return boolean 是否删除成功
	 */
	public function delete($id);

	/**
	 * 清除所有缓存
	 * @return boolean 是否清除成功
	 */
	public function clean();

	/**
	 * 取得缓存信息
	 */
	public function cacheInfo();

	/**
	 * 取得元数据
	 * 
	 * 数据包括创建时间，有效期等
	 * 
	 * @param string $id
	 */
	public function getMetaData($id);

	/**
	 * 测试是否支持某驱动
	 * 
	 * @param string $driver
	 */
	public function isSupported($driver);
}
// End CacheInterface Interface

/* End of file Cache.php */
/* Location: ./system/library/Cache.php */