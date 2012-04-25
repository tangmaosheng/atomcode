<?php
if (!defined('BASE_PATH'))
	exit('No direct script access allowed');

/**
 * Model Class
 *
 * 模型也是 MVC 非常重要的一层。它提供了数据访问的接口。
 * 
 * 在本框架中，模型的作用主要是访问数据库，并封装了访问数据库的接口，使用 ActiveRecord 进行拼接SQL语句。
 * ActiveRecord 可以使用非常少的代码，即可方便且安全的访问数据库。
 * 
 * 模型名与表名一一对应，但不包括前缀，请注意表名命名规则。例：<br>
 * 表名为：ac_session，其中 ac_ 为前缀，则模型名要命名为： SessionModel。这样SQL语句中就可以不用指定表名
 * 即可进行操作。如果要在 SessionModel 中访问 ac_user 表，只需要加上 <code>$this->from('user');</code> 即可。
 * 
 * 数据库模型分为单库模式和多库模式，对于每一个数据库又可以是单独服务器或 master/slave 模式。配置规则示例
 * 请参见 system/config/database.php。<br>
 * 在 master/slave 中，我们仅支持单个主库，如果有多个从库，则模型会随机选择一个作为当前查询库。<br>
 * 多库模式中，需要自己切换数据库，否则查询时会自动连接默认数据库。
 * 
 * 
 * @package		AtomCode
 * @subpackage	core
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
abstract class Model {

	protected $nosql = FALSE;

	/**
	 * 全局配置
	 * @var array
	 */
	protected static $dbConfigs, $dbLinks = array(), $dbDrivers = array(), $multiple, $default;

	protected static $sqls, $queryTime;

	/**
	 * @var Array
	 */
	protected $myConfig = array();

	/**
	 * @var Resource
	 */
	protected $myLink = NULL;

	/**
	 * @var DbDriver
	 */
	protected $myDriver = NULL;

	/**
	 * @var DbData
	 */
	protected $dbData;

	private $doNotExecuteReset = FALSE;

	protected $table, $database, $lastSql;

	protected static $instance;

	public function __construct() {
		$this->init();
		log_message("Model Class Initialized", "debug");
	}

	/**
	 * 初始化运行环境
	 */
	protected function init() {
		// Do not use database
		if ($this->nosql) {
			return;
		}
		
		// Read Database Configures
		if (!isset(self::$dbConfigs)) {
			self::$dbConfigs = load_config('database');
			self::$multiple = self::$dbConfigs['multiple'];
			self::$default = self::$dbConfigs['default'];
		}
		
		// Set Current Model configure
		if (!$this->myConfig) {
			if (!self::$multiple) {
				$this->myConfig = & self::$dbConfigs;
			} else {
				if ($this->database && array_key_exists($this->database, self::$dbConfigs['dbs'])) {
					$this->myConfig = & self::$dbConfigs['dbs'][$this->database];
				} else {
					$this->myConfig = reset(self::$dbConfigs['dbs']);
					$this->database = key(self::$dbConfigs['dbs']);
				}
			}
		}
		
		// Current table name
		$this->table || $this->table = $this->myConfig['table_prefix'] . strtolower(substr(get_class($this), 0, -5));
		
		if (!isset($this->myConfig['type']) || $this->myConfig['type'] == '') {
			$this->myConfig['type'] = 'mysql';
		} else {
			$this->myConfig['type'] = strtolower($this->myConfig['type']);
		}
		
		if (!$this->myDriver) {
			if (!array_key_exists($this->myConfig['type'], self::$dbDrivers)) {
				self::$dbDrivers[$this->myConfig['type']] = Db::loadDriver($this->myConfig['type']);
			}
			
			$this->myDriver = self::$dbDrivers[$this->myConfig['type']];
		}
		$this->myDriver->setErrorHandler($this);
		
		if (!$this->myLink) {
			if (self::$multiple) {
				if (!array_key_exists($this->database, self::$dbLinks)) {
					self::$dbLinks[$this->database] = $this->myDriver->connect($this->myConfig);
				}
				
				$this->myLink = self::$dbLinks[$this->database];
			} else {
				if (!self::$dbLinks) {
					self::$dbLinks = $this->myDriver->connect($this->myConfig);
				}
				
				$this->myLink = self::$dbLinks;
			}
		}
		
		$this->reset();
	}

	/**
	 * 取得当前模型对象
	 * 
	 * 一般交由子类调用，用于取到当前模型的实例
	 * 
	 * @see Model::instance()
	 * @param string $class
	 * @return Model
	 */
	public static function &getInstance($class) {
		if (!isset(self::$instance[$class])) {
			self::$instance[$class] = new $class();
		}
		
		return self::$instance[$class];
	}

	/**
	 * 取得当前模型实例
	 * 
	 * 由子类实现，不需要特殊处理请参照以下写法：{@example ../model/SessionModel.php 18 6}
	 * 
	 * @see Model::getInstance()
	 * @return Model
	 */
	public static function &instance() {
		return self::getInstance(get_called_class());
	}

	protected function getTable() {
		return $this->table;
	}

	protected function getDatabaseName() {
		return $this->myConfig['name'];
	}

	public function limit($limit, $offset = NULL) {
		$this->dbData->limit = array('limit' => $limit, 'offset' => $offset);
	}

	public function select($columns, $escape = TRUE) {
		$this->dbData->selects[] = array('col' => $columns, 'escape' => $escape);
	}

	public function join($table, $conditions, $join_type = 'inner', $escape = TRUE, $index_hint = NULL) {
		$this->dbData->joins[] = array('table' => $table, 'cond' => $conditions, 'type' => $join_type, 'escape' => $escape, 'index_hint' => $index_hint);
	}

	public function where($key, $value = NULL, $escape = TRUE, $logic = 'and') {
		if (is_string($key)) {
			$this->dbData->wheres[strtoupper($logic)][] = array('key' => $key, 'value' => $value, 'escape' => $escape);
		} else {
			$k = key($key);
			if (is_numeric($k)) {
				$this->dbData->wheres[strtoupper($logic)] = $this->dbData->wheres[strtoupper($logic)] ? array_merge($this->dbData->wheres[strtoupper($logic)], $key) : $key;
			} else {
				foreach ($key as $k => $v) {
					$k = strtoupper($k);
					if ($k == 'AND' || $k == 'OR') $this->dbData->wheres[$k] = $this->dbData->wheres[$k] ? array_merge($this->dbData->wheres[$k], $v) : $v;
				}
			}
		}
	}

	public function orWhere($where1, $where2) {
		$this->dbData->wheres["OR"] = $this->dbData->wheres["OR"] ? array_merge($this->dbData->wheres["OR"], func_get_args()) : func_get_args();
	}

	public function andWhere($where1, $where2) {
		$this->dbData->wheres["AND"] = $this->dbData->wheres["AND"] ? array_merge($this->dbData->wheres["AND"], func_get_args()) : func_get_args();
	}

	public function newWhere($key, $value = NULL, $escape = TRUE) {
		return array('key' => $key, 'value' => $value, 'escape' => $escape);
	}

	public function prepareWhere($where_sql) {
		$this->dbData->wheres['AND'][] = array('psql' => $where_sql, 'params' => array_slice(func_get_args(), 1));
	}

	public function startTransaction($option = NULL) {
		return $this->myDriver->startTrans($option, $this->myLink);
	}

	public function commit($option = NULL) {
		return $this->myDriver->commit($option, $this->myLink);
	}

	public function setAutoCommit($auto = TRUE) {
		return $this->myDriver->setAutoCommit($auto, $this->myLink);
	}

	public function rollback($option = NULL) {
		return $this->myDriver->rollback($option, $this->myLink);
	}

	public function groupBy($columns, $direction = '', $option = '') {
		$this->dbData->groupBys[] = array('col' => $columns, 'direction' => $direction, 'option' => $option);
	}

	public function orderBy($columns, $direction = 'ASC') {
		$this->dbData->orderBys[] = array('col' => $columns, 'direction' => $direction);
	}

	public function having($key, $value = NULL, $escape = TRUE, $logic = 'and') {
		if (is_string($key)) {
			$this->dbData->havings[strtoupper($logic)][] = array('key' => $key, 'value' => $value, 'escape' => $escape);
		} else {
			$k = key($key);
			if (is_numeric($k)) {
				$this->dbData->havings[strtoupper($logic)] = $this->dbData->havings[strtoupper($logic)] ? array_merge($this->dbData->havings[strtoupper($logic)], $key) : $key;
			} else {
				$this->dbData->havings[strtoupper($k)] = $this->dbData->havings[strtoupper($k)] ? array_merge($this->dbData->havings[strtoupper($k)], $key) : $key;
			}
		}
		
		return $this;
	}

	public function orHaving($where1, $where2) {
		$this->dbData->havings["OR"] = $this->dbData->havings["OR"] ? array_merge($this->dbData->havings["OR"], func_get_args()) : func_get_args();
	}

	public function andHaving($where1, $where2) {
		$this->dbData->havings["AND"] = $this->dbData->havings["AND"] ? array_merge($this->dbData->havings["AND"], func_get_args()) : func_get_args();
	}

	public function newHaving($key, $value = NULL, $escape = TRUE) {
		return array('key' => $key, 'value' => $value, 'escape' => $escape);
	}

	public function prepareHaving($where_sql) {
		$this->dbData->havings[] = array('sql' => $where_sql, 'params' => array_slice(func_get_args(), 1));
	}

	public function leftJoin($table, $conditions, $escape = TRUE, $index_hint = NULL) {
		return $this->join($table, $conditions, 'LEFT', $escape, $index_hint);
	}

	public function rightJoin($table, $conditions, $escape = TRUE, $index_hint = NULL) {
		return $this->join($table, $conditions, 'RIGHT', $escape, $index_hint);
	}

	public function straightJoin($table, $conditions, $escape = TRUE, $index_hint = NULL) {
		return $this->join($table, $conditions, 'STRAIGHT_JOIN', $escape, $index_hint);
	}

	public function natureJoin($table, $direction = '', $index_hint = NULL) {
		return $this->join($table, NULL, 'NATURAL' . ($direction ? ' ' . $direction : ''), NULL, $index_hint);
	}

	public function reset() {
		unset($this->dbData);
		$this->dbData = new DbData();
		$this->dbData->table = $this->table;
	}

	public function from($table, $alias = NULL, $index_hint = NULL) {
		$this->dbData->froms[] = array('table' => $table, 'alias' => $alias, 'index_hint' => $index_hint);
	}

	public function subFrom($sql, $alias = NULL, $exclude_self = FALSE, $index_hint = NULL) {
		$this->dbData->subQueryNoTable = $exclude_self;
		$this->dbData->froms[] = array('sql' => $sql, 'alias' => $alias, 'index_hint' => $index_hint);
	}

	public function subWhere($key, $sql) {
		$this->dbData->wheres['AND'][] = array('key' => $key, 'sql' => $sql, 'escape' => FALSE);
	}

	public function subOrWhere($key, $sql) {
		$this->dbData->wheres['OR'][] = array('key' => $key, 'sql' => $sql, 'escape' => FALSE);
	}

	public function newSubWhere($key, $sql = NULL) {
		return array('key' => $key, 'sql' => $sql, 'escape' => FALSE);
	}

	/**
	 * 
	 * 当用于插入语句时，可以插入一条或者多条
	 * 用于更新语句时，则一次只能设置一条
	 * @param mixed $key
	 * @param mixed $value 如果key是一个字符串，则此处表示对应的值；如果key是一个数组，此处表示不会转义的字段列表
	 * @param boolean $escape
	 */
	public function set($key, $value = NULL, $escape = TRUE) {
		if (is_array($key)) {
			if (is_array(reset($key))) {
				$this->dbData->msets['values'] = $key;
				$this->dbData->msets['reserve_keys'] = $value;
			} else {
				foreach ($key as $k => $v) {
					$this->set($k, $v, is_array($value) ? !in_array($k, $value) : TRUE);
				}
			}
		
		} else {
			$this->dbData->sets[] = array('key' => $key, 'value' => $value, 'escape' => $escape);
		}
	}

	/**
	 * 
	 * 如果插入时键重复则更新的列
	 * @param mixed $key
	 * @param mixed $value 如果key是一个字符串，则此处表示对应的值；如果key是一个数组，此处表示不会转义的字段列表
	 * @param boolean $escape
	 */
	public function set2($key, $value = NULL, $escape = TRUE) {
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->set2($k, $v, !in_array($k, $value));
			}
		} else {
			$this->dbData->sets2[] = array('key' => $key, 'value' => $value, 'escape' => $escape);
		}
	}

	public function get($limit = NULL, $offset = NULL) {
		if ($limit || $offset)
			$this->limit($limit, $offset);
		
		$this->dbData->queryType = "select";
		
		if ($this->dbData->subQueryNoTable) {
			$this->dbData->table = $this->getTable();
		}
		
		return $this->__getResult();
	}

	public function update($set = NULL) {
		if ($set) {
			$this->set($set);
		}
		
		$this->dbData->queryType = "update";
		
		return $this->__getResult();
	}

	public function replace($set = NULL) {
		if ($set) {
			$this->set($set);
		}
		
		$this->dbData->queryType = "replace";
		
		return $this->__getResult();
	}

	public function replaceSelect($sql) {
		$this->showError(0, "Not support replace ... select");
		return ;
		$this->dbData->queryType = "replaceSelect";
		$this->dbData->selectSql = $sql;
		
		return $this->__getResult();
	}

	public function delete($table_list = array()) {
		$this->dbData->queryType = "delete";
		$this->dbData->deleteTables = $table_list;
		return $this->__getResult();
	}

	public function insert($set = NULL, $set2 = NULL) {
		if ($set)
			$this->set($set);
		if ($set2)
			$this->set2($set2);
		
		$this->dbData->queryType = "insert";
		
		return $this->__getResult();
	}

	public function insertSelect($sql, $set2 = NULL) {
		$this->showError(0, "Not support insert ... select");
		return ;
		if ($set2)
			$this->set2($set2);
		
		$this->dbData->queryType = "insertSelect";
		$this->dbData->selectSql = $sql;
		
		return $this->__getResult();
	}

	public function countAllResults() {
		$this->keep();
		$limit = $this->dbData->limit;
		$select = $this->dbData->selects;
		unset($this->dbData->limit, $this->dbData->selects);
		$this->select('count(1) num');
		$result = $this->__getResult();
		$this->dbData->limit = $limit;
		$this->dbData->selects = $select;
		return $result[0]['num'];
	}

	public function keep($do_not_execute_reset = TRUE) {
		$this->doNotExecuteReset = $do_not_execute_reset;
	}

	public function getByPage($page, $page_size) {
		$this->setOption(DbMysqlDriver::SQL_CALC_FOUND_ROWS);
		$offset = ($page - 1) * $page_size;
		$result1 = $this->get($page_size, $offset);
		
		$total = $this->myDriver->foundRows($this->myLink);
		$pageinfo = array('total' => $total, 'page' => $page, 'page_size' => $page_size, 'page_count' => ceil($total / $page_size));
		return array('result' => $result1, 'page' => $pageinfo);
	}

	public function showError($errno, $error, $sql = NULL) {
		if (!TEST_MODE && !$this->myConfig['show_error']) {
			return;
		}
		
		$error_obj = Error::instance();
		$title = "DB Query Error";
		$msg = array();
		if ($sql)
			$msg[] = 'SQL: ' . $this->lastSql;
		$msg[] = 'ERRNO: ' . $errno;
		$msg[] = 'ERROR: ' . $error;
		$error_obj->show_error($msg, $title, $this->myConfig);
	}

	public function setOption($options) {
		$this->dbData->options[] = $options;
	}

	public function getSql($type = '') {
		if ($type) {
			$this->dbData->queryType = $type;
		}
		return $this->myDriver->getSql($this->dbData);
	}

	public function query($sql) {
		$this->lastSql = $sql;
		
		if ($this->myConfig['save_queries']) {
			$time_start = microtime(TRUE);
		}
		
		if (TEST_MODE || $this->myConfig['show_error']) {
			$this->myDriver->setErrorHandler($this);
		}
		
		$result = $this->myDriver->query($sql, $this->myLink);
		if ($this->myConfig['save_queries']) {
			self::$sqls[] = $sql;
			self::$queryTime[] = microtime(TRUE) - $time_start;
		}
		
		return $this->myDriver->wrapResult($result);
	}

	private function __getResult() {
		$result = $this->query($this->getSql());
		
		if ($this->doNotExecuteReset) {
			$this->doNotExecuteReset = FALSE;
		} else {
			$this->reset();
		}
		
		return $result;
	}

	public function setIndexHint($index) {
		$this->dbData->index_hint = $index;
	}

	public function alias($alias) {
		$this->dbData->alias = $alias;
	}
}