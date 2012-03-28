<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

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
 * 调用技巧：<br>
 * 1. 链式调用<br>
 *   如果比较简单的条件下，可以调用：<code>$this->select('id')->where('dateline >', $date)->get();</code>来实现
 * 
 * 2. 条件语句保持<br>
 *   在有分页的时候，我们希望取完结果后，还要取全部结果的条数，但是此时还要再写一遍 where 语句，所以我们可以通过一个较简单
 *   的方法来实现，即调用：
 *   <code>
 *   $this->keepWhere(); // 可以让下一次查询不会清空 where 语句
 *   $list = $this->get($page_size, $offset); // 使用 where 做第一次查询
 *   $rows = $this->countAllResults(); // 使用 where 做第二次查询，本次查询会清空 where 条件
 *   </code>
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

	/**
	 * Db配置
	 * @var array
	 */
	protected $dbs = array();

	protected $dbConfig = array();

	protected $multiple, $default;

	protected $nosql = FALSE;

	/**
	 * @var DbActiveRecord 当前数据库查询所用的对象
	 */
	protected $currentDb;

	protected $currentGroup, $currentMode, $currentSelectMode;

	protected static $instance;

	protected $defaultTable, $fromTable;
	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		$this->currentGroup = '';
		$this->currentMode = '';
		$this->currentSelectMode = '';
		
		
		$this->dbConfig = load_config('database');
		$this->parseConfig();
		$this->defaultTable = strtolower(substr(get_class($this), 0, -5));
		if (!$this->nosql) {
			$this->autoSelectDb();
		}
		log_message('debug', "Model Class Initialized");
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
	public abstract static function &instance();

	/**
	 * 解析配置
	 * 
	 * @todo 暂时未考虑多库和Master/Slave模式，直接使用了单个数据库
	 */
	private function parseConfig() {
		$this->multiple = isset($this->dbConfig['multiple']) ? $this->dbConfig['multiple'] : FALSE;
		$this->default = isset($this->dbConfig['default']) ? $this->dbConfig['default'] : '';
	}

	/**
	 * 魔术方法，用于调用驱动中的方法
	 * 
	 * @param $method
	 * @param $args
	 * @return mixed
	 */
	public function __call($method, $args) {
		if (!$this->currentDb) {
			$this->autoSelectDb();
		}
		
		// @todo 将所有方法实现死调用，以便编辑器提示
		if ($this->currentDb) {
			return call_user_func_array(array(
				$this->currentDb, $method
			), $args);
		}
		
		return null;
	}

	/**
	 * 切换数据库
	 * 
	 * 仅在有多级数据库时有效，如果目标数据库为 master/slave 模式，仍然遵循自动选择的规则
	 * 
	 * @param string $db
	 */
	public function switchDb($db = '') {
	
	}

	/**
	 * 自动选择一个数据库服务器连接
	 * 
	 * 如果在 master/slave 模式下，自动选择一个从库并进行连接。主库或者非此模式则直接选择相应数据库
	 * 连接即可。
	 * 
	 * @param string $select_mode 查询模式，可选值有：select, update(etc.. not select)
	 */
	private function autoSelectDb($select_mode = 'select') {
		if (!$this->multiple) {
			if ($this->dbConfig['mode'] == '' || $this->dbConfig['mode'] == 'none') {
				$this->linkByConfig($this->dbConfig, 'default');
			}
		}
	}

	/**
	 * 按照配置进行连接数据库
	 * 
	 * 自动判断是否已连接相应的库，如果未连接，则会创建新的连接
	 * @param array $config
	 */
	private function linkByConfig($config, $name) {
		if (!isset($config['type']) or $config['type'] == '') {
			$config['type'] = 'mysql';
		}
		
		$db_driver = 'Db' . ucfirst(strtolower($config['type'])) . 'Driver';
		if (!class_exists($db_driver)) {
			require_once BASE_PATH . '/library/driver/db/' . $config['type'] . '/' . $db_driver . EXT;
		}
		
		if (!$this->currentDb) {
			$this->dbs[$name] = new $db_driver($config);
			$this->currentDb = $this->dbs[$name];
		}
	}

	/**
	 * 运行查询并取得查询结果
	 * 
	 * 取得的结果是一个二维数组，数组包括结果中每条记录和每条记录每个字段的值。<br>
	 * 用法示例：
	 * <code>
	 * $result = $this->get();
	 * // sql: select * from `table`;
	 * // result: array(0 => array('id' => 1, ...), ...);
	 * 
	 * $result = $this->get(10);
	 * // sql: select * from `table` limit 10
	 * 
	 * $result = $this->get(10, 100);
	 * // sql: select * from `table` limit 100, 10
	 * </code>
	 * @param int $limit
	 * @param int $offset
	 */
	public function get($limit = NULL, $offset = NULL) {
		if (!$this->fromTable) {
			$this->fromTable = $this->defaultTable;
		}
		$res = $this->currentDb->get($this->fromTable, $limit, $offset);
		$this->fromTable = '';
		return $res;
	}

	/**
	 * 构造FROM语句
	 * 
	 * 如果要访问非本模型对应的表，则需要指定该表。注意：如果要 join 其他表，请使用 join 方法。
	 * 
	 * @param unknown_type $table
	 */
	public function from($table) {
		$this->fromTable = $table;
		$this->currentDb->from($table);
		return $this;
	}
	
	public function limit($limit, $offset='') {
		$this->currentDb->limit($limit, $offset);
		return $this;
	}

	/**
	 * 构造  SELECT 语句
	 * 
	 * 指定要查询的字段名，可以以空格分隔原名与别名；也可以写几个字段的运算表达式。上述情况请将 $escape 参数置为
	 * false
	 * 
	 * 示例：
	 * <code>
	 * $this->select('id, age');
	 * $result = $this->get(10, 100);
	 * // sql: select `id`, `age` from `table` limit 100, 10
	 * </code>
	 * @param string | array $select 要查询的字段名
	 * @param null | boolean $escape 是否要转义
	 */
	public function select($select = '', $escape = null) {
		$this->currentDb->select($select, $escape);
		return $this;
	}

	/**
	 * 构造 SELECT MAX() 语句
	 * 
	 * 结果类似于：<br>
	 * select max("$select as $alias")
	 * 
	 * 示例：
	 * <code>
	 * $this->selectMax('age', 'max_age');
	 * $result = $this->get(10, 100);
	 * // sql: select max(`age`) as `max_age` from `table` limit 100, 10
	 * </code>
	 * 
	 * @param string $select
	 * @param string $alias
	 */
	public function selectMax($select = '', $alias = '') {
		$this->currentDb->select_max($select, $alias);
		return $this;
	}

	/**
	 * 构造 SELECT MIN() 语句
	 * 
	 * 与 {@link Model::selectMax()} 相似
	 * 
	 * @see Model::selectMax()
	 * @param string $select
	 * @param string $alias
	 */
	public function selectMin($select = '', $alias = '') {
		$this->currentDb->select_min($select, $alias);
		return $this;
	}

	/**
	 * 构造 SELECT AVG() 语句
	 * 
	 * 与 {@link Model::selectMax()} 相似
	 * 
	 * @see Model::selectMax()
	 * @param string $select
	 * @param string $alias
	 */
	public function selectAvg($select = '', $alias = '') {
		$this->currentDb->select_avg($select, $alias);
		return $this;
	}

	/**
	 * 构造 SELECT SUM() 语句
	 * 
	 * 与 {@link Model::selectMax()} 相似
	 * 
	 * @see Model::selectMax()
	 * @param string $select
	 * @param string $alias
	 */
	public function selectSum($select = '', $alias = '') {
		$this->currentDb->select_sum($select, $alias);
		return $this;
	}
	
	public function join($table, $cond, $type = '') {
		$this->currentDb->join($table, $cond, $type);
		return $this;
	}
	
	public function leftJoin($table, $cond) {
		$this->currentDb->join($table, $cond, 'LEFT');
		return $this;
	}
	
	public function rightJoin($table, $cond) {
		$this->currentDb->join($table, $cond, 'RIGHT');
		return $this;
	}
	
	/**
	 * 构造 WHERE 语句
	 * 
	 * 如果 WHERE 语句已存在，则以 AND 连接。<br>
	 * 如果未指定第三个参数，参数名值均会被转义。参数名加上 ` ，而值会使用 {@link DbDriver::escape()} 进行处理。
	 * 此函数的用法比较灵活，你可以以下 4 种方式之一来构造 WHERE 语句：
	 * 1. 简单名值对：
	 *   <code>
	 *   $goods_name = "fish's food";
	 *   $this->where('goods_name', $goods_name);
	 *   // where `goods_name` = 'fish\'s food';
	 *   </code>
	 * 2. 自定义比较条件：
	 *   <code>
	 *   $goods_name = "fish's food";
	 *   $this->where('goods_name !=', $goods_name);
	 *   // where `goods_name` != 'fish\'s food';
	 *   </code>
	 *   <b>注意：</b> 自定义比较符与字段名之前需要有一个空格，否则无法识别！
	 * 3. 关联数组：
	 *   <code>
	 *   $this->where(array('age >' => 18, 'level <' => 10));
	 *   // where `age` > 18 and level < 10;
	 *   </code>
	 * 4. 直接书写：<br>
	 *   这种写法更灵活，便于处理较为复杂的查询
	 *   <code>
	 *   $this->where('id !=', 100);
	 *   $this->where('(catid = 10 or catid = 11) and pub = 1 or pub = 2 or (pub = 3 and sword != \'test\'');
	 *   // where id != 100 and ((catid = 10 or catid = 11) and pub = 1 or pub = 2 or (pub = 3 and sword != 'test'));
	 *   </code>
	 * @param string | array $key
	 * @param null | string $value
	 * @param boolean $escape
	 */
	public function where($key, $value = NULL, $escape = TRUE) {
		$this->currentDb->where($key, $value, $escape);
		return $this;
	}

	/**
	 * 构造 WHERE .. OR 语句
	 * 
	 * 用法同 {@link Model::where()}, 只是与之前语句使用 OR 进行连接
	 * 
	 * @see Model::where()
	 * @param string | array $key
	 * @param null | string $value
	 * @param boolean $escape
	 */
	public function orWhere($key, $value = NULL, $escape = TRUE) {
		$this->currentDb->or_where($key, $value, $escape);
		return $this;
	}

	/**
	 * 构造 WHERE .. IN () 语句
	 * 
	 * 用法同 {@link Model::where()}中的第一种情况，只能使用简单的模式。同样，如果有其他的 WHERE 语句，
	 * 则会用 AND 连接
	 * 
	 * @see Model::where()
	 * @param string $key
	 * @param array $values
	 */
	public function whereIn($key = NULL, $values = NULL) {
		$this->currentDb->where_in($key, $values);
		return $this;
	}

	/**
	 * 构造 WHERE .. OR .. IN () 语句
	 * 
	 * 用法同{@link Model::whereIn()}，区别在于与之前语句使用 OR 连接
	 * 
	 * @see Model::where()
	 * @param string $key
	 * @param array $values
	 */
	public function orWhereIn($key = NULL, $values = NULL) {
		$this->currentDb->where_in($key, $values);
		return $this;
	}

	/**
	 * 构造 WHERE .. NOT IN () 语句
	 * 
	 * 用法同{@link Model::whereIn()}
	 * 
	 * @see Model::where()
	 * @param string $key
	 * @param array $values
	 */
	public function whereNotIn($key = NULL, $values = NULL) {
		$this->currentDb->where_not_in($key, $values);
		return $this;
	}

	/**
	 * 构造 WHERE .. OR .. NOT IN () 语句
	 * 
	 * 用法同{@link Model::whereIn()}，区别在于与之前语句使用 OR 连接
	 * 
	 * @see Model::where()
	 * @param string $key
	 * @param array $values
	 */
	public function orWhereNotIn($key = NULL, $values = NULL) {
		$this->currentDb->or_where_not_in($key, $values);
		return $this;
	}

	/**
	 * 构造 WHERE field LIKE '%$match%' 语句
	 * 
	 * 仍然是 WHERE 语句的一种，不需要在 $match 中写 %， 不支持自定义通配符，要想自定义则需要
	 * 直接使用 {@link Model::where()} 语句。
	 * 
	 * @see Model::where()
	 * @param string $field 字段名
	 * @param string $match 要匹配的内容
	 * @param string $side 在哪边加 % ，可选的值有： left, right, both
	 */
	public function like($field, $match = '', $side = 'both') {
		$this->currentDb->like($field, $match, $side);
		return $this;
	}

	/**
	 * 构造 WHERE field NOT LIKE '%$match%' 语句
	 * 
	 * 与 {@link Model::like()} 类似。
	 * 
	 * @see Model::where()
	 * @see Model::like()
	 * @param string $field 字段名
	 * @param string $match 要匹配的内容
	 * @param string $side 在哪边加 % ，可选的值有： left, right, both
	 */
	public function notLike($field, $match = '', $side = 'both') {
		$this->currentDb->not_like($field, $match, $side);
		return $this;
	}

	/**
	 * 构造 WHERE field NOT LIKE '%$match%' 语句
	 * 
	 * 与 {@link Model::like()} 类似，不同的是与之前语句相连需要
	 * 
	 * @see Model::where()
	 * @see Model::like()
	 * @param string $field 字段名
	 * @param string $match 要匹配的内容
	 * @param string $side 在哪边加 % ，可选的值有： left, right, both
	 */
	public function orLike($field, $match = '', $side = 'both') {
		$this->currentDb->or_like($field, $match, $side);
		return $this;
	}

	/**
	 * 构造 WHERE .. OR field NOT LIKE '%$match%' 语句
	 * 
	 * 与 {@link Model::orLike()} 类似。
	 * 
	 * @param string $field
	 * @param string $match
	 * @param string $side
	 */
	public function orNotLike($field, $match = '', $side = 'both') {
		$this->currentDb->or_not_like($field, $match, $side);
		return $this;
	}

	/**
	 * 构造 GROUP BY 语句
	 * 
	 * 可以直接写 SQL 语句中的 GROUP BY 部分， 如果有多个，直接使用逗号（,）分隔
	 * 
	 * @param string $by
	 */
	public function groupBy($by) {
		$this->currentDb->group_by($by);
		return $this;
	}

	/**
	 * 构造 HAVING 语句
	 * 
	 * 跟 {@link Model::where()} 语句相似，用于 GROUP BY 语句执行之后。支持的
	 * 特性有：
	 * 1. 简单的名值对
	 * 2. 传入一个数组包含多个条件
	 * 
	 * @param string | array $by
	 * @param string $value
	 */
	public function having($key, $value = '', $escape = TRUE) {
		$this->currentDb->having($key, $value, $escape);
		return $this;
	}

	/**
	 * 构造 HAVING .. OR .. 语句
	 * 
	 * 跟 {@link Model::having()} 语句类似.
	 * 
	 * @param string | array $key
	 * @param string $value
	 * @param boolean $escape
	 */
	public function orHaving($key, $value = '', $escape = TRUE) {
		$this->currentDb->or_having($key, $value, $escape);
		return $this;
	}

	/**
	 * 生成 ORDER BY 语句
	 * 
	 * 如果有多个，则直接写SQL语句中 ORDER BY 部分，用逗号（,）隔开即可。
	 * 
	 * @param string $orderby
	 * @param string $direction ASC | ESC | RANDOM
	 */
	public function orderBy($orderby, $direction = '') {
		$this->currentDb->order_by($orderby, $direction);
		return $this;
	}

	/**
	 * 查询记录总条数，一般用于分页程序
	 * 
	 * 生成 select count(*) num 语句，但会忽略 select, limit, order by,
	 * group by, having这方面的设置 
	 * @param string $table 表名，默认为当前表
	 */
	public function countAllResults($table = '') {
		if (!$table) {
			$table = $this->fromTable;
		}
		if (!$table) {
			$table = $this->defaultTable;
		}

		$rows = $this->currentDb->count_all_results($table);
		$this->fromTable = '';
		return $rows;
	}

	/**
	 * 按照条件查询结果集
	 * 
	 * 本函数相当于
	 * <code>
	 * $this->where($where);
	 * return $this->get($limit, $offset);
	 * </code>
	 * @param string $where
	 * @param integer $limit
	 * @param integer $offset
	 */
	public function getWhere($where = null, $limit = null, $offset = null) {
		if (!$this->fromTable) {
			$this->fromTable = $this->defaultTable;
		}
		$res = $this->currentDb->getWhere($this->fromTable, $where, $limit, $offset);
		$this->fromTable = '';
		return $res;
	}

	/**
	 * 清空表
	 * 
	 * 生成的SQL语句类似于:
	 * TRUNCATE TABLE `$table`
	 * 
	 * @param string $table
	 */
	public function emptyTable($table = '') {
		return $this->currentDb->empty_table($table);
	}

	/**
	 * 对数据集合赋值
	 * 
	 * @param string $key
	 * @param string $value
	 * @param boolean $escape 此参数如果为 False，则不会将 $value 两边加单引号，里面的内容也不会被转义
	 */
	public function set($key, $value = '', $escape = TRUE) {
		$this->currentDb->set($key, $value, $escape);
		return $this;
	}
	
	/**
	 * 插入数据
	 * 
	 * 生成 INSERT 语句
	 * @param array | object $set
	 */
	public function insert($set = null) {
		if (!$this->fromTable) {
			$this->fromTable = $this->defaultTable;
		}
		$res = $this->currentDb->insert($this->fromTable, $set);
		$this->fromTable = '';
		return $res;
	}

	/**
	 * 更新数据
	 * 
	 * 生成 UPDATE 语句，数据集、where语句、limit都可以提前设置，此处传递为空，或者以相应的形式传入
	 * @param array | object $set
	 * @param array | string $where
	 * @param integer $limit
	 */
	public function update($set = NULL, $where = NULL, $limit = NULL) {
		if (!$this->fromTable) {
			$this->fromTable = $this->defaultTable;
		}
		
		$res = $this->currentDb->update($this->fromTable, $set, $where, $limit);
		$this->fromTable = '';
		return $res;
	}

	/**
	 * 删除数据
	 * 
	 * 生成 DELETE 语句，where语句、limit都可以提前设置，此处传递为空，或者以相应的形式传入
	 * @param array | string $where
	 * @param integer $limit
	 * @param boolean $reset_data 是否重置条件
	 */
	public function delete($where = '', $limit = NULL, $reset_data = TRUE) {
		if (!$this->fromTable) {
			$this->fromTable = $this->defaultTable;
		}
		
		$res = $this->currentDb->delete($this->fromTable, $where, $limit, $reset_data);
		$this->fromTable = '';
		return $res;
	}
	
	/**
	 * 保持 where 语句，在执行查询时不被清除
	 * 
	 * @param integer $keep 保持的次数，目前限制为 1
	 */
	public function keepWhere($keep = 1) {
		return $this->currentDb->keep_where($keep);
	}
	
	/**
	 * 上次查询语句
	 * 
	 * @return string
	 */
	public function lastQuery() {
		return $this->currentDb->last_query();
	}
	
	/**
	 * 总查询数据库次数
	 * 
	 * @return integer
	 */
	public function queryCount() {
		return $this->currentDb->total_queries();
	}
	
	/**
	 * 取得所有的查询语句
	 * 
	 * @return array
	 */
	public function getAllQueries() {
		return $this->currentDb->all_queries();
	}
}
// END Model Class

/* End of file Model.php */
/* Location: ./system/core/Model.php */