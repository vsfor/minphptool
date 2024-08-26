<?php
namespace mt\core\db;

use mt\core\lib\JArray;
use mt\core\lib\JConfig;
use mt\core\lib\JLog;

/**
 * 数据模型基类|查询构造类
 * 注意：
 *    请提前过滤风险参数；
 *    同一个查询实例默认不可多次获取查询结果 @see self::flushQuery()
 *
 * 不建议使用关联查询
 * 不建议使用事务
 *
 * Class BaseModel
 * @package common\core\mysqldb
 */
class MySQLQuery
{
    protected $select='*';
    protected $where='';
    protected $params=[]; //以 :param 占位符为标准   统一禁用 ? 占位符
    protected $limit=0;
    protected $offset=0;
    protected $order='';
    protected $group='';
    protected $distinct=false;

    protected $sql='';

    protected $_dbName = null;
    protected $_tbName = null;

    public static function find(string $table,string $db='db'):MySQLQuery
    {
        return new self($table,$db);
    }

    /**
     * 不推荐 new , 推荐使用 find 方法替代
     * @param string $table tableName
     * @param string $db connConfigName
     */
    private function __construct(string $table,string $db = 'db')
    {
        $this->setTable($table);
        $this->setDb($db);
    }
    private function __clone() {}

    /**
     * 增加单条记录
     * @param array $data  map: col => val
     * @param bool $duplicateIgnore  冲突时忽略
     * @return bool|string
     * @throws \Exception|\PDOException
     */
    public function insert(array $data,bool $duplicateIgnore = false)
    {
        $cols = array_keys($data);
        $sql = ($duplicateIgnore ? "INSERT IGNORE INTO " : "INSERT INTO ")
            . $this->tableName()
            . " (`" . implode("`, `", $cols) . "`) "
            . "VALUES (:" . implode(", :", $cols) . ") ";
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        foreach ($data as $k => $v) {
            if ($v === false) {
                $v = 0;
            } elseif ($v === null) {
                $v = 'NULL';
            }
            $stmt->bindValue(":{$k}", $v, $this->dataType($v));
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if ($res) {
            return $this->getConn()->lastInsertId();
        }
        return false;
    }

    /**
     * 增加多条记录
     * @param array $columns
     * @param array $rows
     * @return bool|int
     */
    public function batchInsert(array $columns,array $rows)
    {
        $values = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $vs = [];
                foreach ($row as $i => $value) {
                    if (is_string($value)) {
                        $value = $this->quoteValue($value);
                    } elseif ($value === false) {
                        $value = 0;
                    } elseif ($value === null) {
                        $value = 'NULL';
                    }
                    $vs[] = $value;
                }
            } elseif (is_string($row)) {
                $value = $this->quoteValue($row);
                $vs = [$value];
            } elseif (is_numeric($row)) {
                $vs = [$row];
            } elseif ($value === false) {
                $vs = [0];
            } elseif ($value === null) {
                $vs = ['NULL'];
            } else {
                throw new \Exception('unknown value type for:'.jsonEncode($row));
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }
        $sql = "INSERT INTO "
            . $this->tableName()
            . " (`" . implode("`, `", $columns) . "`) "
            . "VALUES " . implode(', ', $values);
        unset($columns);
        unset($values);
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        unset($sql);
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if ($res) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * 增加多条记录,存在冲突则跳过
     * @param array $columns
     * @param array $rows
     * @return bool|int
     */
    public function batchInsertOrIgnore(array $columns,array $rows)
    {
        $values = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $vs = [];
                foreach ($row as $i => $value) {
                    if (is_string($value)) {
                        $value = $this->quoteValue($value);
                    } elseif ($value === false) {
                        $value = 0;
                    } elseif ($value === null) {
                        $value = 'NULL';
                    }
                    $vs[] = $value;
                }
            } elseif (is_string($row)) {
                $value = $this->quoteValue($row);
                $vs = [$value];
            } elseif (is_numeric($row)) {
                $vs = [$row];
            } elseif ($value === false) {
                $vs = [0];
            } elseif ($value === null) {
                $vs = ['NULL'];
            } else {
                throw new \Exception('unknown value type for:'.jsonEncode($row));
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }
        $sql = "INSERT IGNORE INTO "
            . $this->tableName()
            . " (`" . implode("`, `", $columns) . "`) "
            . "VALUES " . implode(', ', $values);
        unset($columns);
        unset($values);
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        unset($sql);
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if ($res) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * 增加多条记录,存在冲突则更新 - 慎用
     * @param array $columns
     * @param array $rows
     * @return bool|int
     */
    public function batchInsertOrUpdate(array $columns,array $rows, string $update='`id`=`id`')
    {
        $values = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $vs = [];
                foreach ($row as $i => $value) {
                    if (is_string($value)) {
                        $value = $this->quoteValue($value);
                    } elseif ($value === false) {
                        $value = 0;
                    } elseif ($value === null) {
                        $value = 'NULL';
                    }
                    $vs[] = $value;
                }
            } elseif (is_string($row)) {
                $value = $this->quoteValue($row);
                $vs = [$value];
            } elseif (is_numeric($row)) {
                $vs = [$row];
            } elseif ($value === false) {
                $vs = [0];
            } elseif ($value === null) {
                $vs = ['NULL'];
            } else {
                throw new \Exception('unknown value type for:'.jsonEncode($row));
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }
        $sql = "INSERT IGNORE INTO "
            . $this->tableName()
            . " (`" . implode("`, `", $columns) . "`) "
            . "VALUES " . implode(', ', $values)
            . " ON DUPLICATE KEY UPDATE "
            . $update;
        unset($columns);
        unset($values);
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        unset($sql);
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if ($res) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * 删除记录
     * 不推荐直接删除数据
     *
     * @param string|array $condition
     * @param array $params
     * @return bool|int
     */
    public function delete($condition, $params = [])
    {
        if (is_array($condition)) {
            $temp = $this->switchCondition($condition);
            $condition = $temp['sql'];
            $params = array_merge($temp['params'], $params);
        }
        $sql = "DELETE FROM "
            . $this->tableName()
            . " WHERE $condition";
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->datatype($v));
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if ($res) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * 更新记录
     * @param array $data
     * @param string|array $condition  不可包含 ? 占位符
     * @param array $params  必须使用 :param 占位符
     * @return bool|int
     * @throws \Exception
     */
    public function update(array $data, $condition = '', $params = [])
    {
        $sets = [];
        $vals = [];
        foreach ($data as $col=>$val) {
            $sets[] = "`$col` = :new_{$col}_val";
            $vals[":new_{$col}_val"] = $val;
        }
        if (empty($sets)) return 0;

        if (is_array($condition)) {
            $temp = $this->switchCondition($condition);
            $condition = $temp['sql'];
            $params = array_merge($temp['params'], $params);
        }
        $sql = "UPDATE "
            . $this->tableName()
            . " SET " . implode(", ", $sets)
            . (($condition) ? " WHERE $condition" : "");
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;

        foreach ($vals as $k => $v) {
            $stmt->bindValue($k, $v, $this->datatype($v));
        }
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->datatype($v));
        }

        $res = $stmt->execute();
        if ($res) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * 更新记录统计数量
     * @param array $data
     * @param string|array $condition  不可包含 ? 占位符
     * @param array $params  必须使用 :param 占位符
     * @return bool|int
     */
    public function updateCounters(array $data, $condition = '', $params = [])
    {
        $sets = [];
        foreach ($data as $col=>$val) {
            $val = intval($val);
            if ($val != 0) {
                $sets[] = "`$col` = (`$col`+($val))";
            }
        }
        if (empty($sets)) return 0;

        if (is_array($condition)) {
            $temp = $this->switchCondition($condition);
            $condition = $temp['sql'];
            $params = array_merge($temp['params'], $params);
        }
        $sql = "UPDATE "
            . $this->tableName()
            . " SET " . implode(", ", $sets)
            . (($condition) ? " WHERE $condition" : "");

        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->datatype($v));
        }

        $res = $stmt->execute();
        if ($res) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * 查询单行数据
     *
     * @param bool|mixed $flush  flush params after query execute
     * @return bool|mixed
     */
    public function one($flush = true)
    {
        $this->limit(1);
        $sql = $this->getSql();
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        foreach ($this->params as $k => $v) {
            $stmt->bindValue($k, $v, $this->dataType($v));
        }
        if ($flush) {
            $this->flushQuery();
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if($res) {
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }
        return false;

    }

    /**
     * 查询多行数据
     *
     * @param bool|mixed $flush  flush params after query execute
     * @return array|bool
     */
    public function all($flush = true)
    {
        $sql = $this->getSql();
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        foreach ($this->params as $k => $v) {
            $stmt->bindValue($k, $v, $this->dataType($v));
        }
        if ($flush) {
            $this->flushQuery();
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if($res) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * 查询单列数据
     *
     * @param bool|mixed $flush  flush params after query execute
     * @return array|bool
     */
    public function column($flush = true)
    {
        $sql = $this->getSql();
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        foreach ($this->params as $k => $v) {
            $stmt->bindValue($k, $v, $this->dataType($v));
        }
        if ($flush) {
            $this->flushQuery();
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if($res) {
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        return false;
    }

    /**
     * 查询数量
     *
     * @param string $q
     * @param bool|mixed $flush  flush params after query execute
     * @return bool|int|mixed
     */
    public function count($q='*', $flush = false)
    {
        $sql =  "SELECT COUNT(";
        if($this->distinct) {
            $sql .= " DISTINCT ";
        }
        $sql .= $q .") FROM ".$this->tableName();
        if($this->where) {
            $sql .= " WHERE {$this->where}";
        }
        if($this->group) {
            $sql .= " GROUP BY {$this->group}";
        }

        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        foreach ($this->params as $k => $v) {
            $stmt->bindValue($k, $v, $this->dataType($v));
        }
        if ($flush) {
            $this->flushQuery();
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if($res) {
            return $stmt->fetchColumn(0);
        }
        return 0;
    }

    /**
     * 获取第一行第一列的数据
     *
     * @param bool|mixed $flush  flush params after query execute
     * @return bool|mixed
     */
    public function scalar($flush = true)
    {
        $this->limit(1);
        $sql = $this->getSql();
        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        foreach ($this->params as $k => $v) {
            $stmt->bindValue($k, $v, $this->dataType($v));
        }
        if ($flush) {
            $this->flushQuery();
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if ($res) {
            return $stmt->fetchColumn(0);
        }
        return false;
    }

    /**
     * 设置查询字段
     * @param array $attr
     * @param bool $link  使用'`'连接字段
     * @return $this
     */
    public function select(array $attr, $link = true)
    {
        if (empty($attr)) {
            $this->select = '*';
        } elseif ($link) {
            $this->select = '`' . implode('`,`', $attr) . '`';
        } else {
            $this->select = implode(',', $attr);
        }
        $this->sql = '';
        return $this;
    }

    /**
     * 设置是否排重
     * @param bool $value
     * @return $this
     */
    public function distinct($value=true)
    {
        $this->distinct = boolval($value);
        $this->sql = '';
        return $this;
    }

    /**
     * 设置查询数量
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = intval($limit);
        $this->sql = '';
        return $this;
    }

    /**
     * 设置分页数量
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = intval($offset);
        $this->sql = '';
        return $this;
    }

    /**
     * 设置查询添加
     * @param string|array $condition
     * @param array $params
     * @return $this
     */
    public function where($condition,$params=[])
    {
        $this->params = [];
        if (is_array($condition)) {
            $temp = $this->switchCondition($condition);
            $condition = $temp['sql'];
            $params = array_merge($temp['params'], $params);
        }
        $this->where = $condition;
        $this->addParams($params);
        $this->sql = '';
        return $this;
    }

    /**
     * 添加查询条件 and
     * @param string|array $condition
     * @param array $params
     * @return $this
     */
    public function andWhere($condition,$params=[])
    {
        if (empty($condition)) {
            return $this;
        }
        if (is_array($condition)) {
            $suffix = count($this->params);
            $temp = $this->switchCondition($condition,'and', "_{$suffix}");
            $condition = $temp['sql'];
            $params = array_merge($temp['params'], $params);
        }
        if($this->where == '') {
            $this->where = $condition;
        } else {
            $this->where = "({$this->where}) AND ($condition)";
        }
        $this->addParams($params);
        $this->sql = '';
        return $this;
    }

    /**
     * 添加查询条件 or
     * @param string|array $condition
     * @param array $params
     * @return $this
     */
    public function orWhere($condition,$params=[])
    {
        if (empty($condition)) {
            return $this;
        }
        if (is_array($condition)) {
            $suffix = count($this->params);
            $temp = $this->switchCondition($condition, 'and',"_{$suffix}");
            $condition = $temp['sql'];
            $params = array_merge($temp['params'], $params);
        }
        if($this->where == '') {
            $this->where = $condition;
        } else {
            $this->where = "({$this->where}) OR ($condition)";
        }
        $this->addParams($params);
        $this->sql = '';
        return $this;
    }

    /**
     * 设置排序
     * @param string $orderBy eg: `id` desc
     * @return $this
     */
    public function orderBy($orderBy)
    {
        if(is_string($orderBy)) {
            $this->order = $orderBy;
            $this->sql = '';
        }
        return $this;
    }

    /**
     * 设置分组
     * @param string|array $groupBy
     * @return $this
     */
    public function groupBy($groupBy)
    {
        if(is_string($groupBy)) {
            $this->group = "$groupBy";
        } else if(is_array($groupBy)) {
            $this->group = '`'.implode('`,`',$groupBy).'`';
        } else {
            $this->group = '';
        }
        $this->sql = '';
        return $this;
    }

    /**
     * 添加参数
     * @param array $params
     * @return $this
     */
    protected function addParams($params)
    {
        if (!empty($params)) {
            if (empty($this->params)) {
                $this->params = $params;
            } else {
                foreach ($params as $name => $value) {
                    if (is_int($name)) {
                        $this->params[] = $value;
                    } else {
                        $this->params[$name] = $value;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * 获取查询语句 (不含参数值)
     * @return string
     */
    public function getSql()
    {
        if($this->sql) return $this->sql;
        $this->sql .=  "SELECT ";
        if($this->distinct) {
            $this->sql .= " DISTINCT ";
        }
        $this->sql .= $this->select ." FROM ".$this->tableName();
        if($this->where) {
            $this->sql .= " WHERE {$this->where}";
        }
        if($this->group) {
            $this->sql .= " GROUP BY {$this->group}";
        }
        if($this->order) {
            $this->sql .= " ORDER BY {$this->order}";
        }
        if($this->limit) {
            $this->sql .= " LIMIT ".$this->limit;
        }
        if($this->offset) {
            $this->sql .= $this->limit ? " OFFSET ".$this->offset : " LIMIT {$this->offset},2147483647";
        }
        return $this->sql;
    }

    /**
     * 特殊字符转义
     * @param mixed $str
     * @return string
     */
    protected function quoteValue($str)
    {
        $str = str_replace("\\","\\\\",$str);
        $str = str_replace("\"","\\\"",$str);
        return "'".str_replace("'","\\'",$str)."'";
    }

    /**
     * 参数类型判定
     * @param mixed $param
     * @return int
     */
    protected function dataType($param)
    {
        if (is_bool($param)) {
            return \PDO::PARAM_BOOL;
        } else if (is_int($param)) {
            return \PDO::PARAM_INT;
        } else if (is_null($param)) {
            return \PDO::PARAM_NULL;
        } else {
            return \PDO::PARAM_STR;
        }
    }

    /**
     * 获取插入数据ID
     * @return string
     */
    protected function getLastInsertId()
    {
        return $this->getConn()->lastInsertId();
    }

    /**
     * 开启事务
     * @deprecated 不推荐使用事务
     * @return bool
     */
    protected function beginTransaction()
    {
        return $this->getConn()->beginTransaction();
    }

    /**
     * 事务提交
     * @deprecated 不推荐使用事务
     * @return bool
     */
    protected function commit()
    {
        return $this->getConn()->commit();
    }

    /**
     * 事务回滚
     * @deprecated 不推荐使用事务
     * @return bool
     */
    protected function rollBack()
    {
        return $this->getConn()->rollBack();
    }

    /**
     * 获取查询语句 - 包含参数值，调试用
     * @return string
     */
    public function getRawSql()
    {
        if(!$this->sql) $this->sql = $this->getSql();
        if(empty($this->params)) {
            return $this->sql;
        }
        $params = [];
        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }
            if (is_string($value)) {
                $params[$name] = $this->quoteValue($value);
            } elseif (is_bool($value)) {
                $params[$name] = ($value ? 'TRUE' : 'FALSE');
            } elseif ($value === null) {
                $params[$name] = 'NULL';
            } elseif (!is_object($value) && !is_resource($value)) {
                $params[$name] = $value;
            }
        }
        if (!isset($params[1])) {
            return strtr($this->sql, $params);
        }
        $sql = '';
        foreach (explode('?', $this->sql) as $i => $part) {
            $sql .= (isset($params[$i]) ? $params[$i] : '') . $part;
        }

        return $sql;
    }

    /**
     * 执行SQL语句
     * @param $sql
     * @return int
     */
    public function exec($sql)
    {
        return $this->getConn()->exec($sql);
    }

    /**
     * 执行SQL语句
     * @param $sql
     * @param array $params
     * @return bool
     */
    public function execute($sql, $params=[])
    {
        return $this->getStmt($sql)->execute($params);
    }

    /**
     * 执行sql查询，并返回查询结果
     * @param $sql
     * @param array $params
     * @return array|bool
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->getStmt($sql);
        if (!$stmt) {
            return false;
        }
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $this->dataType($v));
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if ($res) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * 获取数据库连接
     * @param bool $refresh
     * @return \PDO
     */
    private function getConn($refresh = false)
    {
        if (empty($this->conn) || $refresh) {
            $config = JConfig::getEnv('mysql', $this->dbName());
            $options = [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
            ];
            $this->conn = new \PDO($config['dsn'], $config['username'], $config['password'], $options);
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
            $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(\PDO::ATTR_PERSISTENT,true);
            //解决"this is incompatible with sql_mode=only_full_group_by:42000"查询模式问题
            $this->conn->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
        }
        return $this->conn;
    }
    /** @var \PDO */
    private $conn;

    /**
     * 预处理sql语句
     * @param string $sql
     * @return bool|\PDOStatement
     */
    private function getStmt($sql)
    {
        try {
            $stmt = $this->getConn()->prepare($sql);
        } catch (\Exception $e) {
            try {
                JLog::exception($e, [
                    'msg' => 'getStmtException:'.$e->getMessage().':'.$e->getCode(),
                    'sql' => $sql,
                ],'mysql');
                $msg = strtolower($e->getMessage());
                if (strpos($msg, 'server has gone away')) {
//PDO::prepare(): MySQL server has gone away
                    $stmt = $this->getConn(true)->prepare($sql);
                } elseif (strpos($msg, 'connection timed out')) {
//PDO::prepare(): send of *** bytes failed with errno=110 Connection timed out
                    $stmt = $this->getConn(true)->prepare($sql);
                } else {
                    $stmt = false;
                }
            } catch (\Exception $e) {
                JLog::exception($e, [
                    'msg' => 'getStmtRetryException:'.$e->getMessage().':'.$e->getCode(),
                    'sql' => $sql,
                    'fl' => $e->getFile().':'.$e->getLine(),
                ],'mysql');
                $stmt = false;
            }
        }
        return $stmt;
    }

    /**
     * 数据库名 - 连接配置名称 非实际数据库名
     * @return null|string
     */
    protected function dbName()
    {
        return $this->_dbName ?? 'db';
    }

    /**
     * 设置数据连接名称
     *
     * @param string $name
     * @return $this
     */
    public function setDb(string $name)
    {
        $this->_dbName = $name;
        return $this;
    }

    /**
     * 数据表名
     * @return null|string
     */
    protected function tableName()
    {
        return $this->_tbName ?? 'table_name_not_exist';
    }

    /**
     * 设置自定义表名
     *
     * @param string $name
     * @return $this
     */
    public function setTable(string $name)
    {
        $this->_tbName = $name;
        return $this;
    }

    /**
     * convert array condition to string
     *
     * @param array $condition
     * @param string $separator
     * @return array
     */
    public function switchCondition(array $condition, $separator = 'and', $suffix='')
    {
        if (isset($condition[0]) && is_string($condition[0])) {
            if (in_array($condition[0],['and','AND','or','OR'])) {
                $separator = array_shift($condition);
            } elseif (in_array($condition[0], [
                '=','is','is not','>','>=','<','<=','!=','<>',
                'between','not between', 'in','not in','like','not like',
            ])) {
                return $this->getFilterItem($condition, $suffix);
            }
        }
        $sqlList = [];
        $params = [];
        foreach ($condition as $key => $value) {
            if (is_string($key)) {
                $item = $this->getKeyValueItem($key, $value, "{$suffix}_{$key}");
            } elseif (!is_array($value)) {
                if ($value === '') $value = 1;
                $item = ['sql' => "({$value})",'params'=>[]];
            } elseif (JArray::isAssociative($value)) {
                $item = $this->switchCondition($value,'and', "{$suffix}_{$key}");
                if (count($value) > 1) {
                    $item['sql'] = "({$item['sql']})";
                }
            } else {
                $item = $this->getFilterItem($value, "{$suffix}_{$key}");
            }

            $sqlList[] = $item['sql'];
            $params = array_merge($params, $item['params']);
        }
        $sql = $sqlList ? implode(" $separator ", $sqlList) : true;
        return [
            'sql' => $sql,
            'params' => $params,
        ];
    }

    private function getKeyValueItem($key,$value,$suffix='')
    {
        $params = [];
        if (is_array($value)) {
            $value = array_unique($value);
            $_cnt = count($value);
            if ($_cnt == 1) {
                $value = array_shift($value);
            } elseif ($_cnt == 0) {
                $value = '##valueNotFound##logicMaybeError##';
            }
        }
        if (is_array($value)) {
            $inList = [];
            foreach ($value as $i => $valueItem) {
                $inList[] = ":{$key}_iv{$i}{$suffix}";
                $params[":{$key}_iv{$i}{$suffix}"] = $valueItem;
            }
            $inStr = implode(',', $inList);
            $sql = "`$key` in ($inStr)";
        } else {
            $sql = "`$key`=:{$key}_ev{$suffix}";
            $params[":{$key}_ev{$suffix}"] = $value;
        }
        return [
            'sql' => "({$sql})",
            'params' => $params,
        ];
    }

    private function getFilterItem($condition, $suffix = '')
    {
        if (!is_array($condition)) {
            return ['sql' => true, 'params' => []];
        }
        $params = [];
        $filter = strtolower(array_shift($condition));
        if (in_array($filter, ['and','or'])) {
            $item = $this->switchCondition($condition, $filter, $suffix);
            $sql = $item['sql'];
            $params = $item['params'];
        } else {
            $col = array_shift($condition);
            switch ($filter) {
                case '=': {}
                case 'is': {}
                case 'is not': {}
                case '>': {}
                case '>=': {}
                case '<': {}
                case '<=': {}
                case '!=': {}
                case '<>': {
                    $sql = "`$col` $filter :{$col}_cv{$suffix}";
                    $params[":{$col}_cv{$suffix}"] = $condition[0];
                } break;
                case 'between': {}
                case 'not between': {
                    $sql = "`$col` $filter :{$col}_min{$suffix} and :{$col}_max{$suffix}";
                    $params[":{$col}_min{$suffix}"] = $condition[0];
                    $params[":{$col}_max{$suffix}"] = $condition[1];
                } break;
                case 'in': {}
                case 'not in': {
                    if (is_array($condition[0])) {
                        $_list = [];
                        foreach ($condition[0] as $_k => $_v) {
                            $_list[] = ":{$col}_iv{$_k}{$suffix}";
                            $params[":{$col}_iv{$_k}{$suffix}"] = $_v;
                        }
                        $_str = implode(',', $_list);
                        $sql = "`$col` $filter ({$_str})";
                    } else {
                        $sql = "`$col` $filter ({$condition[0]})";
                    }
                } break;
                case 'like': {}
                case 'not like': {
                    $_fuzzy = $condition[1] ?? true;
                    if ($_fuzzy) {
                        $_likeStr = "%{$condition[0]}%";
                    } else {
                        $_likeStr = "{$condition[0]}";
                    }
                    $sql = "`$col` $filter :{$col}_word{$suffix}";
                    $params[":{$col}_word{$suffix}"] = $_likeStr;
                } break;
                default: {
                    $sql = true;
                }
            }
        }
        return [
            'sql' => "({$sql})",
            'params' => $params,
        ];
    }

    /**
     * 清空查询条件/绑定参数等历史信息，防止逻辑交叉污染
     * @return $this
     */
    public function flushQuery()
    {
        $this->select = '*';
        $this->where = '';
        $this->params = [];
        $this->limit = 0;
        $this->offset = 0;
        $this->order = '';
        $this->group = '';
        $this->distinct = false;
        $this->sql = '';
        return $this;
    }

    /**
     * 插入与更新操作,存在冲突则执行更新 —— 慎用，多个唯一索引易出错
     * @param array $insertData   map: col => val
     * @param array $updateData  map: col => val
     * @return bool|string
     * @throws \Exception|\PDOException
     */
    public function insertOrUpdate(array $insertData, array $updateData = [])
    {
        $cols = array_keys($insertData);
        $sql = "INSERT INTO "
            . $this->tableName()
            . " (`" . implode("`, `", $cols) . "`) "
            . "VALUES (:" . implode(", :", $cols) . ")";

        if (!empty($updateData)) {
            $str = " ON DUPLICATE KEY UPDATE ";
            foreach ($updateData as $key => $val) {
                $str .= "`". $key ."`= :up". $key. ",";
            }
            $sql .= substr($str, 0, -1);
        }

        $stmt = $this->getStmt($sql);
        if (!$stmt) return false;
        foreach ($insertData as $k => $v) {
            if ($v === false) {
                $v = 0;
            } elseif ($v === null) {
                $v = 'NULL';
            }
            $stmt->bindValue(":{$k}", $v, $this->dataType($v));
        }

        if (!empty($updateData)) {
            foreach ($updateData as $key => $val) {
                $stmt->bindValue(":up{$key}", $val, $this->dataType($val));
            }
        }
        try {
            $res = $stmt->execute();
        } catch(\PDOException $e) {
            throw $e;
        }
        if ($res) {
            return $this->getConn()->lastInsertId();
        }
        return false;
    }

}