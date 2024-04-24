<?php

namespace packages\base;

use packages\base\db\MysqliDb;

/**
 * @method static MysqliDb jsonBuilder()
 * @method static MysqliDb arrayBuilder()
 * @method static MysqliDb setPrefix(string $prefix = '')
 * @method static array    rawQuery(string $query, ?array $bindParams = null)
 * @method static array    rawQueryOne(string $query, ?array $bindParams = null)
 * @method static mixed    rawQueryValue(string $query, ?array $bindParams = null)
 * @method static array    query(string $query, array|int|null $numRows = null)
 * @method static MysqliDb setQueryOption(string|array $options)
 * @method static MysqliDb withTotalCount()
 * @method static array    get(string $tableName, int|array|null $numRows = null, string|array $columns = '*')
 * @method static array    getOne(string $tableName, string|array $columns = '*')
 * @method static mixed    getValue(string $tableName, string $column, int $limit = 1)
 * @method static bool     insert(string $tableName, array $insertData)
 * @method static bool     insertMulti(string $tableName, array $multiInsertData)
 * @method static bool     replace(string $tableName, array $insertData)
 * @method static bool     has(string $tableName)
 * @method static bool     update(string $tableName, array $tableData, ?int $numRows = null)
 * @method static bool     delete(string $tableName, int|array|null $numRows = null)
 * @method static MysqliDb where(string $whereProp, mixed $whereValue = 'DBNULL', string $operator = '=', string $cond = 'AND')
 * @method static MysqliDb onDuplicate(array $updateColumns, ?string $lastInsertId = null)
 * @method static MysqliDb orWhere(string $whereProp, mixed $whereValue = 'DBNULL', string $operator = '=')
 * @method static MysqliDb having(string $havingProp, mixed $havingValue = 'DBNULL', string $operator = '=', string $cond = 'AND')
 * @method static MysqliDb orHaving(string $havingProp, mixed $havingValue = null, string $operator = null)
 * @method static MysqliDb join(string $joinTable, string $joinCondition, string $joinType = '')
 * @method static MysqliDb joinWhere(string $whereJoin, string $whereProp, mixed $whereValue = 'DBNULL', string $operator = '=', string $cond = 'AND')
 * @method static MysqliDb joinOrWhere(string $whereJoin, string $whereProp, mixed $whereValue = 'DBNULL', string $operator = '=')
 * @method static MysqliDb orderBy(string $orderByField, string $orderbyDirection = "DESC", ?array $customFields = null)
 * @method static MysqliDb groupBy(string $groupByField)
 * @method static int      getInsertId()
 * @method static bool     ping()
 * @method static string   getLastQuery()
 * @method static string   getLastError()
 * @method static array    getSubQuery()
 * @method static string   interval(string $diff, string $func = "NOW()")
 * @method static array    now(string $diff = null, string $func = "NOW()")
 * @method static array    inc(int $num = 1)
 * @method static array    dec(int $num = 1)
 * @method static array    not(?string $col = null)
 * @method static MysqliDb subQuery(string $subQueryAlias = "")
 * @method static void     startTransaction()
 * @method static bool     commit()
 * @method static bool     rollback()
 * @method static void     _transaction_status_check()
 * @method static array    tables()
 * @method static bool     tableExists(string|array $tables)
 * @method static MysqliDb map(string $idField)
 * @method static array    paginate(string $table, int $page, array|string|null $fields = null)
 */
class DB
{
    /** @var array<string,MysqliDb> */
    private static $driver = [];

    public static function connect($conname, $host, $username, $db = null, $password = null, $port = null, $charset = 'utf8mb4'): void
    {
        self::$driver[$conname] = new MysqliDb($host, $username, $password, $db, $port, $charset);
    }

    public static function has_connection($conname = 'default'): bool
    {
        if (!isset(self::$driver[$conname])) {
            Loader::connectdb();
        }

        return isset(self::$driver[$conname]);
    }

    public static function connection($conname = 'default'): MysqliDb|false
    {
        if (self::has_connection($conname)) {
            return self::$driver[$conname];
        }

        return false;
    }

    public static function getConnectionOrFail($conname = 'default'): MysqliDb
    {
        $connection = self::connection($conname);
        if (!$connection) {
            throw new db\DatabaseException("connection [{$conname}] does not exists!");
        }

        return $connection;
    }

    /**
     * Function to get total results count.
     */
    public static function totalCount(): int
    {
        return self::getConnectionOrFail()->totalCount;
    }

    public static function pageLimit($limit): void
    {
        self::getConnectionOrFail()->pageLimit = $limit;
    }

    public static function __callStatic(string $method, array $arguments)
    {
        return call_user_func_array([self::getConnectionOrFail(), $method], $arguments);
    }
}
