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
 * 
 *
 * @category   AtomCode
 * @package  libraries
 * @subpackage  cache
 */
class CacheApc extends Cache
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
		parent::__construct();
		if(!function_exists('apc_cache_info')) 
		{
			throw new Exception(L('_NOT_SUPPERT_').':Apc');
		}
		$this->expire = isset($options['expire'])?$options['expire']:$this->config['CACHE']['TIME'];
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
	 function get($name)
	 {
		$this->Q(1);
		 return apc_fetch($name);
	 }

	/**
	 *
	 * 写入缓存
	 * 
	 *
	 * @access public 
	 *
	 * @param string $name 缓存变量名
	 * @param mixed $value  存储数据
	 *
	 * @return boolen
	 *
	 */
	 function set($name, $value, $ttl = null)
	 {
		$this->W(1);
		if(isset($ttl) && is_int($ttl))
			$expire = $ttl;
		else 
			$expire = $this->expire;
		 return apc_store($name, $value, $expire);
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
	 function rm($name)
	 {
		 return apc_delete($name);
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
	function clear()
	{
		return apc_clear_cache();
	}

}//类定义结束
?>