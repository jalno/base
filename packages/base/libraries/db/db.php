<?php
namespace packages\base;
use \packages\base\db\MysqliDb;
class db{
	private static $driver = array();
	static function connect($conname, $host, $username, $db = null,$password = null,$port = null, $charset = 'utf8'){
		self::$driver[$conname] = new MysqliDb($host, $username, $password, $db, $port, $charset);
	}
	static function has_connection($conname = 'default'){
		$connected = isset(self::$driver[$conname]);
		if(!$connected){
			$connected = loader::connectdb();
		}
		return $connected;
	}
	static function connection($conname = 'default'){
		if(self::has_connection($conname)){
			return self::$driver[$conname];
		}
		return false;
	}
	/**
	 * Helper function to create dbObject with JSON return type
	 *
	 */
	static function jsonBuilder(){
		if(self::has_connection()){
			return self::connection()->jsonBuilder();
		}
	}

	/**
	 * Helper function to create dbObject with array return type
	 * Added for consistency as thats default output type
	 *
	 */
	static function arrayBuilder(){
		if(self::has_connection()){
			return self::connection()->arrayBuilder();
		}
	}

	/**
	 * Method to set a prefix
	 *
	 * @param string $prefix	 Contains a tableprefix
	 *
	 */
	static function setPrefix($prefix = ''){
		if(self::has_connection()){
			return self::connection()->setPrefix($prefix);
		}
	}

	/**
	 * Execute raw SQL query.
	 *
	 * @param string $query	  User-provided query to execute.
	 * @param array  $bindParams Variables array to bind to the SQL statement.
	 *
	 * @return array Contains the returned rows from the query.
	 */
	static function rawQuery($query, $bindParams = null){
		if(self::has_connection()){
			return self::connection()->rawQuery($query, $bindParams);
		}
	}

	/**
	 * Helper function to execute raw SQL query and return only 1 row of results.
	 * Note that function do not add 'limit 1' to the query by itself
	 * Same idea as getOne()
	 *
	 * @param string $query	  User-provided query to execute.
	 * @param array  $bindParams Variables array to bind to the SQL statement.
	 *
	 * @return array|null Contains the returned row from the query.
	 */
	static function rawQueryOne($query, $bindParams = null){
		if(self::has_connection()){
			return self::connection()->rawQueryOne($query, $bindParams);
		}
	}
	/**
	 * Helper function to execute raw SQL query and return only 1 column of results.
	 * If 'limit 1' will be found, then string will be returned instead of array
	 * Same idea as getValue()
	 *
	 * @param string $query	  User-provided query to execute.
	 * @param array  $bindParams Variables array to bind to the SQL statement.
	 *
	 * @return mixed Contains the returned rows from the query.
	 */
	static function rawQueryValue($query, $bindParams = null){
		if(self::has_connection()){
			return self::connection()->rawQueryValue($query, $bindParams);
		}
	}

	/**
	 * A method to perform select query
	 *
	 * @param string $query   Contains a user-provided select query.
	 * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
	 *
	 * @return array Contains the returned rows from the query.
	 */
	static function query($query, $numRows = null){
		if(self::has_connection()){
			return self::connection()->query($query, $numRows);
		}
	}

	/**
	 * This method allows you to specify multiple (method chaining optional) options for SQL queries.
	 *
	 * @uses $MySqliDb->setQueryOption('name');
	 *
	 * @param string|array $options The optons name of the query.
	 *
	 * @throws Exception
	 * @return MysqliDb
	 */
	static function setQueryOption($options){
		if(self::has_connection()){
			return self::connection()->setQueryOption($options);
		}
	}

	/**
	 * Function to enable SQL_CALC_FOUND_ROWS in the get queries
	 *
	 * @return MysqliDb
	 */
	static function withTotalCount(){
		if(self::has_connection()){
			return self::connection()->withTotalCount();
		}
	}

	/**
	 * Function to get total results count
	 *
	 * @return int
	 */
	static function totalCount(){
		if(self::has_connection()){
			return self::connection()->totalCount;
		}
	}

	/**
	 * A convenient SELECT * function.
	 *
	 * @param string  $tableName The name of the database table to work with.
	 * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
	 *							   or only $count
	 * @param string $columns Desired columns
	 *
	 * @return array Contains the returned rows from the select query.
	 */
	static function get($tableName, $numRows = null, $columns = '*'){
		if(self::has_connection()){
			return self::connection()->get($tableName, $numRows, $columns);
		}
	}

	/**
	 * A convenient SELECT * function to get one record.
	 *
	 * @param string  $tableName The name of the database table to work with.
	 * @param string  $columns Desired columns
	 *
	 * @return array Contains the returned rows from the select query.
	 */
	static function getOne($tableName, $columns = '*'){
		if(self::has_connection()){
			return self::connection()->getOne($tableName, $columns);
		}
	}

	/**
	 * A convenient SELECT COLUMN function to get a single column value from one row
	 *
	 * @param string  $tableName The name of the database table to work with.
	 * @param string  $column	The desired column
	 * @param int	 $limit	 Limit of rows to select. Use null for unlimited..1 by default
	 *
	 * @return mixed Contains the value of a returned column / array of values
	 */
	static function getValue($tableName, $column, $limit = 1){
		if(self::has_connection()){
			return self::connection()->getValue($tableName, $column, $limit);
		}
	}

	/**
	 * Insert method to add new row
	 *
	 * @param string $tableName The name of the table.
	 * @param array $insertData Data containing information for inserting into the DB.
	 *
	 * @return bool Boolean indicating whether the insert query was completed succesfully.
	 */
	static function insert($tableName, $insertData){
		if(self::has_connection()){
			return self::connection()->insert($tableName, $insertData);
		}
	}

	/**
	 * Replace method to add new row
	 *
	 * @param string $tableName The name of the table.
	 * @param array $insertData Data containing information for inserting into the DB.
	 *
	 * @return bool Boolean indicating whether the insert query was completed succesfully.
	 */
	static function replace($tableName, $insertData){
		if(self::has_connection()){
			return self::connection()->replace($tableName, $insertData);
		}
	}

	/**
	 * A convenient function that returns TRUE if exists at least an element that
	 * satisfy the where condition specified calling the "where" method before this one.
	 *
	 * @param string  $tableName The name of the database table to work with.
	 *
	 * @return array Contains the returned rows from the select query.
	 */
	static function has($tableName){
		if(self::has_connection()){
			return self::connection()->has($tableName);
		}
	}

	/**
	 * Update query. Be sure to first call the "where" method.
	 *
	 * @param string $tableName The name of the database table to work with.
	 * @param array  $tableData Array of data to update the desired row.
	 * @param int	$numRows   Limit on the number of rows that can be updated.
	 *
	 * @return bool
	 */
	static function update($tableName, $tableData, $numRows = null){
		if(self::has_connection()){
			return self::connection()->update($tableName, $tableData, $numRows);
		}
	}

	/**
	 * Delete query. Call the "where" method first.
	 *
	 * @param string  $tableName The name of the database table to work with.
	 * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
	 *							   or only $count
	 *
	 * @return bool Indicates success. 0 or 1.
	 */
	static function delete($tableName, $numRows = null){
		if(self::has_connection()){
			return self::connection()->delete($tableName, $numRows);
		}
	}

	/**
	 * This method allows you to specify multiple (method chaining optional) AND WHERE statements for SQL queries.
	 *
	 * @uses $MySqliDb->where('id', 7)->where('title', 'MyTitle');
	 *
	 * @param string $whereProp  The name of the database field.
	 * @param mixed  $whereValue The value of the database field.
	 * @param string $operator Comparison operator. Default is =
	 * @param string $cond Condition of where statement (OR, AND)
	 *
	 * @return MysqliDb
	 */
	static function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND'){
		if(self::has_connection()){
			return self::connection()->where($whereProp, $whereValue, $operator, $cond);
		}
	}

	/**
	 * This function store update column's name and column name of the
	 * autoincrement column
	 *
	 * @param array $updateColumns Variable with values
	 * @param string $lastInsertId Variable value
	 *
	 * @return MysqliDb
	 */
	static function onDuplicate($updateColumns, $lastInsertId = null){
		if(self::has_connection()){
			return self::connection()->onDuplicate($updateColumns, $lastInsertId);
		}
	}

	/**
	 * This method allows you to specify multiple (method chaining optional) OR WHERE statements for SQL queries.
	 *
	 * @uses $MySqliDb->orWhere('id', 7)->orWhere('title', 'MyTitle');
	 *
	 * @param string $whereProp  The name of the database field.
	 * @param mixed  $whereValue The value of the database field.
	 * @param string $operator Comparison operator. Default is =
	 *
	 * @return MysqliDb
	 */
	static function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '='){
		if(self::has_connection()){
			return self::connection()->orWhere($whereProp, $whereValue, $operator);
		}
	}

	/**
	 * This method allows you to specify multiple (method chaining optional) AND HAVING statements for SQL queries.
	 *
	 * @uses $MySqliDb->having('SUM(tags) > 10')
	 *
	 * @param string $havingProp  The name of the database field.
	 * @param mixed  $havingValue The value of the database field.
	 * @param string $operator Comparison operator. Default is =
	 *
	 * @return MysqliDb
	 */

	static function having($havingProp, $havingValue = 'DBNULL', $operator = '=', $cond = 'AND'){
		if(self::has_connection()){
			return self::connection()->having($havingProp, $havingValue, $operator, $cond);
		}
	}

	/**
	 * This method allows you to specify multiple (method chaining optional) OR HAVING statements for SQL queries.
	 *
	 * @uses $MySqliDb->orHaving('SUM(tags) > 10')
	 *
	 * @param string $havingProp  The name of the database field.
	 * @param mixed  $havingValue The value of the database field.
	 * @param string $operator Comparison operator. Default is =
	 *
	 * @return MysqliDb
	 */
	static function orHaving($havingProp, $havingValue = null, $operator = null){
		if(self::has_connection()){
			return self::connection()->orHaving($havingProp, $havingValue, $operator);
		}
	}

	/**
	 * This method allows you to concatenate joins for the final SQL statement.
	 *
	 * @uses $MySqliDb->join('table1', 'field1 <> field2', 'LEFT')
	 *
	 * @param string $joinTable The name of the table.
	 * @param string $joinCondition the condition.
	 * @param string $joinType 'LEFT', 'INNER' etc.
	 *
	 * @throws Exception
	 * @return MysqliDb
	 */
	static function join($joinTable, $joinCondition, $joinType = ''){
		if(self::has_connection()){
			return self::connection()->join($joinTable, $joinCondition, $joinType);
		}
	}
	/**
     * This method allows you to specify multiple (method chaining optional) AND WHERE statements for the join table on part of the SQL query.
     *
     * @uses $dbWrapper->joinWhere('user u', 'u.id', 7)->where('user u', 'u.title', 'MyTitle');
     *
     * @param string $whereJoin  The name of the table followed by its prefix.
     * @param string $whereProp  The name of the database field.
     * @param mixed  $whereValue The value of the database field.
     *
     * @return MysqliDb
     */
    public static function joinWhere($whereJoin, $whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
		if(self::has_connection()){
			return self::connection()->joinWhere($whereJoin, $whereProp, $whereValue, $operator, $cond);
		}
    }

    /**
     * This method allows you to specify multiple (method chaining optional) OR WHERE statements for the join table on part of the SQL query.
     *
     * @uses $dbWrapper->joinWhere('user u', 'u.id', 7)->where('user u', 'u.title', 'MyTitle');
     *
     * @param string $whereJoin  The name of the table followed by its prefix.
     * @param string $whereProp  The name of the database field.
     * @param mixed  $whereValue The value of the database field.
     *
     * @return MysqliDb
     */
    public static function joinOrWhere($whereJoin, $whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
		if(self::has_connection()){
			return self::connection()->joinOrWhere($whereJoin, $whereProp, $whereValue, $operator);
		}
    }
	/**
	 * This method allows you to specify multiple (method chaining optional) ORDER BY statements for SQL queries.
	 *
	 * @uses $MySqliDb->orderBy('id', 'desc')->orderBy('name', 'desc');
	 *
	 * @param string $orderByField The name of the database field.
	 * @param string $orderByDirection Order direction.
	 * @param array $customFields Fieldset for ORDER BY FIELD() ordering
	 *
	 * @throws Exception
	 * @return MysqliDb
	 */
	static function orderBy($orderByField, $orderbyDirection = "DESC", $customFields = null){
		if(self::has_connection()){
			return self::connection()->orderBy($orderByField, $orderbyDirection, $customFields);
		}
	}

	/**
	 * This method allows you to specify multiple (method chaining optional) GROUP BY statements for SQL queries.
	 *
	 * @uses $MySqliDb->groupBy('name');
	 *
	 * @param string $groupByField The name of the database field.
	 *
	 * @return MysqliDb
	 */
	static function groupBy($groupByField){
		if(self::has_connection()){
			return self::connection()->groupBy($groupByField);
		}
	}

	/**
	 * This methods returns the ID of the last inserted item
	 *
	 * @return int The last inserted item ID.
	 */
	static function getInsertId(){
		if(self::has_connection()){
			return self::connection()->getInsertId();
		}
	}

	/**
	 * Escape harmful characters which might affect a query.
	 *
	 * @param string $str The string to escape.
	 *
	 * @return string The escaped string.
	 */
	static function escape($str){
		if(self::has_connection()){
			return self::connection()->escape($str);
		}
	}

	/**
	 * Method to call mysqli->ping() to keep unused connections open on
	 * long-running scripts, or to reconnect timed out connections (if php.ini has
	 * global mysqli.reconnect set to true). Can't do this directly using object
	 * since _mysqli is protected.
	 *
	 * @return bool True if connection is up
	 */
	static function ping(){
		if(self::has_connection()){
			return self::connection()->ping();
		}
	}

	/**
	 * Method returns last executed query
	 *
	 * @return string
	 */
	static function getLastQuery(){
		if(self::has_connection()){
			return self::connection()->getLastQuery();
		}
	}

	/**
	 * Method returns mysql error
	 *
	 * @return string
	 */
	static function getLastError(){
		if(self::has_connection()){
			return self::connection()->getLastError();
		}
	}

	/**
	 * Method returns mysql error code
	 * @return int
	 */
	static function getLastErrno () {
		if(self::has_connection()){
			return self::connection()->getLastErrno();
		}
	}

	/**
	 * Mostly internal method to get query and its params out of subquery object
	 * after get() and getAll()
	 *
	 * @return array
	 */
	static function getSubQuery(){
		if(self::has_connection()){
			return self::connection()->getSubQuery();
		}
	}

	/* Helper functions */

	/**
	 * Method returns generated interval function as a string
	 *
	 * @param string $diff interval in the formats:
	 *		"1", "-1d" or "- 1 day" -- For interval - 1 day
	 *		Supported intervals [s]econd, [m]inute, [h]hour, [d]day, [M]onth, [Y]ear
	 *		Default null;
	 * @param string $func Initial date
	 *
	 * @return string
	 */
	static function interval($diff, $func = "NOW()"){
		if(self::has_connection()){
			return self::connection()->interval($diff, $func);
		}
	}

	/**
	 * Method returns generated interval function as an insert/update function
	 *
	 * @param string $diff interval in the formats:
	 *		"1", "-1d" or "- 1 day" -- For interval - 1 day
	 *		Supported intervals [s]econd, [m]inute, [h]hour, [d]day, [M]onth, [Y]ear
	 *		Default null;
	 * @param string $func Initial date
	 *
	 * @return array
	 */
	static function now($diff = null, $func = "NOW()"){
		if(self::has_connection()){
			return self::connection()->now($diff, $func);
		}
	}

	/**
	 * Method generates incremental function call
	 *
	 * @param int $num increment by int or float. 1 by default
	 *
	 * @throws Exception
	 * @return array
	 */
	static function inc($num = 1){
		if(self::has_connection()){
			return self::connection()->inc($num);
		}
	}

	/**
	 * Method generates decrimental function call
	 *
	 * @param int $num increment by int or float. 1 by default
	 *
	 * @return array
	 */
	static function dec($num = 1){
		if(self::has_connection()){
			return self::connection()->dec($num);
		}
	}

	/**
	 * Method generates change boolean function call
	 *
	 * @param string $col column name. null by default
	 *
	 * @return array
	 */
	static function not($col = null){
		if(self::has_connection()){
			return self::connection()->not($col);
		}
	}

	/**
	 * Method generates user defined function call
	 *
	 * @param string $expr user function body
	 * @param array $bindParams
	 *
	 * @return array
	 */
	static function func($expr, $bindParams = null){
		if(self::has_connection()){
			return self::connection()->func($expr, $bindParams);
		}
	}

	/**
	 * Method creates new mysqlidb object for a subquery generation
	 *
	 * @param string $subQueryAlias
	 *
	 * @return MysqliDb
	 */
	static function subQuery($subQueryAlias = ""){
		if(self::has_connection()){
			return self::connection()->subQuery($subQueryAlias);
		}
	}

	/**
	 * Method returns a copy of a mysqlidb subquery object
	 *
	 * @return MysqliDb new mysqlidb object
	 */
	static function copy(){
		if(self::has_connection()){
			return self::connection()->copy();
		}
	}

	/**
	 * Begin a transaction
	 *
	 * @uses mysqli->autocommit(false)
	 * @uses register_shutdown_function(array($this, "_transaction_shutdown_check"))
	 */
	static function startTransaction(){
		if(self::has_connection()){
			return self::connection()->startTransaction();
		}
	}

	/**
	 * Transaction commit
	 *
	 * @uses mysqli->commit();
	 * @uses mysqli->autocommit(true);
	 */
	static function commit(){
	   	if(self::has_connection()){
			return self::connection()->commit();
		}
	}

	/**
	 * Transaction rollback function
	 *
	 * @uses mysqli->rollback();
	 * @uses mysqli->autocommit(true);
	 */
	static function rollback(){
		if(self::has_connection()){
			return self::connection()->rollback();
		}
	}

	/**
	 * Shutdown handler to rollback uncommited operations in order to keep
	 * atomic operations sane.
	 *
	 * @uses mysqli->rollback();
	 */
	static function _transaction_status_check(){
		if(self::has_connection()){
			return self::connection()->_transaction_status_check();
		}
	}

	/**
	 * Query exection time tracking switch
	 *
	 * @param bool $enabled Enable execution time tracking
	 * @param string $stripPrefix Prefix to strip from the path in exec log
	 *
	 * @return MysqliDb
	 */
	static function setTrace($enabled, $stripPrefix = null){
		if(self::has_connection()){
			return self::connection()->setTrace($enabled, $stripPrefix);
		}
	}
	/**
	 * Method to check if needed table is created
	 *
	 * @param array $tables Table name or an Array of table names to check
	 *
	 * @return bool True if table exists
	 */
	static function tableExists($tables){
		if(self::has_connection()){
			return self::connection()->tableExists($tables);
		}
	}

	/**
	 * Return result as an associative array with $idField field value used as a record key
	 *
	 * Array Returns an array($k => $v) if get(.."param1, param2"), array ($k => array ($v, $v)) otherwise
	 *
	 * @param string $idField field name to use for a mapped element key
	 *
	 * @return MysqliDb
	 */
	static function map($idField){
		if(self::has_connection()){
			return self::connection()->map($idField);
		}
	}

	static function pageLimit ($limit) {
		if(self::has_connection()){
			return self::connection()->pageLimit = $limit;
		}
	}
	/**
	 * Pagination wraper to get()
	 *
	 * @access public
	 * @param string  $table The name of the database table to work with
	 * @param int $page Page number
	 * @param array|string $fields Array or coma separated list of fields to fetch
	 * @return array
	 */
	static function paginate ($table, $page, $fields = null) {
		if(self::has_connection()){
			return self::connection()->paginate($table,$page, $fields);
		}
	}
}
?>
