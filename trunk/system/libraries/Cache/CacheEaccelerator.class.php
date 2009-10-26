<?php 
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.atomcode.cn
 * @link http://www.atomcode.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-6
 * @filesource 
 * 
 */
/**
 * CacheEaccelerator
 *
 * @category   AtomCode
 * @package  libraries
 * @subpackage  cache
 */
class CacheEaccelerator extends Cache
{

	/**
	 *
	 * 架构函数
	 *
	 * @access public 
	 *
	 */
	public function __construct($options='')
	{
		$this->type = strtoupper(substr(__CLASS__,6));
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
		 return eaccelerator_get($name);
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
		 eaccelerator_lock($name);
		 return eaccelerator_put ($name, $value, $expire);
	 }


	/**
	 *
	 * 删除缓存
	 *
	 * @access public 
	 *
	 * @param string $name 缓存变量名
	 *
	 * @return boolen
	 *
	 */
	 public function rm($name)
	 {
		 return eaccelerator_rm($name);
	 }

}//类定义结束
?>