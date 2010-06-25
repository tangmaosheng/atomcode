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
 * @version 1.0 2010-6-7
 * @filesource 
 */
abstract class DbModel extends Model
{
	var $ins_data;
	var $dbs;
	var $db;
	var $table;
	var $in_var;
	var $tpl;
	var $upload;
	var $query;
	var $order;
	var $orders;
	var $limit;
	var $pageSize;
	var $db_insert_id;
	var $page;
	var $where = array();
	var $join = array();
	var $from = array();
	var $groupby  = array();
	var $having = array();
	var $prefix = '';
	var $lastId;
	
	protected $get;
	protected $post;
	protected $input;
	protected $cookie;
	protected $session;
	protected $request_method;
	protected $config;
	protected $defaultDb;

	/**
	 * 构造函数
	 * @global $this->db 数据库类
	 *
	 * @return Null
	 */
	public function __construct()
	{
		parent::__construct();

		$this->dbs			= Db::getInstance();
		
		if ($this->config['DB']['DEPLOY_TYPE'] && $this->config['DB']['DEFAULT'])
		{
			$this->defaultDb	= $this->config['DB']['DEFAULT'];
			$this->switchDb($this->defaultDb);
		}
		elseif($this->config['DB']['DEPLOY_TYPE'])
		{
			$this->defaultDb	 = key($this->dbs);
			$this->switchDb($this->defaultDb);
		}
		else
		{
			$this->db		=& $this->dbs;
			$this->prefix	= $var->config['DB']['PREFIX'];
		}
		
		$this->lastId		= &$this->db_insert_id;
	}
	
	/**
	 * 选择数据库
	 * @param $db
	 * @return unknown_type
	 */
	public function switchDb($db)
	{
		if ($this->config['DB']['DEPLOY_TYPE'])
		{
			$this->db		= & $this->dbs[$db];
			$this->prefix	= $this->config['DB'][$db]['PREFIX'];
		}
	}
	
	/**
	 * 添加处理数据
	 *
	 * @param string $var   变量名
	 * @param steing $value 值
	 * @param int    $modified  = 1 : 为添加修饰符
	 */
	public function set($var,$value = null,$modified = 1)
	{
		if (is_array($var))
        {
            foreach ($var as $key => $val)
            {
                if ($key != '')
                {
                    $this->ins_data[$key] = $val;
                    $this->modified[$key] = $modified;
                }
            }
        }
        else
        {
                $this->ins_data[$var] = $value;
                $this->modified[$var] = $modified;
        }
	}

	/**
	 * 清空数据
	 *
	 */
	public function cleanData()
	{
		unset($this->ins_data);
	}

	/**
	 * 清空条件
	 *
	 */
	public function cleanConditions()
	{
		unset($this->where);
		unset($this->conditions);
		unset($this->from);
		unset($this->join);
		unset($this->join_table);
		unset($this->groupby);
		unset($this->groupby_where);
		unset($this->having);
		unset($this->having_where);
		unset($this->order);
		unset($this->orders);
		unset($this->limit);
	}
	
	public function ensql($data)
	{
		return mysql_escape_string($data);
	}

	/**
	 * 插入数据库数据
	 *
	 * @param string $table
	 * @return boolean
	 */
	public function insert($table)
	{
		//构建SQL语句
		$ins_data_num = count($this->ins_data);
		$foreach_i = 0;
		$query = "Insert into " . $this->getTable($table) . " \n(\n";
		$query_key = "";
		$query_val = "";

		foreach($this->ins_data as $key=>$val)
		{
			if($foreach_i == 0)
			{
				$query_key .= $key;

				if($this->modified[$key])
				{
					$query_val .= "'" . $this->ensql($val) . "'";
				}
				else
				{
					$query_val .= $this->ensql($val);
				}
			}
			else
			{
				$query_key .= ",\n{$key}";

				if($this->modified[$key])
				{
					$query_val .= ",\n'" . $this->ensql($val) . "'";
				}
				else
				{
					$query_val .= ",\n" . $this->ensql($val);
				}
			}

			$foreach_i = $foreach_i + 1;
		}

		$query .= $query_key . "\n) \nValues \n(\n" . $query_val . "\n)";

		$this->cleanData();
		$this->query = $query;

		//SQL语句执行
		//echo $query;
		if($result = $this->db->Execute($query))
		{
			$this->db_insert_id = $this->db->getInsertID();
			return true;
		}
		else
		{
			return false;
		}
	
	}

	/**
	 * 添加条件语句
	 *
	 * @param string $field  表字段
	 * @param string $value	 值
	 * @param string $terms  条件
	 * @param string $type  条件逻辑运算符
	 * @param string $modify  条件修饰符
	 */
	public function where($field,$value,$terms = "=",$type = "and",$modify = 1)
	{
		$prefix = (count($this->where) == 0) ? '' : $type . ' ';

		if(strtolower($terms) == 'in' || strtolower($terms) == 'not in')
		{
			$this->where[] = $prefix . $field . ' ' . $terms . ' (' . $value . ')';
		}
		elseif(strtolower($terms) == 'like')
		{
			$this->where[] = $prefix . $field . ' ' . $terms . " '" . $value . "'" ;
		}
		else
		{
			if($modify)
			{
				$value = "'" . $value . "'";
			}

			$this->where[] = $prefix . $field . ' ' . $terms . ' ' . $value;
		}

		$this->conditions = implode(" ",$this->where);
	}
	
	/**
	 * 添加复杂的where语句
	 * @param $where
	 * @return unknown_type
	 */
	public function addWhere($where)
	{
		$this->where[] = $where;
	}

	/**
	 * 得到表名
	 *
	 */
	public function getTable($table)
	{
		$ts = explode(' ',$table);
		$ts[0] = $this->prefix . $ts[0];
		
		return trim("`$ts[0]` $ts[1]");
	}

	/**
	 * 设置多表查询
	 *
	 * @param string $table
	 */
	public function from($table)
	{
		$this->from[] = $this->getTable(trim($table));

		$this->from_table = implode(",",$this->from);
	}




	/**
	 * join查询
	 *
	 * @param string $table
	 * @param string $cond
	 * @param string $type
	 */
	public function join($table, $cond, $type = '')
	{
		$table = $this->getTable($table);
		
		if ($type != '')
		{
			$type = strtoupper(trim($type));

			if ( ! in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER')))
			{
				$type = '';
			}
			else
			{
				$type .= ' ';
			}
		}

		// Strip apart the condition and protect the identifiers
		if (preg_match('/([\w\.]+)([\W\s]+)(.+)/', $cond, $match))
		{
			$cond = $match[1] . $match[2] . $match[3];
		}

		$this->join[] = $type . 'JOIN ' . $table . ' ON ' . $cond;

		$this->join_table = " " . implode(" ",$this->join);
	}
	
	/**
	 * 左连接
	 * @param $table
	 * @param $cond
	 * @return unknown_type
	 */
	public function leftJoin($table,$cond)
	{
		$this->join($table,$cond,'left');
	}
	
	/**
	 * 右连接
	 * @param $table
	 * @param $cond
	 * @return unknown_type
	 */
	public function rightJoin($table,$cond)
	{
		$this->join($table,$cond,'right');
	}
	
	/**
	 * 内连接
	 * @param $table
	 * @param $cond
	 * @return unknown_type
	 */
	public function innerJoin($table,$cond)
	{
		$this->join($table,$cond,'inner');
	}

	/**
	 * 添加groupBy 条件
	 *
	 * @param string $field
	 */
	public function groupBy($field)
	{
		$this->groupby[] = $field;

		$this->groupby_where = " GROUP BY " . implode(",",$this->groupby) . ' ';
	}


	/**
	 * 添加having 条件
	 *
	 * @param string $field
	 */
	public function having($field,$value,$terms = '=', $type = 'and')
	{
		$prefix = (count($this->having) == 0) ? '' : $type . ' ';

		if (is_null($value))
		{
			$value = ' IS NULL';
		}

		if(!is_integer($value))
		{
			$value = "'" . $value . "'";
		}


		if(strtolower($terms) == 'in' || strtolower($terms) == 'not in')
		{
			$this->having[] = $prefix . $field . ' ' . $terms . ' (' . $value . ')';
		}
		else
		{
			$this->having[] = $prefix . $field . ' ' . $terms . ' ' . $value;
		}

		$this->having_where = " HAVING " . implode(" ",$this->having) . ' ';
	}

	/**
	 * 设置记录上限
	 *
	 * @param int $limit
	 */
	public function limit($limit)
	{
		if ($limit > 0)
		{
			$this->limit = $limit;
			$this->pageSize = $limit;
		}
	}

	/**
	 * 设置排序
	 *
	 * @param string $order
	 */
	public function orderBy($order = 'ID desc')
	{
		$orders = explode(' ',$order);
		$this->order[$orders[0]] = $order; 
		
		$this->orders = ' order by ' . implode(',',$this->order);
	}

	/**
	 * 设置取得的页数
	 *
	 * @param int $page
	 */
	public function page($page)
	{
		$this->page = $page;
	}

	/**
	 * 更新数据库数据
	 *
	 * @param string $table 表名
	 * @return boolean
	 */
	public function update($table)
	{
		try
		{
			//构建SQL语句
     		$foreach_i = 0;
     		$query     = "update " . $this->getTable($table) . " set ";
     		$query_key = "";
     		$query_val = "";
     		$where = $this->conditions ? ' where ' . $this->conditions : '';

	     	foreach($this->ins_data as $key=>$val)
			{
	  				if($foreach_i == 0)
					{
	     				$query_key = "{$key}";

	     				if($this->modified[$key])
						{
							$query_val = "='" . $this->ensql($val) . "'";
						}
						else
						{
							$query_val = "=" .$this->ensql($val);
						}

						$query .= $query_key . $query_val;
					}
					else
					{
         				$query_key = ",{$key}";

         				if($this->modified[$key])
						{
							$query_val = "='" .  $this->ensql($val) . "'";
						}
						else
						{
							$query_val = "=" . $this->ensql($val);
						}

						$query .= $query_key . $query_val;
   					}

	 				$foreach_i = $foreach_i + 1;
 			}

			$query .= " $where";

			$this->cleanConditions();
			$this->cleanData();
			//echo $query;exit;
			//SQL语句执行
     		if($this->db->query($query))
			{
     			return true;
	        }
	 		else
 			{
     			return false;
			}
		}
		catch (MySqlException $e)
		{
			$e->showError();
		}
	}

	/**
	 * 删除数据
	 *
	 * @param string $table
	 * @return boolean
	 */
	public function delete($table)
	{
		$where = $this->conditions ? ' where ' . $this->conditions : '';

		/**Modified by jefurry*/
		if(isset($this->orders))
		{
			$order = ' ' . $this->orders;
		}

		if(isset($this->page) && isset($this->limit))
		{
			$offset = ($this->page - 1) * $this->limit;
			$limit = ' limit ' . $this->limit;

			$this->page_limit = $limit;
		}
		//构建SQL语句
     	$query = "Delete From " . $this->getTable($table) . $where
     	. $order
     	. $this->page_limit;
     	
     	$this->cleanConditions();

     	if($this->db->Execute($query))
		{
     		return true;
        }
 		else
 		{
     		return false;
		}
	}

	/**
	 * 取得记录数量
	 *
	 * @param string $table  传入的数据表
	 * @return int   传回记录条数
	 */
	public function count($table)
	{
		$this->from($table);

		$where = $this->conditions ? ' where ' . $this->conditions : '';
		$this->cleanConditions();

		$sql = "select count(*) as Num from " . $this->from_table . $where;
		$result = $this->db->getRow($sql);
    	return $result['Num'];
	}

	/**
	 * 得到一组数据
	 *
	 * @param string $table  数据表名
	 * @param string $where  查询条件
	 * @param string $select  查询字段
	 * @param string $sql  自定义sql语句,当$sql存在是,前面参数无效
	 * @return array      得到一条记录
	 */
	public function getOne($table,$select = '*')
	{
		$this->from($table);

		$where = $this->conditions ? ' where ' . $this->conditions : '';

		try
		{
			$sql = "select " . $select ." from "
			. $this->from_table
			. $this->join_table
			. $where
			. $this->groupby_where
			. $this->having_where
			. $this->orders;

			$this->cleanConditions();

			$result = $this->db->getRow($sql);

			return $result;
		}
		catch (MySqlException $e)
		{
			$e->showError();
		}
	}

	/**
	 * 得到数据列表
	 *
	 * @param string $table  数据表名
	 * @param string $select  查询字段
	 * @return array   返回数据记录列表
	 */
	public function select($table,$select = '*')
	{
		$this->from($table);

		$where = $this->conditions ? ' where ' . $this->conditions : '';

		if(isset($this->orders))
		{
			$order = ' ' . $this->orders;
		}

		if(isset($this->page) && isset($this->limit))
		{
			$offset = ($this->page - 1) * $this->limit;
			$limit = ' limit ' . $this->limit . ' offset ' . $offset;

			$this->page_limit = $this->limit;
		}

		$this->query = "select " . $select . " from " . $this->from_table
		. $this->join_table
		. $where
		. $this->groupby_where
		. $this->having_where
		. $order
		. $limit;

		$this->cleanConditions();
		$result = $this->db->Execute($this->query);

		while(!$result->EOF)
		{
			$list[] = $result->fields;
			$result->moveNext();
		}

		return $list;
	}

	/**
	 * 自定义查询
	 *
	 * @param string $sql 查询语句
	 * @param int $type 返回类型 1=多条记录，2=单条记录
	 * @return array
	 */
	public function query($sql,$type = 1)
	{
		$this->query = $sql;

		if($type == 1)
		{
			$result = $this->db->Execute($this->query);

			while(!$result->EOF)
			{
				$list[] = $result->fields;
				$result->moveNext();
			}

			return $list;
		}
		else
		{
			$result = $this->db->getRow($this->query);

			return $result;
		}
	}

	/**
	 * 得到上一个多记录查询语句的总记录数和总页数
	 *
	 * @param string $url  分页链接地址
	 */
	public function getPageInfo()
	{
		if ($this->query)
		{
			$pattern 			= '/select (.*?) from (.*)limit(.*)/i';
			$replacement 		= 'select count(*) as num from $2';
			$count_query 		= preg_replace($pattern, $replacement, $this->query);

			$result = $this->db->getRow($count_query);

			$page_info['result_count']	= $result['num'];
			$page_info['page_count']	= ceil($result['num'] / $this->pageSize);
			$page_info['current_page']	= $this->page;
			
			unset($this->pageSize);
			unset($this->page);

			return $page_info;
		}
		else
		{
			return false;
		}
	}
}