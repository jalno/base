<?php

namespace packages\base\db;

use packages\base\Log;

/**
 * MysqliDb Class.
 *
 * @category  Database Access
 *
 * @author	Jeffery Way <jeffrey@jeffrey-way.com>
 * @author	Josh Campbell <jcampbell@ajillion.com>
 * @author	Alexander V. Butenko <a.butenka@gmail.com>
 * @copyright Copyright (c) 2010-2016
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see	  http://github.com/joshcam/PHP-MySQLi-Database-Class
 *
 * @version   2.7
 */
class MysqliDb
{
    public const DEADLOCKTRY = 5;
    public const DEADLOCK_ERRNO = 1213;

    /**
     * Static instance of self.
     *
     * @var MysqliDb
     */
    protected static $_instance;

    /**
     * This flag indicates should print database credentials in var_dump or print_r.
     *
     * @var bool
     */
    public $printDatabaseCredentials = false;

    /**
     * Table prefix.
     *
     * @var string
     */
    public $prefix = '';

    /**
     * MySQLi instance.
     *
     * @var mysqli
     */
    protected $_mysqli;

    /**
     * The SQL query to be prepared and executed.
     *
     * @var string
     */
    protected $_query;

    /**
     * The previously executed SQL query.
     *
     * @var string
     */
    protected $_lastQuery;

    /**
     * The SQL query options required after SELECT, INSERT, UPDATE or DELETE.
     *
     * @var string
     */
    protected $_queryOptions = [];

    /**
     * An array that holds where joins.
     *
     * @var array
     */
    protected $_join = [];

    /**
     * An array that holds where conditions.
     *
     * @var array
     */
    protected $_where = [];
    /**
     * An array that holds where join ands.
     *
     * @var array
     */
    protected $_joinAnd = [];
    /**
     * An array that holds having conditions.
     *
     * @var array
     */
    protected $_having = [];

    /**
     * Dynamic type list for order by condition value.
     *
     * @var array
     */
    protected $_orderBy = [];

    /**
     * Dynamic type list for group by condition value.
     *
     * @var array
     */
    protected $_groupBy = [];

    /**
     * Dynamic array that holds a combination of where condition/table data value types and parameter references.
     *
     * @var array
     */
    protected $_bindParams = ['']; // Create the empty 0 index

    /**
     * Variable which holds an amount of returned rows during get/getOne/select queries.
     *
     * @var string
     */
    public $count = 0;

    /**
     * Variable which holds an amount of returned rows during get/getOne/select queries with withTotalCount().
     *
     * @var string
     */
    public $totalCount = 0;

    /**
     * Variable which holds last statement error.
     *
     * @var string
     */
    protected $_stmtError;

    /**
     * Variable which holds last statement error code.
     *
     * @var int
     */
    protected $_stmtErrno;

    /**
     * Database credentials.
     *
     * @var string
     */
    protected $host;
    protected $username;
    protected $password;
    protected $db;
    protected $port;
    protected $charset;

    /**
     * Is Subquery object.
     *
     * @var bool
     */
    protected $isSubQuery = false;

    /**
     * Name of the auto increment column.
     *
     * @var int
     */
    protected $_lastInsertId;

    /**
     * Column names for update when using onDuplicate method.
     *
     * @var array
     */
    protected $_updateColumns;

    /**
     * Return type: 'array' to return results as array, 'object' as object
     * 'json' as json string.
     *
     * @var string
     */
    public $returnType = 'array';

    /**
     * Should join() results be nested by table.
     *
     * @var bool
     */
    protected $_nestJoin = false;

    /**
     * Table name (with prefix, if used).
     *
     * @var string
     */
    private $_tableName = '';

    /**
     * FOR UPDATE flag.
     *
     * @var bool
     */
    protected $_forUpdate = false;

    /**
     * LOCK IN SHARE MODE flag.
     *
     * @var bool
     */
    protected $_lockInShareMode = false;

    protected $_transaction_in_progress = false;

    /**
     * Key field for Map()'ed result array.
     *
     * @var string
     */
    protected $_mapKey;

    /**
     * Variables for query execution tracing.
     */
    protected $traceStartQ;
    protected $traceEnabled;
    protected $traceStripPrefix;
    public $trace = [];

    /**
     * Per page limit for pagination.
     *
     * @var int
     */
    public $pageLimit = 20;
    /**
     * Variable that holds total pages count of last paginate() query.
     *
     * @var int
     */
    public $totalPages = 0;

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $db
     * @param int    $port
     * @param string $charset
     */
    public function __construct($host = null, $username = null, $password = null, $db = null, $port = null, $charset = 'utf8mb4')
    {
        $isSubQuery = false;

        // if params were passed as array
        if (is_array($host)) {
            foreach ($host as $key => $val) {
                $$key = $val;
            }
        }
        // if host were set as mysqli socket
        if (is_object($host)) {
            $this->_mysqli = $host;
        } else {
            $this->host = $host;
        }

        $this->username = $username;
        $this->password = $password;
        $this->db = $db;
        $this->port = $port;
        $this->charset = $charset;

        if ($isSubQuery) {
            $this->isSubQuery = true;

            return;
        }

        if (isset($prefix)) {
            $this->setPrefix($prefix);
        }

        self::$_instance = $this;
    }

    /**
     * This magic method indicates which properites shown in export of var_dump or print_r.
     *
     * removes database credentials in dumping by $printDatabaseCredentials flags status
     */
    public function __debugInfo(): array
    {
        $properties = get_object_vars($this);
        if (!$this->printDatabaseCredentials) {
            foreach (['host', 'port', 'username', 'password', 'db', 'charset'] as $property) {
                unset($properties[$property]);
            }
        }

        return $properties;
    }

    /**
     * A method to connect to the database.
     *
     * @return void
     *
     * @throws Exception
     */
    public function connect()
    {
        if ($this->isSubQuery) {
            return;
        }
        $log = log::getInstance();

        if (empty($this->host)) {
            $log->fatal('MySQL host is not set');
            throw new \Exception('MySQL host is not set');
        }
        $log->info('connect to '.$this->username.'@'.$this->host.':'.$this->port.'/'.$this->db);
        $this->_mysqli = @new \mysqli($this->host, $this->username, $this->password, $this->db, $this->port);

        if ($this->_mysqli->connect_error) {
            $mysqli = $this->_mysqli;
            $this->_mysqli = null;
            $log->reply()->fatal($mysqli->connect_errno.': '.$mysqli->connect_error);
            throw new \Exception('Connect Error '.$mysqli->connect_errno.': '.$mysqli->connect_error);
        }
        $log->reply('Success');
        if ($this->charset) {
            $log->debug('set charset to', $this->charset);
            $this->_mysqli->set_charset($this->charset);
            $log->reply('Success');
        }
    }

    /**
     * A method to get mysqli object or create it in case needed.
     *
     * @return mysqli
     */
    public function mysqli()
    {
        if (!$this->_mysqli) {
            $log = log::getInstance();
            $log->debug('connect to mysql');
            $this->connect();
            $log->reply('Success');
        }

        return $this->_mysqli;
    }

    /**
     * A method to change default database in connection.
     *
     * @return MysqliDb returns the current instance
     */
    public function select_db($dbname)
    {
        $log = log::getInstance();
        $log->debug('switch database to');
        if ($this->mysqli()->select_db($dbname)) {
            $log->reply('Success');
            $this->db = $dbname;
        } else {
            $log->reply()->error('Failed');
        }

        return $this;
    }

    /**
     * A method of returning the static instance to allow access to the
     * instantiated object from within another class.
     * Inheriting this class would require reloading connection info.
     *
     * @uses $db = MySqliDb::getInstance();
     *
     * @return MysqliDb returns the current instance
     */
    public static function getInstance()
    {
        return self::$_instance;
    }

    /**
     * Reset states after an execution.
     *
     * @return MysqliDb returns the current instance
     */
    protected function reset()
    {
        if ($this->traceEnabled) {
            $this->trace[] = [$this->_lastQuery, microtime(true) - $this->traceStartQ, $this->_traceGetCaller()];
        }

        $this->_where = [];
        $this->_having = [];
        $this->_join = [];
        $this->_joinAnd = [];
        $this->_orderBy = [];
        $this->_groupBy = [];
        $this->_bindParams = ['']; // Create the empty 0 index
        $this->_query = null;
        $this->_queryOptions = [];
        $this->returnType = 'array';
        $this->_nestJoin = false;
        $this->_forUpdate = false;
        $this->_lockInShareMode = false;
        $this->_tableName = '';
        $this->_lastInsertId = null;
        $this->_updateColumns = null;
        $this->_mapKey = null;

        return $this;
    }

    /**
     * Helper function to create dbObject with JSON return type.
     *
     * @return MysqliDb
     */
    public function jsonBuilder()
    {
        $this->returnType = 'json';

        return $this;
    }

    /**
     * Helper function to create dbObject with array return type
     * Added for consistency as thats default output type.
     *
     * @return MysqliDb
     */
    public function arrayBuilder()
    {
        $this->returnType = 'array';

        return $this;
    }

    /**
     * Helper function to create dbObject with object return type.
     *
     * @return MysqliDb
     */
    public function objectBuilder()
    {
        $this->returnType = 'object';

        return $this;
    }

    /**
     * Method to set a prefix.
     *
     * @param string $prefix Contains a tableprefix
     *
     * @return MysqliDb
     */
    public function setPrefix($prefix = '')
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Execute raw SQL query.
     *
     * @param string $query      user-provided query to execute
     * @param array  $bindParams variables array to bind to the SQL statement
     *
     * @return array contains the returned rows from the query
     */
    public function rawQuery($query, $bindParams = null)
    {
        $params = ['']; // Create the empty 0 index
        $this->_query = $query;
        $stmt = $this->_prepareQuery();

        if (true === is_array($bindParams)) {
            foreach ($bindParams as $prop => $val) {
                $params[0] .= $this->_determineType($val);
                array_push($params, $bindParams[$prop]);
            }

            call_user_func_array([$stmt, 'bind_param'], $this->refValues($params));
        }

        $this->_lastQuery = $this->replacePlaceHolders($this->_query, $params);
        $log = log::getInstance();
        $log->debug('SQL Query:', $this->_lastQuery);

        $this->executeStmt($stmt);
        $this->count = $stmt->affected_rows;
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $res = $this->_dynamicBindResults($stmt);
        $this->reset();

        return $res;
    }

    /**
     * Helper function to execute raw SQL query and return only 1 row of results.
     * Note that function do not add 'limit 1' to the query by itself
     * Same idea as getOne().
     *
     * @param string $query      user-provided query to execute
     * @param array  $bindParams variables array to bind to the SQL statement
     *
     * @return array|null contains the returned row from the query
     */
    public function rawQueryOne($query, $bindParams = null)
    {
        $res = $this->rawQuery($query, $bindParams);
        if (is_array($res) && isset($res[0])) {
            return $res[0];
        }

        return null;
    }

    /**
     * Helper function to execute raw SQL query and return only 1 column of results.
     * If 'limit 1' will be found, then string will be returned instead of array
     * Same idea as getValue().
     *
     * @param string $query      user-provided query to execute
     * @param array  $bindParams variables array to bind to the SQL statement
     *
     * @return mixed contains the returned rows from the query
     */
    public function rawQueryValue($query, $bindParams = null)
    {
        $res = $this->rawQuery($query, $bindParams);
        if (!$res) {
            return null;
        }

        $limit = preg_match('/limit\s+1;?$/i', $query);
        $key = key($res[0]);
        if (isset($res[0][$key]) && true == $limit) {
            return $res[0][$key];
        }

        $newRes = [];
        for ($i = 0; $i < $this->count; ++$i) {
            $newRes[] = $res[$i][$key];
        }

        return $newRes;
    }

    /**
     * A method to perform select query.
     *
     * @param string    $query   contains a user-provided select query
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *
     * @return array contains the returned rows from the query
     */
    public function query($query, $numRows = null)
    {
        $this->_query = $query;
        $stmt = $this->_buildQuery($numRows);
        $this->executeStmt($stmt);
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $res = $this->_dynamicBindResults($stmt);
        $this->reset();

        return $res;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) options for SQL queries.
     *
     * @uses $MySqliDb->setQueryOption('name');
     *
     * @param string|array $options the optons name of the query
     *
     * @return MysqliDb
     *
     * @throws Exception
     */
    public function setQueryOption($options)
    {
        $allowedOptions = ['ALL', 'DISTINCT', 'DISTINCTROW', 'HIGH_PRIORITY', 'STRAIGHT_JOIN', 'SQL_SMALL_RESULT',
            'SQL_BIG_RESULT', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_NO_CACHE', 'SQL_CALC_FOUND_ROWS',
            'LOW_PRIORITY', 'IGNORE', 'QUICK', 'MYSQLI_NESTJOIN', 'FOR UPDATE', 'LOCK IN SHARE MODE'];

        if (!is_array($options)) {
            $options = [$options];
        }

        foreach ($options as $option) {
            $option = strtoupper($option);
            if (!in_array($option, $allowedOptions)) {
                throw new \Exception('Wrong query option: '.$option);
            }

            if ('MYSQLI_NESTJOIN' == $option) {
                $this->_nestJoin = true;
            } elseif ('FOR UPDATE' == $option) {
                $this->_forUpdate = true;
            } elseif ('LOCK IN SHARE MODE' == $option) {
                $this->_lockInShareMode = true;
            } else {
                $this->_queryOptions[] = $option;
            }
        }

        return $this;
    }

    /**
     * Function to enable SQL_CALC_FOUND_ROWS in the get queries.
     *
     * @return MysqliDb
     */
    public function withTotalCount()
    {
        $this->setQueryOption('SQL_CALC_FOUND_ROWS');

        return $this;
    }

    /**
     * A convenient SELECT * function.
     *
     * @param string    $tableName the name of the database table to work with
     * @param int|array $numRows   Array to define SQL limit in format Array ($count, $offset)
     *                             or only $count
     * @param string    $columns   Desired columns
     *
     * @return array contains the returned rows from the select query
     */
    public function get($tableName, $numRows = null, $columns = '*')
    {
        if (empty($columns)) {
            $columns = '*';
        }

        $column = is_array($columns) ? implode(', ', $columns) : $columns;

        if (false === strpos($tableName, '.')) {
            $this->_tableName = $this->prefix.$tableName;
        } else {
            $this->_tableName = $tableName;
        }

        $this->_query = 'SELECT '.implode(' ', $this->_queryOptions).' '.
            $column.' FROM '.$this->_tableName;
        $stmt = $this->_buildQuery($numRows);

        if ($this->isSubQuery) {
            return $this;
        }

        $this->executeStmt($stmt);
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $res = $this->_dynamicBindResults($stmt);
        $this->reset();

        return $res;
    }

    /**
     * A convenient SELECT * function to get one record.
     *
     * @param string $tableName the name of the database table to work with
     * @param string $columns   Desired columns
     *
     * @return array contains the returned rows from the select query
     */
    public function getOne($tableName, $columns = '*')
    {
        $res = $this->get($tableName, 1, $columns);

        if ($res instanceof MysqliDb) {
            return $res;
        } elseif (is_array($res) && isset($res[0])) {
            return $res[0];
        } elseif ($res) {
            return $res;
        }

        return null;
    }

    /**
     * A convenient SELECT COLUMN function to get a single column value from one row.
     *
     * @param string $tableName the name of the database table to work with
     * @param string $column    The desired column
     * @param int    $limit     Limit of rows to select. Use null for unlimited..1 by default
     *
     * @return mixed Contains the value of a returned column / array of values
     */
    public function getValue($tableName, $column, $limit = 1)
    {
        $res = $this->ArrayBuilder()->get($tableName, $limit, "{$column} AS retval");

        if (!$res) {
            return null;
        }

        if (1 == $limit) {
            if (isset($res[0]['retval'])) {
                return $res[0]['retval'];
            }

            return null;
        }

        $newRes = [];
        for ($i = 0; $i < $this->count; ++$i) {
            $newRes[] = $res[$i]['retval'];
        }

        return $newRes;
    }

    /**
     * Insert method to add new row.
     *
     * @param string $tableName  the name of the table
     * @param array  $insertData data containing information for inserting into the DB
     *
     * @return bool boolean indicating whether the insert query was completed succesfully
     */
    public function insert($tableName, $insertData)
    {
        return $this->_buildInsert($tableName, $insertData, 'INSERT');
    }

    /**
     * Insert method to add several rows at once.
     *
     * @param string $tableName       the name of the table
     * @param array  $multiInsertData two-dimensinal Data-array containing information for inserting into the DB
     *
     * @return bool|array Boolean indicating the insertion failed (false), else return last id inserted id
     */
    public function insertMulti($tableName, array $multiInsertData)
    {
        return $this->_buildInsert($tableName, $multiInsertData, 'INSERT');
    }

    /**
     * Replace method to add new row.
     *
     * @param string $tableName  the name of the table
     * @param array  $insertData data containing information for inserting into the DB
     *
     * @return bool boolean indicating whether the insert query was completed succesfully
     */
    public function replace($tableName, $insertData)
    {
        return $this->_buildInsert($tableName, $insertData, 'REPLACE');
    }

    /**
     * A convenient function that returns TRUE if exists at least an element that
     * satisfy the where condition specified calling the "where" method before this one.
     *
     * @param string $tableName the name of the database table to work with
     */
    public function has($tableName): bool
    {
        $this->getOne($tableName, '1');

        return $this->count >= 1;
    }

    /**
     * Update query. Be sure to first call the "where" method.
     *
     * @param string $tableName the name of the database table to work with
     * @param array  $tableData array of data to update the desired row
     * @param int    $numRows   limit on the number of rows that can be updated
     *
     * @return bool
     */
    public function update($tableName, $tableData, $numRows = null)
    {
        if ($this->isSubQuery) {
            return;
        }

        $this->_query = 'UPDATE '.$this->prefix.$tableName;

        $stmt = $this->_buildQuery($numRows, $tableData);
        $status = $this->executeStmt($stmt);
        $this->reset();
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $this->count = $stmt->affected_rows;

        return $status;
    }

    /**
     * Delete query. Call the "where" method first.
     *
     * @param string    $tableName the name of the database table to work with
     * @param int|array $numRows   Array to define SQL limit in format Array ($count, $offset)
     *                             or only $count
     *
     * @return bool Indicates success. 0 or 1.
     */
    public function delete($tableName, $numRows = null)
    {
        if ($this->isSubQuery) {
            return;
        }

        $table = $this->prefix.$tableName;

        if (count($this->_join)) {
            $this->_query = 'DELETE '.preg_replace('/.* (.*)/', '$1', $table).' FROM '.$table;
        } else {
            $this->_query = 'DELETE FROM '.$table;
        }

        $stmt = $this->_buildQuery($numRows);
        $this->executeStmt($stmt);
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $this->reset();

        return $stmt->affected_rows > 0;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) AND WHERE statements for SQL queries.
     *
     * @uses $MySqliDb->where('id', 7)->where('title', 'MyTitle');
     *
     * @param string $whereProp  the name of the database field
     * @param mixed  $whereValue the value of the database field
     * @param string $operator   Comparison operator. Default is =
     * @param string $cond       Condition of where statement (OR, AND)
     *
     * @return MysqliDb
     */
    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        // forkaround for an old operation api
        if (is_array($whereValue) && ($key = key($whereValue)) != '0') {
            $operator = $key;
            $whereValue = $whereValue[$key];
        }

        if (0 == count($this->_where)) {
            $cond = '';
        }
        if ('contains' == $operator) {
            $whereValue = '%'.$whereValue.'%';
            $operator = 'LIKE';
        } elseif ('equals' == $operator) {
            $whereValue = $whereValue;
            $operator = '=';
        } elseif ('startswith' == $operator) {
            $whereValue = $whereValue.'%';
            $operator = 'LIKE';
        }
        $this->_where[] = [$cond, $whereProp, $operator, $whereValue];

        return $this;
    }

    /**
     * This function store update column's name and column name of the
     * autoincrement column.
     *
     * @param array  $updateColumns Variable with values
     * @param string $lastInsertId  Variable value
     *
     * @return MysqliDb
     */
    public function onDuplicate($updateColumns, $lastInsertId = null)
    {
        $this->_lastInsertId = $lastInsertId;
        $this->_updateColumns = $updateColumns;

        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) OR WHERE statements for SQL queries.
     *
     * @uses $MySqliDb->orWhere('id', 7)->orWhere('title', 'MyTitle');
     *
     * @param string $whereProp  the name of the database field
     * @param mixed  $whereValue the value of the database field
     * @param string $operator   Comparison operator. Default is =
     *
     * @return MysqliDb
     */
    public function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        return $this->where($whereProp, $whereValue, $operator, 'OR');
    }

    /**
     * This method allows you to specify multiple (method chaining optional) AND HAVING statements for SQL queries.
     *
     * @uses $MySqliDb->having('SUM(tags) > 10')
     *
     * @param string $havingProp  the name of the database field
     * @param mixed  $havingValue the value of the database field
     * @param string $operator    Comparison operator. Default is =
     *
     * @return MysqliDb
     */
    public function having($havingProp, $havingValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        // forkaround for an old operation api
        if (is_array($havingValue) && ($key = key($havingValue)) != '0') {
            $operator = $key;
            $havingValue = $havingValue[$key];
        }

        if (0 == count($this->_having)) {
            $cond = '';
        }

        $this->_having[] = [$cond, $havingProp, $operator, $havingValue];

        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) OR HAVING statements for SQL queries.
     *
     * @uses $MySqliDb->orHaving('SUM(tags) > 10')
     *
     * @param string $havingProp  the name of the database field
     * @param mixed  $havingValue the value of the database field
     * @param string $operator    Comparison operator. Default is =
     *
     * @return MysqliDb
     */
    public function orHaving($havingProp, $havingValue = null, $operator = null)
    {
        return $this->having($havingProp, $havingValue, $operator, 'OR');
    }

    /**
     * This method allows you to concatenate joins for the final SQL statement.
     *
     * @uses $MySqliDb->join('table1', 'field1 <> field2', 'LEFT')
     *
     * @param string $joinTable     the name of the table
     * @param string $joinCondition the condition
     * @param string $joinType      'LEFT', 'INNER' etc
     *
     * @return MysqliDb
     *
     * @throws Exception
     */
    public function join($joinTable, $joinCondition, $joinType = '')
    {
        $allowedTypes = ['LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'];
        $joinType = strtoupper(trim($joinType));

        if ($joinType && !in_array($joinType, $allowedTypes)) {
            throw new \Exception('Wrong JOIN type: '.$joinType);
        }

        if (!is_object($joinTable)) {
            $joinTable = $this->prefix.$joinTable;
        }

        $this->_join[] = [$joinType, $joinTable, $joinCondition];

        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) AND WHERE statements for the join table on part of the SQL query.
     *
     * @uses $dbWrapper->joinWhere('user u', 'u.id', 7)->where('user u', 'u.title', 'MyTitle');
     *
     * @param string $whereJoin  the name of the table followed by its prefix
     * @param string $whereProp  the name of the database field
     * @param mixed  $whereValue the value of the database field
     *
     * @return dbWrapper
     */
    public function joinWhere($whereJoin, $whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        $this->_joinAnd[$this->prefix.$whereJoin][] = [$cond, $whereProp, $operator, $whereValue];

        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) OR WHERE statements for the join table on part of the SQL query.
     *
     * @uses $dbWrapper->joinWhere('user u', 'u.id', 7)->where('user u', 'u.title', 'MyTitle');
     *
     * @param string $whereJoin  the name of the table followed by its prefix
     * @param string $whereProp  the name of the database field
     * @param mixed  $whereValue the value of the database field
     *
     * @return dbWrapper
     */
    public function joinOrWhere($whereJoin, $whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        return $this->joinWhere($whereJoin, $whereProp, $whereValue, $operator, 'OR');
    }

    /**
     * This method allows you to specify multiple (method chaining optional) ORDER BY statements for SQL queries.
     *
     * @uses $MySqliDb->orderBy('id', 'desc')->orderBy('name', 'desc');
     *
     * @param string $orderByField the name of the database field
     * @param array  $customFields Fieldset for ORDER BY FIELD() ordering
     *
     * @return MysqliDb
     *
     * @throws Exception
     */
    public function orderBy($orderByField, $orderbyDirection = 'DESC', $customFields = null)
    {
        $allowedDirection = ['ASC', 'DESC'];
        $orderbyDirection = strtoupper(trim($orderbyDirection));
        $orderByField = preg_replace("/[^-a-z0-9\.\(\),_`\*\'\"]+/i", '', $orderByField);

        // Add table prefix to orderByField if needed.
        // FIXME: We are adding prefix only if table is enclosed into `` to distinguish aliases
        // from table names
        $orderByField = preg_replace('/(\`)([`a-zA-Z0-9_]*\.)/', '\1'.$this->prefix.'\2', $orderByField);

        if (empty($orderbyDirection) || !in_array($orderbyDirection, $allowedDirection)) {
            throw new \Exception('Wrong order direction: '.$orderbyDirection);
        }

        if (is_array($customFields)) {
            $orderbyDirection = array_merge([$orderbyDirection, $orderByField], array_values($customFields));
            $orderByField = 'FIELD()';
        }

        $this->_orderBy[$orderByField] = $orderbyDirection;

        return $this;
    }

    /**
     * This method allows you to specify multiple (method chaining optional) GROUP BY statements for SQL queries.
     *
     * @uses $MySqliDb->groupBy('name');
     *
     * @param string $groupByField the name of the database field
     *
     * @return MysqliDb
     */
    public function groupBy($groupByField)
    {
        $this->_groupBy[] = $groupByField;

        return $this;
    }

    /**
     * This methods returns the ID of the last inserted item.
     *
     * @return int the last inserted item ID
     */
    public function getInsertId()
    {
        return $this->mysqli()->insert_id;
    }

    /**
     * Escape harmful characters which might affect a query.
     *
     * @param string $str the string to escape
     *
     * @return string the escaped string
     */
    public function escape($str)
    {
        return $this->mysqli()->real_escape_string($str);
    }

    /**
     * Method to call mysqli->ping() to keep unused connections open on
     * long-running scripts, or to reconnect timed out connections (if php.ini has
     * global mysqli.reconnect set to true). Can't do this directly using object
     * since _mysqli is protected.
     *
     * @return bool True if connection is up
     */
    public function ping()
    {
        return $this->mysqli()->ping();
    }

    /**
     * This method is needed for prepared statements. They require
     * the data type of the field to be bound with "i" s", etc.
     * This function takes the input, determines what type it is,
     * and then updates the param_type.
     *
     * @param mixed $item input to determine the type
     *
     * @return string the joined parameter types
     */
    protected function _determineType($item)
    {
        switch (gettype($item)) {
            case 'NULL':
            case 'string':
                return 's';
            case 'boolean':
            case 'integer':
                return 'i';
            case 'blob':
                return 'b';
            case 'double':
                return 's';
        }

        return '';
    }

    /**
     * Helper function to add variables into bind parameters array.
     *
     * @param string Variable value
     */
    protected function _bindParam($value)
    {
        $this->_bindParams[0] .= $this->_determineType($value);
        array_push($this->_bindParams, $value);
    }

    /**
     * Helper function to add variables into bind parameters array in bulk.
     *
     * @param array $values Variable with values
     */
    protected function _bindParams($values)
    {
        foreach ($values as $value) {
            $this->_bindParam($value);
        }
    }

    /**
     * Helper function to add variables into bind parameters array and will return
     * its SQL part of the query according to operator in ' $operator ?' or
     * ' $operator ($subquery) ' formats.
     *
     * @param string $operator
     * @param mixed  $value    Variable with values
     *
     * @return string
     */
    protected function _buildPair($operator, $value)
    {
        if (!is_object($value)) {
            $this->_bindParam($value);

            return ' '.$operator.' ? ';
        }

        $subQuery = $value->getSubQuery();
        $this->_bindParams($subQuery['params']);

        return ' '.$operator.' ('.$subQuery['query'].') '.$subQuery['alias'];
    }

    /**
     * Internal function to build and execute INSERT/REPLACE calls.
     *
     * @param string $tableName  the name of the table
     * @param array  $insertData data containing information for inserting into the DB
     * @param string $operation  Type of operation (INSERT, REPLACE)
     *
     * @return bool boolean indicating whether the insert query was completed succesfully
     */
    private function _buildInsert($tableName, $insertData, $operation)
    {
        if ($this->isSubQuery) {
            return;
        }

        $this->_query = $operation.' '.implode(' ', $this->_queryOptions).' INTO '.$this->prefix.$tableName;
        $stmt = $this->_buildQuery(null, $insertData);
        $status = $this->executeStmt($stmt);
        $this->_stmtError = $stmt->error;
        $this->_stmtErrno = $stmt->errno;
        $haveOnDuplicate = !empty($this->_updateColumns);
        $this->reset();
        $this->count = $stmt->affected_rows;

        if ($stmt->affected_rows < 1) {
            // in case of onDuplicate() usage, if no rows were inserted
            if ($status && $haveOnDuplicate) {
                return true;
            }

            return false;
        }

        if ($stmt->insert_id > 0) {
            return $stmt->insert_id;
        }

        return true;
    }

    /**
     * Abstraction method that will compile the WHERE statement,
     * any passed update data, and the desired rows.
     * It then builds the SQL query.
     *
     * @param int|array $numRows   Array to define SQL limit in format Array ($count, $offset)
     *                             or only $count
     * @param array     $tableData should contain an array of data for updating the database
     *
     * @return mysqli_stmt returns the $stmt object
     */
    protected function _buildQuery($numRows = null, $tableData = null)
    {
        // $this->_buildJoinOld();
        $this->_buildJoin();
        $this->_buildInsertQuery($tableData);
        $this->_buildCondition('WHERE', $this->_where);
        $this->_buildGroupBy();
        $this->_buildCondition('HAVING', $this->_having);
        $this->_buildOrderBy();
        $this->_buildLimit($numRows);
        $this->_buildOnDuplicate($tableData);

        if ($this->_forUpdate) {
            $this->_query .= ' FOR UPDATE';
        }
        if ($this->_lockInShareMode) {
            $this->_query .= ' LOCK IN SHARE MODE';
        }

        $this->_lastQuery = $this->replacePlaceHolders($this->_query, $this->_bindParams);
        // echo($this->_lastQuery."\n");
        if ($this->isSubQuery) {
            return;
        }
        $log = log::getInstance();
        $log->debug('SQL Query:', $this->_lastQuery);

        // Prepare query
        $stmt = $this->_prepareQuery();

        // Bind parameters to statement if any
        if (count($this->_bindParams) > 1) {
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($this->_bindParams));
        }

        return $stmt;
    }

    /**
     * This helper method takes care of prepared statements' "bind_result method
     * , when the number of variables to pass is unknown.
     *
     * @param mysqli_stmt $stmt equal to the prepared statement object
     *
     * @return array the results of the SQL fetch
     */
    protected function _dynamicBindResults(\mysqli_stmt $stmt)
    {
        $parameters = [];
        $results = [];
        /**
         * @see http://php.net/manual/en/mysqli-result.fetch-fields.php
         */
        $mysqlLongType = 252;
        $shouldStoreResult = false;

        $meta = $stmt->result_metadata();

        // if $meta is false yet sqlstate is true, there's no sql error but the query is
        // most likely an update/insert/delete which doesn't produce any results
        if (!$meta && $stmt->sqlstate) {
            return [];
        }

        $row = [];
        while ($field = $meta->fetch_field()) {
            if ($field->type == $mysqlLongType) {
                $shouldStoreResult = true;
            }

            if ($this->_nestJoin && $field->table != $this->_tableName) {
                $field->table = substr($field->table, strlen($this->prefix));
                $row[$field->table][$field->name] = null;
                $parameters[] = &$row[$field->table][$field->name];
            } else {
                $row[$field->name] = null;
                $parameters[] = &$row[$field->name];
            }
        }

        // avoid out of memory bug in php 5.2 and 5.3. Mysqli allocates lot of memory for long*
        // and blob* types. So to avoid out of memory issues store_result is used
        // https://github.com/joshcam/PHP-MySQLi-Database-Class/pull/119
        if ($shouldStoreResult) {
            $stmt->store_result();
        }

        call_user_func_array([$stmt, 'bind_result'], $parameters);

        $this->totalCount = 0;
        $this->count = 0;

        while ($stmt->fetch()) {
            if ('object' == $this->returnType) {
                $result = new \stdClass();
                foreach ($row as $key => $val) {
                    if (is_array($val)) {
                        $result->$key = new \stdClass();
                        foreach ($val as $k => $v) {
                            $result->$key->$k = $v;
                        }
                    } else {
                        $result->$key = $val;
                    }
                }
            } else {
                $result = [];
                foreach ($row as $key => $val) {
                    if (is_array($val)) {
                        foreach ($val as $k => $v) {
                            $result[$key][$k] = $v;
                        }
                    } else {
                        $result[$key] = $val;
                    }
                }
            }
            ++$this->count;
            if ($this->_mapKey) {
                $results[$row[$this->_mapKey]] = count($row) > 2 ? $result : end($result);
            } else {
                array_push($results, $result);
            }
        }

        if ($shouldStoreResult) {
            $stmt->free_result();
        }

        $stmt->close();

        // stored procedures sometimes can return more then 1 resultset
        if ($this->mysqli()->more_results()) {
            $this->mysqli()->next_result();
        }

        if (in_array('SQL_CALC_FOUND_ROWS', $this->_queryOptions)) {
            $stmt = $this->mysqli()->query('SELECT FOUND_ROWS()');
            $totalCount = $stmt->fetch_row();
            $this->totalCount = $totalCount[0];
        }

        if ('json' == $this->returnType) {
            return json_encode($results);
        }

        return $results;
    }

    /**
     * Abstraction method that will build an JOIN part of the query.
     *
     * @return void
     */
    protected function _buildJoinOld()
    {
        if (empty($this->_join)) {
            return;
        }

        foreach ($this->_join as $data) {
            list($joinType, $joinTable, $joinCondition) = $data;

            if (is_object($joinTable)) {
                $joinStr = $this->_buildPair('', $joinTable);
            } else {
                $joinStr = $joinTable;
            }

            $this->_query .= ' '.$joinType.' JOIN '.$joinStr.
                (false !== stripos($joinCondition, 'using') ? ' ' : ' on ')
                .$joinCondition;
        }
    }

    /**
     * Abstraction method that will build an JOIN part of the query.
     */
    protected function _buildJoin()
    {
        if (empty($this->_join)) {
            return;
        }

        foreach ($this->_join as $data) {
            list($joinType, $joinTable, $joinCondition) = $data;

            if (is_object($joinTable)) {
                $joinStr = $this->_buildPair('', $joinTable);
            } else {
                $joinStr = $joinTable;
            }

            $this->_query .= ' '.$joinType.' JOIN '.$joinStr.' on '.$joinCondition;

            // Add join and query
            if (!empty($this->_joinAnd) && isset($this->_joinAnd[$joinStr])) {
                foreach ($this->_joinAnd[$joinStr] as $join_and_cond) {
                    list($concat, $varName, $operator, $val) = $join_and_cond;
                    $this->_query .= ' '.$concat.' ';
                    if (is_object($varName) and $varName instanceof parenthesis) {
                        $this->_query .= ' (';
                        $condis = $varName->getWheres();
                        $this->_buildCondition(null, $condis);
                        $this->_query .= ')';
                        continue;
                    }
                    $this->_query .= $varName;
                    $this->conditionToSql($operator, $val);
                }
            }
        }
    }

    /**
     * Convert a condition and value into the sql string.
     *
     * @param string $operator The where constraint operator
     * @param string $val      The where constraint value
     */
    private function conditionToSql($operator, $val)
    {
        switch (strtolower($operator)) {
            case 'not in':
            case 'in':
                $comparison = ' '.$operator.' (';
                if (is_object($val)) {
                    $comparison .= $this->_buildPair('', $val);
                } else {
                    foreach ($val as $v) {
                        $comparison .= ' ?,';
                        $this->_bindParam($v);
                    }
                }
                $this->_query .= rtrim($comparison, ',').' ) ';
                break;
            case 'not between':
            case 'between':
                $this->_query .= " $operator ? AND ? ";
                $this->_bindParams($val);
                break;
            case 'not exists':
            case 'exists':
                $this->_query .= $operator.$this->_buildPair('', $val);
                break;
            default:
                if (is_array($val)) {
                    $this->_bindParams($val);
                } elseif (null === $val) {
                    $this->_query .= $operator.' NULL';
                } elseif ('DBNULL' != $val || '0' == $val) {
                    $this->_query .= $this->_buildPair($operator, $val);
                }
        }
    }

    /**
     * Insert/Update query helper.
     *
     * @param array $tableData
     * @param array $tableColumns
     * @param bool  $isInsert     INSERT operation flag
     *
     * @throws Exception
     */
    public function _buildDataPairs($tableData, $tableColumns, $isInsert)
    {
        foreach ($tableColumns as $column) {
            $value = $tableData[$column];

            if (!$isInsert) {
                if (false === strpos($column, '.')) {
                    $this->_query .= '`'.$column.'` = ';
                } else {
                    $this->_query .= str_replace('.', '.`', $column).'` = ';
                }
            }

            // Subquery value
            if ($value instanceof MysqliDb) {
                $this->_query .= $this->_buildPair('', $value).', ';
                continue;
            }

            // Simple value
            if (!is_array($value)) {
                $this->_bindParam($value);
                $this->_query .= '?, ';
                continue;
            }

            // Function value
            $key = key($value);
            $val = $value[$key];
            switch ($key) {
                case '[I]':
                    $this->_query .= $column.$val.', ';
                    break;
                case '[F]':
                    $this->_query .= $val[0].', ';
                    if (!empty($val[1])) {
                        $this->_bindParams($val[1]);
                    }
                    break;
                case '[N]':
                    if (null == $val) {
                        $this->_query .= '!'.$column.', ';
                    } else {
                        $this->_query .= '!'.$val.', ';
                    }
                    break;
                default:
                    throw new \Exception('Wrong operation');
            }
        }
        $this->_query = rtrim($this->_query, ', ');
    }

    /**
     * Helper function to add variables into the query statement.
     *
     * @param array $tableData Variable with values
     */
    protected function _buildOnDuplicate($tableData)
    {
        if (is_array($this->_updateColumns) && !empty($this->_updateColumns)) {
            $this->_query .= ' ON DUPLICATE KEY UPDATE ';
            if ($this->_lastInsertId) {
                $this->_query .= $this->_lastInsertId.'=LAST_INSERT_ID ('.$this->_lastInsertId.'), ';
            }

            foreach ($this->_updateColumns as $key => $val) {
                // skip all params without a value
                if (is_numeric($key)) {
                    $this->_updateColumns[$val] = '';
                    unset($this->_updateColumns[$key]);
                } else {
                    $tableData[$key] = $val;
                }
            }
            $this->_buildDataPairs($tableData, array_keys($this->_updateColumns), false);
        }
    }

    /**
     * Abstraction method that will build an INSERT or UPDATE part of the query.
     *
     * @param array $tableData
     */
    protected function _buildInsertQuery($tableData)
    {
        if (!is_array($tableData)) {
            return;
        }
        $length = count($tableData);
        $isInsert = preg_match('/^[INSERT|REPLACE]/', $this->_query);
        $isSingle = array_keys($tableData) !== range(0, $length - 1);

        $dataColumns = array_keys($isSingle ? $tableData : $tableData[0]);
        if ($isInsert) {
            if (isset($dataColumns[0])) {
                $this->_query .= ' (`'.implode('`, `', $dataColumns).'`) ';
            }
            $this->_query .= ' VALUES ';
        } else {
            $this->_query .= ' SET ';
        }
        if ($isSingle) {
            if ($isInsert) {
                $this->_query .= '(';
            }
            $this->_buildDataPairs($tableData, $dataColumns, $isInsert);
            if ($isInsert) {
                $this->_query .= ')';
            }
        } elseif ($isInsert) {
            for ($x = 0; $x < $length; ++$x) {
                $this->_query .= '(';
                $this->_buildDataPairs($tableData[$x], $dataColumns, $isInsert);
                $this->_query .= ')';
                if ($x != $length - 1) {
                    $this->_query .= ',';
                }
            }
        }
    }

    /**
     * Abstraction method that will build the part of the WHERE conditions.
     *
     * @param string $operator
     * @param array  $conditions
     */
    protected function _buildCondition($operator, &$conditions)
    {
        if (empty($conditions)) {
            return;
        }

        // Prepare the where portion of the query
        if ($operator) {
            $this->_query .= ' '.$operator;
        }

        foreach ($conditions as $cond) {
            list($concat, $varName, $operator, $val) = $cond;
            $this->_query .= ' '.$concat.' ';
            if (is_object($varName) and $varName instanceof parenthesis) {
                $this->_query .= '(';
                $condis = $varName->getWheres();
                $this->_buildCondition(null, $condis);
                $this->_query .= ')';
                continue;
            }
            $this->_query .= $varName;

            switch (strtolower($operator)) {
                case 'not in':
                case 'in':
                    $comparison = ' '.$operator.' (';
                    if (is_object($val)) {
                        $comparison .= $this->_buildPair('', $val);
                    } else {
                        foreach ($val as $v) {
                            $comparison .= ' ?,';
                            $this->_bindParam($v);
                        }
                    }
                    $this->_query .= rtrim($comparison, ',').' ) ';
                    break;
                case 'not between':
                case 'between':
                    $this->_query .= " $operator ? AND ? ";
                    $this->_bindParams($val);
                    break;
                case 'not exists':
                case 'exists':
                    $this->_query .= $operator.$this->_buildPair('', $val);
                    break;
                default:
                    if (is_array($val)) {
                        $this->_bindParams($val);
                    } elseif (null === $val) {
                        $this->_query .= ' '.$operator.' NULL';
                    } elseif ('DBNULL' != $val || '0' == $val) {
                        $this->_query .= $this->_buildPair($operator, $val);
                    }
            }
        }
    }

    /**
     * Abstraction method that will build the GROUP BY part of the WHERE statement.
     *
     * @return void
     */
    protected function _buildGroupBy()
    {
        if (empty($this->_groupBy)) {
            return;
        }

        $this->_query .= ' GROUP BY ';

        foreach ($this->_groupBy as $key => $value) {
            $this->_query .= $value.', ';
        }

        $this->_query = rtrim($this->_query, ', ').' ';
    }

    /**
     * Abstraction method that will build the LIMIT part of the WHERE statement.
     *
     * @return void
     */
    protected function _buildOrderBy()
    {
        if (empty($this->_orderBy)) {
            return;
        }

        $this->_query .= ' ORDER BY ';
        foreach ($this->_orderBy as $prop => $value) {
            if ('rand()' == strtolower(str_replace(' ', '', $prop))) {
                $this->_query .= 'rand(), ';
            } elseif ('FIELD()' == $prop) {
                $this->_query .= "FIELD({$value[1]}".str_repeat(', ?', count($value) - 2).") {$value[0]}, ";
                for ($x = 2, $l = count($value); $x < $l; ++$x) {
                    $this->_bindParam($value[$x]);
                }
            } else {
                $this->_query .= $prop.' '.$value.', ';
            }
        }

        $this->_query = rtrim($this->_query, ', ').' ';
    }

    /**
     * Abstraction method that will build the LIMIT part of the WHERE statement.
     *
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *                           or only $count
     *
     * @return void
     */
    protected function _buildLimit($numRows)
    {
        if (!isset($numRows)) {
            return;
        }

        if (is_array($numRows)) {
            $this->_query .= ' LIMIT '.(int) $numRows[0].', '.(int) $numRows[1];
        } else {
            $this->_query .= ' LIMIT '.(int) $numRows;
        }
    }

    /**
     * Method attempts to prepare the SQL query
     * and throws an error if there was a problem.
     *
     * @return mysqli_stmt
     */
    protected function _prepareQuery()
    {
        // echo("{$this->_query}\n");
        if (!$stmt = $this->mysqli()->prepare($this->_query)) {
            $msg = $this->mysqli()->error.' query: '.$this->_query;
            $this->reset();
            throw new \Exception($msg);
        }

        if ($this->traceEnabled) {
            $this->traceStartQ = microtime(true);
        }

        return $stmt;
    }

    /**
     * Close connection.
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->isSubQuery) {
            return;
        }

        if ($this->_mysqli) {
            $this->_mysqli->close();
            $this->_mysqli = null;
        }
    }

    /**
     * Referenced data array is required by mysqli since PHP 5.3+.
     *
     * @return array
     */
    protected function refValues(array &$arr)
    {
        // Reference in the function arguments are required for HHVM to work
        // https://github.com/facebook/hhvm/issues/5155
        // Referenced data array is required by mysqli since PHP 5.3+
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = [];
            foreach ($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }

            return $refs;
        }

        return $arr;
    }

    /**
     * Function to replace ? with variables from bind variable.
     *
     * @param string $str
     * @param array  $vals
     *
     * @return string
     */
    protected function replacePlaceHolders($str, $vals)
    {
        $i = 1;
        $newStr = '';

        if (empty($vals)) {
            return $str;
        }

        while ($pos = strpos($str, '?')) {
            $val = $vals[$i++];
            if (is_object($val)) {
                $val = '[object]';
            }
            if (null === $val) {
                $val = 'NULL';
            }
            $newStr .= substr($str, 0, $pos)."'".$val."'";
            $str = substr($str, $pos + 1);
        }
        $newStr .= $str;

        return $newStr;
    }

    /**
     * Method returns last executed query.
     *
     * @return string
     */
    public function getLastQuery()
    {
        return $this->_lastQuery;
    }

    /**
     * Method returns mysql error.
     *
     * @return string
     */
    public function getLastError()
    {
        if (!$this->_mysqli) {
            return 'mysqli is null';
        }

        return trim($this->_stmtError.' '.$this->mysqli()->error);
    }

    /**
     * Method returns mysql error code.
     *
     * @return int
     */
    public function getLastErrno()
    {
        return $this->_stmtErrno;
    }

    /**
     * Mostly internal method to get query and its params out of subquery object
     * after get() and getAll().
     *
     * @return array
     */
    public function getSubQuery()
    {
        if (!$this->isSubQuery) {
            return null;
        }

        array_shift($this->_bindParams);
        $val = ['query' => $this->_query,
            'params' => $this->_bindParams,
            'alias' => $this->host,
        ];
        $this->reset();

        return $val;
    }

    /* Helper functions */

    /**
     * Method returns generated interval function as a string.
     *
     * @param string $diff interval in the formats:
     *                     "1", "-1d" or "- 1 day" -- For interval - 1 day
     *                     Supported intervals [s]econd, [m]inute, [h]hour, [d]day, [M]onth, [Y]ear
     *                     Default null;
     * @param string $func Initial date
     *
     * @return string
     */
    public function interval($diff, $func = 'NOW()')
    {
        $types = ['s' => 'second', 'm' => 'minute', 'h' => 'hour', 'd' => 'day', 'M' => 'month', 'Y' => 'year'];
        $incr = '+';
        $items = '';
        $type = 'd';

        if ($diff && preg_match('/([+-]?) ?([0-9]+) ?([a-zA-Z]?)/', $diff, $matches)) {
            if (!empty($matches[1])) {
                $incr = $matches[1];
            }

            if (!empty($matches[2])) {
                $items = $matches[2];
            }

            if (!empty($matches[3])) {
                $type = $matches[3];
            }

            if (!in_array($type, array_keys($types))) {
                throw new \Exception("invalid interval type in '{$diff}'");
            }

            $func .= ' '.$incr.' interval '.$items.' '.$types[$type].' ';
        }

        return $func;
    }

    /**
     * Method returns generated interval function as an insert/update function.
     *
     * @param string $diff interval in the formats:
     *                     "1", "-1d" or "- 1 day" -- For interval - 1 day
     *                     Supported intervals [s]econd, [m]inute, [h]hour, [d]day, [M]onth, [Y]ear
     *                     Default null;
     * @param string $func Initial date
     *
     * @return array
     */
    public function now($diff = null, $func = 'NOW()')
    {
        return ['[F]' => [$this->interval($diff, $func)]];
    }

    /**
     * Method generates incremental function call.
     *
     * @param int $num increment by int or float. 1 by default
     *
     * @return array
     *
     * @throws Exception
     */
    public function inc($num = 1)
    {
        if (!is_numeric($num)) {
            throw new \Exception('Argument supplied to inc must be a number');
        }

        return ['[I]' => '+'.$num];
    }

    /**
     * Method generates decrimental function call.
     *
     * @param int $num increment by int or float. 1 by default
     *
     * @return array
     */
    public function dec($num = 1)
    {
        if (!is_numeric($num)) {
            throw new \Exception('Argument supplied to dec must be a number');
        }

        return ['[I]' => '-'.$num];
    }

    /**
     * Method generates change boolean function call.
     *
     * @param string $col column name. null by default
     *
     * @return array
     */
    public function not($col = null)
    {
        return ['[N]' => (string) $col];
    }

    /**
     * Method generates user defined function call.
     *
     * @param string $expr       user function body
     * @param array  $bindParams
     *
     * @return array
     */
    public function func($expr, $bindParams = null)
    {
        return ['[F]' => [$expr, $bindParams]];
    }

    /**
     * Method creates new mysqlidb object for a subquery generation.
     *
     * @param string $subQueryAlias
     *
     * @return MysqliDb
     */
    public static function subQuery($subQueryAlias = '')
    {
        return new self(['host' => $subQueryAlias, 'isSubQuery' => true]);
    }

    /**
     * Method returns a copy of a mysqlidb subquery object.
     *
     * @return MysqliDb new mysqlidb object
     */
    public function copy()
    {
        $copy = unserialize(serialize($this));
        $copy->_mysqli = null;

        return $copy;
    }

    /**
     * Begin a transaction.
     *
     * @uses mysqli->autocommit(false)
     */
    public function startTransaction()
    {
        $this->mysqli()->autocommit(false);
        $this->_transaction_in_progress = true;
        register_shutdown_function([$this, '_transaction_status_check']);
    }

    /**
     * Transaction commit.
     *
     * @uses mysqli->commit();
     * @uses mysqli->autocommit(true);
     */
    public function commit(): bool
    {
        $result = $this->mysqli()->commit();
        $this->_transaction_in_progress = false;
        $this->mysqli()->autocommit(true);

        return $result;
    }

    /**
     * Transaction rollback function.
     *
     * @uses mysqli->rollback();
     * @uses mysqli->autocommit(true);
     */
    public function rollback(): bool
    {
        $result = $this->mysqli()->rollback();
        $this->_transaction_in_progress = false;
        $this->mysqli()->autocommit(true);

        return $result;
    }

    /**
     * Shutdown handler to rollback uncommited operations in order to keep
     * atomic operations sane.
     *
     * @uses mysqli->rollback();
     */
    public function _transaction_status_check()
    {
        if (!$this->_transaction_in_progress) {
            return;
        }
        $this->rollback();
    }

    /**
     * Query exection time tracking switch.
     *
     * @param bool   $enabled     Enable execution time tracking
     * @param string $stripPrefix Prefix to strip from the path in exec log
     *
     * @return MysqliDb
     */
    public function setTrace($enabled, $stripPrefix = null)
    {
        $this->traceEnabled = $enabled;
        $this->traceStripPrefix = $stripPrefix;

        return $this;
    }

    /**
     * Get where and what function was called for query stored in MysqliDB->trace.
     *
     * @return string with information
     */
    private function _traceGetCaller()
    {
        $dd = debug_backtrace();
        $caller = next($dd);
        while (isset($caller) && __FILE__ == $caller['file']) {
            $caller = next($dd);
        }

        return __CLASS__.'->'.$caller['function'].'() >>  file "'.
            str_replace($this->traceStripPrefix, '', $caller['file']).'" line #'.$caller['line'].' ';
    }

    /**
     * Method to get list of tables.
     *
     * @return array
     */
    public function tables()
    {
        $this->where('table_schema', $this->db);

        return array_column($this->get('information_schema.tables', null, ['TABLE_NAME']), 'TABLE_NAME');
    }

    /**
     * Method to check if needed table is created.
     *
     * @param array $tables Table name or an Array of table names to check
     *
     * @return bool True if table exists
     */
    public function tableExists($tables)
    {
        $tables = !is_array($tables) ? [$tables] : $tables;
        $count = count($tables);
        if (0 == $count) {
            return false;
        }

        foreach ($tables as $i => $value) {
            $tables[$i] = $this->prefix.$value;
        }
        $this->where('table_schema', $this->db);
        $this->where('table_name', $tables, 'in');
        $this->get('information_schema.tables', $count);

        return $this->count == $count;
    }

    /**
     * Return result as an associative array with $idField field value used as a record key.
     *
     * Array Returns an array($k => $v) if get(.."param1, param2"), array ($k => array ($v, $v)) otherwise
     *
     * @param string $idField field name to use for a mapped element key
     *
     * @return MysqliDb
     */
    public function map($idField)
    {
        $this->_mapKey = $idField;

        return $this;
    }

    /**
     * Pagination wraper to get().
     *
     * @param string       $table  The name of the database table to work with
     * @param int          $page   Page number
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return array
     */
    public function paginate($table, $page, $fields = null)
    {
        $offset = $this->pageLimit * ($page - 1);
        $res = $this->withTotalCount()->get($table, [$offset, $this->pageLimit], $fields);
        $this->totalPages = ceil($this->totalCount / $this->pageLimit);

        return $res;
    }

    public function getWheres(): array
    {
        return $this->_where;
    }

    public function resetWheres(): void
    {
        $this->_where = [];
    }

    /**
     * Excectue a sql statement and watch for deadlock.
     * In case of dead lock it will try agian up to {MysqliDb::DEADLOCKTRY} times.
     *
     * @return bool returns TRUE on success or FALSE on failure
     *
     * @throws SqlException if sql still occur a deadlock after {MysqliDb::DEADLOCKTRY} tries
     */
    private function executeStmt(\mysqli_stmt $stmt): bool
    {
        $tries = self::DEADLOCKTRY;
        do {
            $status = $stmt->execute();
            --$tries;
        } while (self::DEADLOCK_ERRNO == $stmt->errno and $tries > 0);

        if (self::DEADLOCK_ERRNO == $stmt->errno) {
            throw new SqlException($stmt->error, $stmt->errno);
        }

        return $status;
    }
}

// END class
class parenthesis
{
    protected $_where = [];

    /**
     * @return $this
     */
    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND'): self
    {
        if (is_array($whereValue) && ($key = key($whereValue)) != '0') {
            $operator = $key;
            $whereValue = $whereValue[$key];
        }

        if (0 == count($this->_where)) {
            $cond = '';
        }
        if ('contains' == $operator) {
            $whereValue = '%'.$whereValue.'%';
            $operator = 'LIKE';
        } elseif ('equals' == $operator) {
            $whereValue = $whereValue;
            $operator = '=';
        } elseif ('startswith' == $operator) {
            $whereValue = $whereValue.'%';
            $operator = 'LIKE';
        }
        $this->_where[] = [$cond, $whereProp, $operator, $whereValue];

        return $this;
    }

    /**
     * @return $this
     */
    public function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '='): self
    {
        return $this->where($whereProp, $whereValue, $operator, 'OR');
    }

    public function getWheres(): array
    {
        return $this->_where;
    }

    public function isEmpty(): bool
    {
        return empty($this->_where);
    }
}
