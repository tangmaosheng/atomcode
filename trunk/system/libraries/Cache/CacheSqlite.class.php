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
 * Sqlite缓存类
 *--------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author	liu21st <liu21st@gmail.com>
 * @version   $Id$
 *--------------------
 */
class CacheSqlite extends Cache
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
		if ( !extension_loaded('sqlite') ) {	
			throw new Exception(L('_NOT_SUPPERT_').':sqlite');
		}
		if(empty($options))
		{
			$options= array
			(
				'db'		=> ':memory:',
				'table'	 => 'sharedmemory',
				'var'	   => 'var',
				'value'	 => 'value',
				'expire'	=> 'expire',
				'persistent'=> false
			);
		}
		$this->options = $options;
		$func = $this->options['persistent'] ? 'sqlite_popen' : 'sqlite_open';
		$this->handler = $func($this->options['db']);
		$this->connected = is_resource($this->handler);
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
		$name   = sqlite_escape_string($name);
		$sql = 'SELECT '.$this->options['value'].
			   ' FROM '.$this->options['table'].
			   ' WHERE '.$this->options['var'].'=\''.$name.'\' AND ('.$this->options['expire'].'=-1 OR '.$this->options['expire'].'>'.time().
			   ') LIMIT 1';
		$result = sqlite_query($this->handler, $sql);
		if (sqlite_num_rows($result)) 
		{
			$content   =  sqlite_fetch_single($result);
			if($this->config['CACHE']['COMPRESS'] && function_exists('gzcompress')) 
			{
				//启用数据压缩
				$content   =   gzuncompress($content);
			}
			return unserialize($content);
		}
		return false;
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
	public function set($name, $value,$expireTime=0)
	{
		$this->W(1);
		$expire =  !empty($expireTime)? $expireTime : $this->config['CACHE']['TIME'];
		$name  = sqlite_escape_string($name);
		$value = sqlite_escape_string(serialize($value));
		$expire =  ($expireTime==-1)?-1: (time()+$expire);
		if( $this->config['CACHE']['COMPRESS'] && function_exists('gzcompress')) 
		{
			//数据压缩
			$value   =   gzcompress($value,3);
		}
		$sql  = 'REPLACE INTO '.$this->options['table'].
				' ('.$this->options['var'].', '.$this->options['value'].','.$this->options['expire'].
				') VALUES (\''.$name.'\', \''.$value.'\', \''.$expire.'\')';
		sqlite_query($this->handler, $sql);
		return true;
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
		$name  = sqlite_escape_string($name);
		$sql  = 'DELETE FROM '.$this->options['table'].
			   ' WHERE '.$this->options['var'].'=\''.$name.'\'';
		sqlite_query($this->handler, $sql);
		return true;
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
		$sql  = 'delete from `'.$this->options['table'].'`';
		sqlite_query($this->handler, $sql);
		return ;
	}
}//类定义结束
?>