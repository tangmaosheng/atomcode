<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-6
 * @filesource
 * 
 */
/**
 *--------------------
 * Memcache缓存类
 *--------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author	liu21st <liu21st@gmail.com>
 * @version   $Id$
 *--------------------
 */
class CacheMemcache extends Cache
{//类定义开始

	/**
	 *
	 * 架构函数
	 *
	 * @access public
	 *
	 */
	function __construct($options='')
	{
		if ( !extension_loaded('memcache') ) 
		{
			throw new Exception('载入扩展失败,失败扩展名为:memcache');
		}
		if(empty($options)) 
		{
			$options = array
			(
				'host'  => '127.0.0.1',
				'port'  => 11211,
				'timeout' => false,
				'persistent' => false
			);
		}
		$func = $options['persistent'] ? 'pconnect' : 'connect';
		$this->expire = isset($options['expire'])?$options['expire']:$this->config['CACHE']['TIME'];
		$this->handler  = new Memcache;
		$this->connected = $options['timeout'] === false ?
			$this->handler->$func($options['host'], $options['port']) :
			$this->handler->$func($options['host'], $options['port'], $options['timeout']);
		$this->type = strtoupper(substr(__CLASS__,6));
	}

	/**
	 *
	 * 是否连接
	 *
	 * @access private
	 *
	 * @return boolen
	 *
	 */
	private function isConnected()
	{
		return $this->connected;
	}

	/**
	 *
	 * 读取缓存
	 *
	 * @access public
	 *
	 * @param string $name 缓存变量名
	 *
	 * @return mixed
	 *
	 */
	public function get($name)
	{
		$this->Q(1);
		return $this->handler->get($name);
	}

	/**
	 *
	 * 写入缓存
	 *
	 * @access public
	 *
	 * @param string $name 缓存变量名
	 * @param mixed $value  存储数据
	 *
	 * @return boolen
	 *
	 */
	public function set($name, $value, $ttl = null)
	{
		$this->W(1);
		if(isset($ttl) && is_int($ttl))
			$expire = $ttl;
		else
			$expire = $this->expire;
		return $this->handler->set($name, $value, 0, $expire);
	}

	/**
	 *
	 * 删除缓存
	 *
	 *
	 * @access public
	 *
	 * @param string $name 缓存变量名
	 *
	 * @return boolen
	 *
	 */
	public function rm($name, $ttl = false)
	{
		return $ttl === false ?
			$this->handler->delete($name) :
			$this->handler->delete($name, $ttl);
	}

	/**
	 *
	 * 清除缓存
	 *
	 * @access public
	 *
	 * @return boolen
	 *
	 */
	public function clear()
	{
		return $this->handler->flush();
	}
}//类定义结束
?>