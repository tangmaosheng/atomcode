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
 * Mysqli数据库驱动类
 *
 * @category   AtomCode
 * @package  Db
 * @subpackage  databases
 */
Class DbMysqli extends Db{

	/**
	 *
	 * 架构函数 读取数据库配置信息
	 *
	 * @access public
	 *
	 * @param array $config 数据库配置数组
	 *
	 */
	public function __construct($config='')
	{
		if ( !extension_loaded('mysqli') ) 
		{
			throw new Exception(L('_NOT_SUPPERT_').':mysqli');
		}
		if(!empty($config)) 
		{
			$this->Config   =   $config;
		}
	}

	/**
	 *
	 * 连接数据库方法
	 *
	 * @access public
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function connect($config='',$linkNum=0) 
	{
		if ( !isset($this->linkID[$linkNum]) ) 
		{
			if(empty($config))  $config =   $this->Config;
			$this->linkID[$linkNum] = new mysqli(
								$config['hostname'],
								$config['username'],
								$config['password'],
								$config['database'],
								$config['hostport']);
			if (mysqli_connect_errno()) 
			{
				throw new Exception(mysqli_connect_error());
			}
			if($this->autoCommit)
			{
				$this->linkID[$linkNum]->autocommit( true);
			}
			else 
			{
				$this->linkID[$linkNum]->autocommit( false);
			}
			$this->dbVersion = $this->linkID[$linkNum]->server_version;
			if ($this->dbVersion >= "4.1") 
			{
				// 设置数据库编码 需要mysql 4.1.0以上支持
				$this->linkID[$linkNum]->query("SET NAMES '".$this->config['DB']['CHARSET']."'");
			}
			//设置 sql_model
			if($this->dbVersion >'5.0.1')
			{
				$this->linkID[$linkNum]->query("SET sql_mode=''");
			}
			// 标记连接成功
			$this->connected	=   true;
			//注销数据库安全信息
			if(1 != $this->config['DB']['DEPLOY_TYPE']) unset($this->Config);
		}
		return $this->linkID[$linkNum];
	}

	/**
	 *
	 * 释放查询结果
	 *
	 * @access public
	 *
	 */
	public function free() 
	{
		mysqli_free_result($this->queryID);
		$this->queryID = 0;
	}

	/**
	 *
	 * 执行查询 主要针对 SELECT, SHOW 等指令
	 * 返回数据集
	 *
	 * @access protected
	 *
	 * @param string $sqlStr  sql指令
	 *
	 * @return ArrayObject
	 *
	 * @throws ThinkExecption
	 *
	 */
	protected function _query($str='') 
	{
		$this->initConnect(false);
		if ( !$this->_linkID ) return false;
		if ( $str != '' ) $this->queryStr = $str;
		if (!$this->autoCommit && $this->isMainIps($this->queryStr)) 
		{
			$this->startTrans();
		}
		else 
		{
			//释放前次的查询结果
			if ( $this->queryID ) {	$this->free();	}
		}
		$this->Q(1);
		$this->queryID = $this->_linkID->query($this->queryStr);
		$this->debug();
		if ( !$this->queryID ) 
		{
			if ( $this->debug || $this->Config['DEBUG_MODE'])
				throw new Exception($this->error());
			else
				return false;
		}
		 else {
			$this->numRows  = $this->queryID->num_rows;
			$this->numCols	= $this->queryID->field_count;
			$this->resultSet	= $this->getAll();
			return $this->resultSet;
		}
	}

	/**
	 *
	 * 执行语句 针对 INSERT, UPDATE 以及DELETE
	 *
	 * @access protected
	 *
	 * @param string $str  sql指令
	 *
	 * @return integer
	 *
	 * @throws ThinkExecption
	 *
	 */
	protected function _execute($str='') 
	{
		$this->initConnect(true);
		if ( !$this->_linkID ) return false;
		if ( $str != '' ) $this->queryStr = $str;
		if (!$this->autoCommit && $this->isMainIps($this->queryStr)) 
		{
			$this->startTrans();
		}
		else 
		{
			//释放前次的查询结果
			if ( $this->queryID ) {	$this->free();	}
		}
		$this->W(1);
		$result =   $this->_linkID->query($this->queryStr);
		$this->debug();
		if ( false === $result ) 
		{
			if ( $this->debug || $this->Config['DEBUG_MODE'])
				throw new Exception($this->error());
			else
				return false;
		}
		 else {
			$this->numRows = $this->_linkID->affected_rows;
			$this->lastInsID = $this->_linkID->insert_id;
			return $this->numRows;
		}
	}

	/**
	 *
	 * 启动事务
	 *
	 * @access public
	 *
	 * @return void
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function startTrans() 
	{
		$this->initConnect(true);
		//数据rollback 支持
		if ($this->transTimes == 0) 
		{
			$this->_linkID->autocommit(false);
		}
		$this->transTimes++;
		return ;
	}

	/**
	 *
	 * 用于非自动提交状态下面的查询提交
	 *
	 * @access public
	 *
	 * @return boolen
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function commit()
	{
		if ($this->transTimes > 0) 
		{
			$result = $this->_linkID->commit();
			$this->_linkID->autocommit( true);
			$this->transTimes = 0;
			if(!$result)
			{
				throw new Exception($this->error());
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * 事务回滚
	 *
	 * @access public
	 *
	 * @return boolen
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function rollback()
	{
		if ($this->transTimes > 0) 
		{
			$result = $this->_linkID->rollback();
			$this->transTimes = 0;
			if(!$result)
			{
				throw new Exception($this->error());
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * 获得下一条查询结果 简易数据集获取方法
	 * 查询结果放到 result 数组中
	 *
	 * @access public
	 *
	 * @return boolen
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function next() 
	{
		if ( !$this->queryID ) 
		{
			throw new Exception($this->error());
			return false;
		}
		if($this->resultType==DATA_TYPE_OBJ)
		{
			// 返回对象集
			$this->result = $this->queryID->fetch_object();
			$stat = is_object($this->result);
		}
		else
		{
			// 返回数组集
			$this->result = $this->queryID->fetch_assoc();
			$stat = is_array($this->result);
		}
		return $stat;
	}

	/**
	 *
	 * 获得一条查询结果
	 *
	 * @access public
	 *
	 * @param index $seek 指针位置
	 * @param string $str  SQL指令
	 *
	 * @return array
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function getRow($sql = null,$seek=0) 
	{
		if (!empty($sql)) $this->_query($sql);
		if ( !$this->queryID ) 
		{
			throw new Exception($this->error());
			return false;
		}
		if($this->numRows >0) 
		{
			if($this->queryID->data_seek($seek))
			{
				if($this->resultType== DATA_TYPE_OBJ)
				{
					//返回对象集
					$result = $this->queryID->fetch_object();
				}
				else
				{
					// 返回数组集
					$result = $this->queryID->fetch_assoc();
				}
			}
			return $result;
		}
		else 
		{
			return false;
		}
	}

	/**
	 *
	 * 获得所有的查询数据
	 * 查询结果放到 resultSet 数组中
	 *
	 * @access public
	 *
	 * @param string $resultType  数据集类型
	 *
	 * @return array
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function getAll($sql = null,$resultType=null) 
	{
		if (!empty($sql)) $this->_query($sql);
		if ( !$this->queryID ) 
		{
			throw new Exception($this->error());
			return false;
		}
		//返回数据集
		$result = array();
		//$info   = $this->queryID->fetch_fields();
		if($this->numRows>0) 
		{
			if(is_null($resultType)){ $resultType   =  $this->resultType ; }
			// 判断数据返回类型
			$fun	=   $resultType== DATA_TYPE_OBJ?'fetch_object':'fetch_assoc';
			//返回数据集
			for($i=0;$i<$this->numRows ;$i++ )
			{
				$result[$i] = $this->queryID->$fun();
			}
			$this->queryID->data_seek(0);
		}
		return $result;
	}

	/**
	 *
	 * 取得数据表的字段信息
	 *
	 * @access public
	 *
	 * @throws ThinkExecption
	 *
	 */
	function getFields($tableName) 
	{
		$result =   $this->_query('SHOW COLUMNS FROM '.$tableName);
		$info   =   array();
		foreach ($result as $key => $val) 
		{
			if(is_object($val)) 
			{
				$val	=   get_object_vars($val);
			}
			$info[$val['Field']] = array(
				'name'	=> $val['Field'],
				'type'	=> $val['Type'],
				'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
				'default' => $val['Default'],
				'primary' => (strtolower($val['Key']) == 'pri'),
				'autoInc' => (strtolower($val['Extra']) == 'auto_increment'),
			);
		}
		return $info;
	}

	/**
	 *
	 * 取得数据表的字段信息
	 *
	 * @access public
	 *
	 * @throws ThinkExecption
	 *
	 */
	function getTables($dbName='') 
	{
		if(!empty($dbName)) 
		{
		   $sql	= 'SHOW TABLES FROM '.$dbName;
		}
		else
		{
		   $sql	= 'SHOW TABLES ';
		}
		$result =   $this->_query($sql);
		$info   =   array();
		foreach ($result as $key => $val) 
		{
			$info[$key] = current($val);
		}
		return $info;
	}


	/**
	 *
	 * 关闭数据库
	 *
	 * @static
	 * @access public
	 *
	 * @throws ThinkExecption
	 *
	 */
	function close() 
	{
		if (!empty($this->queryID))
			$this->queryID->free_result();
		if (!$this->_linkID->close())
		{
			throw new Exception($this->error());
		}
		$this->_linkID = 0;
	}

	/**
	 *
	 * 数据库错误信息
	 * 并显示当前的SQL语句
	 *
	 * @static
	 * @access public
	 *
	 * @return string
	 *
	 * @throws ThinkExecption
	 *
	 */
	function error() 
	{
		$this->error = $this->_linkID->error;
		if($this->queryStr!='')
		{
			$this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
		}
		return $this->error;
	}

	/**
	 *
	 * SQL指令安全过滤
	 *
	 * @static
	 * @access public
	 *
	 * @param string $str  SQL指令
	 *
	 * @return string
	 *
	 * @throws ThinkExecption
	 *
	 */
	function escape_string($str) 
	{
		if($this->_linkID) 
		{
			return  $this->_linkID->real_escape_string($str);
		}
		else
		{
			return addslashes($str);
		}
	}

}//类定义结束
?>