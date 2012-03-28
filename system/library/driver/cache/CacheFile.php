<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * CacheFile
 *
 * File Caching Class 
 *
 * @package		AtomCode
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class CacheFile extends Driver implements CacheInterface {
	protected $_cache_path;

	/**
	 * Constructor
	 */
	public function __construct() {
		$path = get_config('cache_path');
		
		$this->_cache_path = ($path == '') ? APP_PATH . '/cache/' : $path;
	}

	/**
	 * Fetch from cache
	 *
	 * @param 	mixed		unique key id
	 * @return 	mixed		data on success/FALSE on failure
	 */
	public function get($id) {
		if (!file_exists($this->_cache_path . $id)) {
			return FALSE;
		}
		
		$data = file_get_contents($this->_cache_path . $id);
		$data = unserialize($data);
		
		if (time() > $data['time'] + $data['ttl']) {
			unlink($this->_cache_path . $id);
			return FALSE;
		}
		
		return $data['data'];
	}

	// ------------------------------------------------------------------------
	

	/**
	 * Save into cache
	 *
	 * @param 	string		unique key
	 * @param 	mixed		data to store
	 * @param 	int			length of time (in seconds) the cache is valid 
	 * - Default is 60 seconds
	 * @return 	boolean		true on success/FALSE on failure
	 */
	public function save($id, $data, $ttl = 60) {
		$contents = array('time' => time(), 'ttl' => $ttl, 'data' => $data);
		
		if (file_put_contents($this->_cache_path . $id, serialize($contents))) {
			@chmod($this->_cache_path . $id, 0777);
			return TRUE;
		}
		
		return FALSE;
	}

	// ------------------------------------------------------------------------
	

	/**
	 * Delete from Cache
	 *
	 * @param 	mixed		unique identifier of item in cache
	 * @return 	boolean		true on success/FALSE on failure
	 */
	public function delete($id) {
		return unlink($this->_cache_path . $id);
	}

	// ------------------------------------------------------------------------
	

	/**
	 * Clean the Cache
	 *
	 * @return 	boolean		FALSE on failure/true on success
	 */
	public function clean() {
		return FileHelper::deleteFiles($this->_cache_path);
	}

	// ------------------------------------------------------------------------
	

	/**
	 * Cache Info
	 *
	 * Not supported by file-based caching
	 *
	 * @param 	string	user/filehits
	 * @return 	mixed 	FALSE
	 */
	public function cacheInfo($type = NULL) {
		return stat($this->_cache_path);
	}

	// ------------------------------------------------------------------------
	

	/**
	 * Get Cache Metadata
	 *
	 * @param 	mixed		key to get cache metadata on
	 * @return 	mixed		FALSE on failure, array on success.
	 */
	public function getMetaData($id) {
		if (!file_exists($this->_cache_path . $id)) {
			return FALSE;
		}
		
		$data = file_get_contents($this->_cache_path . $id);
		$data = unserialize($data);
		
		if (is_array($data)) {
			$data = $data['data'];
			$mtime = filemtime($this->_cache_path . $id);
			
			if (!isset($data['ttl'])) {
				return FALSE;
			}
			
			return array('expire' => $mtime + $data['ttl'], 'mtime' => $mtime);
		}
		
		return FALSE;
	}

	// ------------------------------------------------------------------------
	

	/**
	 * Is supported
	 *
	 * In the file driver, check to see that the cache directory is indeed writable
	 * 
	 * @return boolean
	 */
	public function isSupported($driver) {
		return is_really_writable($this->_cache_path);
	}
}
// END CacheFile CLASS

/* Location: ./system/library/driver/cache/CacheFile.php */