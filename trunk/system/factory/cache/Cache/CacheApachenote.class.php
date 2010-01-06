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
 * 
 *
 * @category   AtomCode
 * @package  libraries
 * @subpackage  cache
 */
class CacheApachenote extends Cache
{//类定义开始


	/**
	 *
	 * 架构函数
	 *
	 * @access public 
	 *
	 */
	public function __construct($options='')
	{
		if(empty($options))
		{
			$options = array(		   
				'host' => '127.0.0.1',
				'port' => 1042,
				'timeout' => 10
		);
		}
		$this->handler = null;
		$this->open();
		$this->options = $options;
		$this->type = strtoupper(substr(__CLASS__,6));

	}

	/**
	 *
	 * 是否连接
	 *
	 * @access public 
	 *
	 * @return boolen
	 *
	 */
	public function isConnected()
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
		 $this->open();
		 $s = 'F' . pack('N', strlen($name)) . $name;
		 fwrite($this->handler, $s);

		 for ($data = ''; !feof($this->handler);) {
			 $data .= fread($this->handler, 4096);
		 }
		$this->Q(1);
		 $this->close();
		 return $data === '' ? '' : unserialize($data);
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
	public function set($name, $value)
	{
		$this->W(1);
		$this->open();
		$value = serialize($value);
		$s = 'S' . pack('NN', strlen($name), strlen($value)) . $name . $value;

		fwrite($this->handler, $s);
		$ret = fgets($this->handler);
		$this->close();
		$this->setTime[$name] = time();
		return $ret === "OK\n";
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
		 $this->open();
		 $s = 'D' . pack('N', strlen($name)) . $name;
		 fwrite($this->handler, $s);
		 $ret = fgets($this->handler);
		 $this->close();

		 return $ret === "OK\n";
	 }

	/**
	 *
	 * 关闭缓存
	 *
	 * @access private 
	 *
	 */
	 private function close()
	 {
		 fclose($this->handler);
		 $this->handler = false;
	 }

	/**
	 *
	 * 打开缓存
	 *
	 * @access private 
	 *
	 */
	 private function open()
	 {
		 if (!is_resource($this->handler)) {
			 $this->handler = fsockopen($this->options['host'], $this->options['port'], $_, $_, $this->options['timeout']);
			 $this->connected = is_resource($this->handler);		 
		 }
	 }

}//类定义结束
?>