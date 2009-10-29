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
 * Pgsql数据库驱动类
 *
 * @category   AtomCode
 * @package  Db
 * @subpackage  databases
 */
Class DbPgsql extends Db{

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
		if ( !extension_loaded('pgsql') ) 
		{
			throw new Exception(L('_NOT_SUPPERT_').':pgsql');
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
			$conn = $this->pconnect ? 'pg_pconnect':'pg_connect';
			$this->linkID[$linkNum] =  $conn(
			'host='		 . $config['hostname'] .
			' port='			. $config['hostport'] .
			' dbname='  . $config['database'] .
			' user='			. $config['username'] .
			' password='	. $config['password']
			);

			if (pg_connection_status($this->linkID[$linkNum]) !== 0)
			{
				throw new Exception($this->error(false));
			}
			$pgInfo = pg_version($this->linkID[$linkNum]);
			$this->dbVersion = $pgInfo['server'];
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
		@pg_free_result($this->queryID);
		$this->queryID = 0;
	}

	/**
	 *
	 * 执行查询 主要针对 SELECT, SHOW 等指令
	 * 返回数据集
	 *
	 * @access protected
	 *
	 * @param string $str  sql指令
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
		$this->queryTimes ++;
		$this->Q(1);
		$this->queryID = pg_query($this->_linkID,$this->queryStr );
		$this->debug();
		if ( !$this->queryID ) 
		{
			if ( $this->debug || $this->Config['DEBUG_MODE'])
				throw new Exception($this->error());
			else
				return false;
		}
		 else {
			$this->numRows = pg_num_rows($this->queryID);
			//$this->numCols = pg_num_fields($this->queryID);
			$this->resultSet = $this->getAll();
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

		$this->writeTimes ++;
		$this->W(1);
		$this->debug();
		$tableName  =   '';
		if(substr($this->queryStr,0,6)=="INSERT")
		{
			$tableName=explode(" ",$this->queryStr);
			$tableName=";select last_value from {$tableName[2]}_id_seq;";
		}

		$result =   pg_query($this->_linkID,$this->queryStr.$tableName);
		$this->debug();
		if ( false === $result ) 
		{
			if ( $this->debug || $this->Config['DEBUG_MODE'])
				throw new Exception($this->error());
			else
				return false;
		}
		 else {
			$this->numRows = pg_affected_rows($result);
			if($tableName!="")
			{
				$result=pg_fetch_array($result);
				$this->lastInsID =$result[0];
			}
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
		if ( !$this->_linkID ) return false;
		//数据rollback 支持
		if ($this->transTimes == 0) 
		{
			pg_exec($this->_linkID,'begin;');
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
			$result = pg_exec($this->_linkID,'end;');
			if(!$result)
			{
				throw new Exception($this->error());
				return false;
			}
			$this->transTimes = 0;
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
			$result = pg_exec($this->_linkID,'abort;');
			if(!$result)
			{
				throw new Exception($this->error());
				return false;
			}
			$this->transTimes = 0;
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
		// 查询结果
		if($this->resultType== DATA_TYPE_OBJ)
		{
			$this->result = pg_fetch_object($this->queryID);
			$stat = is_object($this->result);
		}
		else
		{
			$this->result = pg_fetch_assoc($this->queryID);
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
	 * @return string
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
			if(pg_result_seek($this->queryID,$seek))
			{
				if($this->resultType== DATA_TYPE_OBJ)
				{
					//返回对象集
					$result = pg_fetch_object($this->queryID);
				}
				else
				{
					// 返回数组集
					$result = pg_fetch_assoc($this->queryID);
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
	 * @return resultSet
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
		if($this->numRows >0) 
		{
			if(is_null($resultType)){ $resultType   =  $this->resultType ; }
			$fun	=   $resultType== DATA_TYPE_OBJ ? 'pg_fetch_object':'pg_fetch_assoc';
			for($i=0;$i<$this->numRows ;$i++ )
			{
				$result[$i] =   $fun($this->queryID);
			}
			pg_result_seek($this->queryID,0);
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
	public function getFields($tableName) 
	{
		$result   =  $this->_query("select fields_name as \"Field\",fields_type as \"Type\",fields_not_null as \"Null\",fields_key_name as \"Key\",fields_default as \"Default\",fields_default as \"Extra\" from table_msg('{$tableName}');");
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
			'primary' => (strtolower($val['Key']) == $tableName.'_pkey'),
			'autoInc' => (strtolower($val['Extra']) == "nextval('{$tableName}_id_seq'::regclass)"),
			);
		}
		return $info;
	}

	/**
	 *
	 * 取得数据库的表信息
	 *
	 * @access public
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function getTables($dbName='') 
	{
		$result = $this->_query("select tablename as Tables_in_test from pg_tables where  schemaname ='public'");
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
	 * @access public
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function close() 
	{
		if (!empty($this->queryID))
		pg_free_result($this->queryID);
		if(!pg_close($this->_linkID))
		{
			throw new Exception($this->error(false));
		}
		$this->_linkID = 0;
	}

	/**
	 *
	 * 数据库错误信息
	 * 并显示当前的SQL语句
	 *
	 * @access public
	 *
	 * @return string
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function error($result = true) 
	{
		if($result)
		{
			$this->error = pg_result_error($this->queryID);
		}
		else
		{
			$this->error = pg_last_error($this->_linkID);
		}
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
	 * @access public
	 *
	 * @param string $str  SQL指令
	 *
	 * @return string
	 *
	 * @throws ThinkExecption
	 *
	 */
	public function escape_string($str) 
	{
		return pg_escape_string($str);
	}

}//类定义结束
?>