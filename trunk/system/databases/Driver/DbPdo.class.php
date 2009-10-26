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
 * Pdo数据库驱动类
 *
 * @category   AtomCode
 * @package  Db
 * @subpackage  databases
 */
Class DbPdo extends Db{

	protected $PDOStatement = null;

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
		if ( !class_exists('PDO') ) 
		{
			throw new Exception(L('_NOT_SUPPERT_').':PDO');
		}
		if(!empty($config)) 
		{
			$this->Config   =   $config;
			if(empty($this->Config['params'])) 
			{
				$this->Config['params'] =   array();
			}
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
			if($this->pconnect) 
			{
				$config['params'][constant('PDO::ATTR_PERSISTENT')] = true;
			}
			try
			{
				$this->linkID[$linkNum] = new PDO( $config['dsn'], $config['username'], $config['password'],$config['params']);
			}
			catch (PDOException $e) 
			{
				throw new Exception($e->getMessage());
			}
			$this->linkID[$linkNum]->exec('SET NAMES '.$this->config['DB']['CHARSET']);
			// 因个别驱动不支持getAttribute方法 暂时注释
			//$this->dbVersion = $this->linkID[$linkNum]->getAttribute(constant("PDO::ATTR_SERVER_INFO"));
			// 标记连接成功
			$this->connected	=   true;
			// 注销数据库连接配置信息
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
		$this->PDOStatement = null;
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
			//$this->startTrans();
		}
		else 
		{
			//释放前次的查询结果
			if ( !empty($this->PDOStatement) ) {	$this->free();	}
		}
		$this->Q(1);
		$this->PDOStatement = $this->_linkID->prepare($this->queryStr);
		$result =   $this->PDOStatement->execute();
		$this->debug();
		if ( !$result ) 
		{
			if ( $this->debug || $this->Config['DEBUG_MODE'])
				throw new Exception($this->error());
			else
				return false;
		}
		 else {
			//$this->numCols = $this->PDOStatement->columnCount();
			$this->resultSet = $this->getAll();
			$this->numRows = count( $this->resultSet );
			if ( $this->numRows > 0 )
			{
				return $this->resultSet;
			}
			return false;
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
			if ( !empty($this->PDOStatement) ) {	$this->free();	}
		}
		$this->W(1);
		$this->PDOStatement	=	$this->_linkID->prepare($this->queryStr);
		$result	=	$this->PDOStatement->execute();
		$this->debug();
		if ( false === $result) 
		{
			if ( $this->debug || $this->Config['DEBUG_MODE'])
				throw new Exception($this->error());
			else
				return false;
		}
		 else {
			$this->numRows = $result;
			$this->lastInsID = $this->_linkID->lastInsertId();
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
			$this->_linkID->beginTransaction();
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
		if ( !$this->PDOStatement ) 
		{
			throw new Exception($this->error());
			return false;
		}
		if($this->resultType== DATA_TYPE_OBJ)
		{
			// 返回对象集
			$this->result = $this->PDOStatement->fetch(constant('PDO::FETCH_OBJ'));
			$stat = is_object($this->result);
		}
		else
		{
			// 返回数组集
			$this->result = $this->PDOStatement->fetch(constant('PDO::FETCH_ASSOC'));
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
	 * @param integer $seek 指针位置
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
		if ( empty($this->PDOStatement) ) 
		{
			throw new Exception($this->error());
			return false;
		}
		if($this->resultType== DATA_TYPE_OBJ)
		{
			//返回对象集
			$result = $this->PDOStatement->fetch(constant('PDO::FETCH_OBJ'),constant('PDO::FETCH_ORI_NEXT'),$seek);
		}
		else
		{
			// 返回数组集
			$result = $this->PDOStatement->fetch(constant('PDO::FETCH_ASSOC'),constant('PDO::FETCH_ORI_NEXT'),$seek);
		}
		return $result;
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
		if ( empty($this->PDOStatement) ) 
		{
			throw new Exception($this->error());
			return false;
		}
		//返回数据集
		$result = array();
		if(is_null($resultType)){ $resultType   =  $this->resultType ; }
		if($resultType== DATA_TYPE_ARRAY)
		{
			$result =   $this->PDOStatement->fetchAll(constant('PDO::FETCH_ASSOC'));
		}
		else
		{
			$result =   $this->PDOStatement->fetchAll(constant('PDO::FETCH_OBJ'));
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
		$this->initConnect(true);
		if($this->config['DB']['TABLE_DESCRIBE_SQL']) 
		{
			// 定义特殊的字段查询SQL
			$sql   = str_replace('%table%',$tableName,$this->config['DB']['TABLE_DESCRIBE_SQL']);
		}
		else
		{
			$sql   = 'DESCRIBE '.$tableName;
		}
		$sth	=   $this->_linkID->prepare($sql);
		$sth->execute();
		$result = $sth->fetchAll(constant('PDO::FETCH_ASSOC'));
		$info   =   array();
		foreach ($result as $key => $val) 
		{
			$name=$val['Field']?$val['Field']:$val['name'];
			$info[$name] = array(
				'name'	=>$name ,
				'type'	=> $val['Type']?  $val['Type'] :   $val['type'],
				'notnull' => (bool) ($val['Null'] === ''   ||  $val['notnull'] === ''), // not null is empty, null is yes
				'default' => $val['Default']? $val['Default'] :   $val['dflt_value'],
				'primary' => (strtolower($val['Key']) == 'pri'  || $val['pk']),
				'autoInc' => (strtolower($val['Extra']) == 'auto_increment'  ||  $val['pk']),
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
		if(!empty($dbName)) 
		{
		   $sql	= 'SHOW TABLES FROM '.$dbName;
		}
		else
		{
		   $sql	= 'SHOW TABLES ';
		}
		$result = $this->_query($sql);
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
		$this->_linkID = null;
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
	public function error() 
	{
		$error = $this->PDOStatement->errorInfo();
		$this->error = $error[2];
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
		return addslashes($str);
	}

}//类定义结束
?>