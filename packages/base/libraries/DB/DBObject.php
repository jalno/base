<?php

namespace packages\base\DB;

use packages\base\DB;
use packages\base\InputValidationException;
use packages\base\Json;
use packages\base\Loader;
use packages\base\Validator;
use packages\base\Validator\IValidator;

/**
 * Mysqli Model wrapper.
 *
 * @category  Database Access
 *
 * @author	Alexander V. Butenko <a.butenka@gmail.com>
 * @copyright Copyright (c) 2015
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see	  http://github.com/joshcam/PHP-MySQLi-Database-Class
 *
 * @version   2.6-master
 *
 * @method        int         count()
 * @method static int         count()
 * @method        static      ArrayBuilder()
 * @method static static      ArrayBuilder()
 * @method        static      JsonBuilder()
 * @method static static      JsonBuilder()
 * @method        static      ObjectBuilder()
 * @method static static      ObjectBuilder()
 * @method        static|null byId(string $id, mixed $fields = null)
 * @method static static|null byId(string $id, mixed $fields = null)
 * @method        static[]    get(mixed $limit = null, mixed $fields = null)
 * @method static static[]    get(mixed $limit = null, mixed $fields = null)
 * @method        static|null getOne (mixed $fields = null)
 * @method static static|null getOne(mixed $fields = null)
 * @method        mixed       getValue (string $field)
 * @method static mixed       getValue(string $field)
 * @method        mixed       paginate(int $page, array $fields = null)
 * @method static mixed       paginate(int $page, array $fields = null)
 * @method        static      query(string $query, $numRows = null)
 * @method static static      query(string $query, $numRows = null)
 * @method        static[]    rawQuery($query, $bindParams, $sanitize)
 * @method        static[]    static rawQuery($query, $bindParams, $sanitize)
 * @method        static      join(string $objectName, string $key = null, string $joinType = 'LEFT', string $primaryKey = null)
 * @method static static      join(string $objectName, string $key = null, string $joinType = 'LEFT', string $primaryKey = null)
 * @method        static      with(string $objectName, string $type = 'LEFT')
 * @method static static      with(string $objectName, string $type = 'LEFT')
 * @method        static      groupBy(string $groupByField)
 * @method static static      groupBy(string $groupByField)
 * @method        static      orderBy($orderByField, $orderbyDirection = "DESC", $customFields = null)
 * @method static static      orderBy($orderByField, $orderbyDirection = "DESC", $customFields = null)
 * @method        static      where($whereProp, $whereValue = "DBNULL", $operator = "=")
 * @method static static      where($whereProp, $whereValue = "DBNULL", $operator = "=")
 * @method        static      orWhere($whereProp, $whereValue = "DBNULL", $operator = "=")
 * @method static static      orWhere($whereProp, $whereValue = "DBNULL", $operator = "=")
 * @method        static      setQueryOption($options)
 * @method static static      setQueryOption($options)
 * @method        static      setTrace($enabled, $stripPrefix = null)
 * @method static static      setTrace($enabled, $stripPrefix = null)
 * @method        static      withTotalCount()
 * @method static static      withTotalCount()
 * @method        static      startTransaction()
 * @method static static      startTransaction()
 * @method        static      commit()
 * @method static static      commit()
 * @method        static      rollback()
 * @method static static      rollback()
 * @method        static      ping()
 * @method static static      ping()
 * @method        string      getLastError()
 * @method static string      getLastError ()
 * @method        string      getLastQuery()
 * @method static string      getLastQuery()
 *
 * @property int|null $totalCount
 **/
class DBObject implements IValidator
{
    private $connection = 'default';
    /**
     * Working instance of MysqliDb created earlier.
     *
     * @var MysqliDb
     */
    protected $db;
    /**
     * Models path.
     *
     * @var modelPath
     */
    protected static $modelPath;
    /**
     * An array that holds original object data.
     *
     * @var array
     */
    public $original_data = [];
    /**
     * An array that holds object data.
     *
     * @var array
     */
    public $data = [];
    /**
     * Flag to define is object is new or loaded from database.
     *
     * @var bool
     */
    public $isNew = true;
    /**
     * Return type: 'Array' to return results as array, 'Object' as object
     * 'Json' as json string.
     *
     * @var string
     */
    public $returnType = 'Object';
    /**
     * An array that holds has* objects which should be loaded togeather with main
     * object togeather with main object.
     *
     * @var string
     */
    private $_with = [];
    /**
     * Per page limit for pagination.
     *
     * @var int
     */
    public static $pageLimit = 20;
    /**
     * Variable that holds total pages count of last paginate() query.
     *
     * @var int
     */
    public static $totalPages = 0;
    /**
     * An array that holds insert/update/select errors.
     *
     * @var array
     */
    public $errors;
    /**
     * Primary key for an object. 'id' is a default value.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * Table name for an object. Class name will be used by default.
     *
     * @var string
     */
    protected $dbTable;
    /**
     * @param array $data Data to preload on object creation
     */
    protected static $recursivelySerialize = false;

    public function __construct($data = [], $connection = 'default')
    {
        if ('default' == $connection) {
            Loader::requiredb();
        }
        $this->db = DB::connection($connection);
        $this->connection = $connection;
        if (empty($this->dbTable)) {
            $this->dbTable = get_class($this);
        }
        if ($data) {
            $this->original_data = $data;
            if (is_object($data) and $data instanceof DBObject) {
                $this->original_data = $data->data;
            }
            if (is_array($this->original_data) and $this->primaryKey and isset($this->original_data[$this->primaryKey]) and $this->original_data[$this->primaryKey]) {
                $this->isNew = false;
                $this->processArrays($this->original_data);
            }
            $this->data = $this->original_data;
        }
    }

    /**
     * Magic setter function.
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Magic getter function.
     *
     * @param $name Variable name
     */
    public function __get($name)
    {
        if (isset($this->data[$name]) and $this->data[$name] instanceof DBObject) {
            return $this->data[$name];
        }
        if (property_exists($this, 'relations') and isset($this->relations[$name])) {
            $relationType = strtolower($this->relations[$name][0]);
            $modelName = $this->relations[$name][1];
            switch ($relationType) {
                case 'hasone':
                    $key = isset($this->relations[$name][2]) ? $this->relations[$name][2] : $name;
                    if (isset($this->data[$key])) {
                        $obj = new $modelName();
                        $obj->returnType = $this->returnType;

                        return $this->data[$name] = $obj->byId($this->data[$key]);
                    }

                    return null;
                    break;
                case 'hasmany':
                    if (isset($this->data[$this->primaryKey])) {
                        $key = $this->relations[$name][2];
                        $obj = new $modelName();
                        $obj->returnType = $this->returnType;
                        $obj->where($key, $this->data[$this->primaryKey]);
                        $this->data[$name] = $obj->get();
                        if (!$this->data[$name]) {
                            $this->data[$name] = [];
                        }

                        return $this->data[$name];
                    }

                    return [];
                    break;
                default:
                    break;
            }
        }
        if (is_array($this->data) and array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
        $properties = get_class_vars(MysqliDb::class);
        if (array_key_exists($name, $properties)) {
            return $this->db->$name;
        }
    }

    public function __isset($name)
    {
        if (isset($this->data[$name])) {
            return isset($this->data[$name]);
        }
        if (property_exists($this->db, $name)) {
            return isset($this->db->$name);
        }

        return false;
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /**
     * Helper function to create dbObject with Json return type.
     *
     * @return dbObject
     */
    private function JsonBuilder()
    {
        $this->returnType = 'Json';

        return $this;
    }

    /**
     * Helper function to create dbObject with Array return type.
     *
     * @return dbObject
     */
    private function ArrayBuilder()
    {
        $this->returnType = 'Array';

        return $this;
    }

    /**
     * Helper function to create dbObject with Object return type.
     * Added for consistency. Works same way as new $objname ().
     *
     * @return dbObject
     */
    private function ObjectBuilder()
    {
        $this->returnType = 'Object';

        return $this;
    }

    /**
     * Helper function to create a virtual table class.
     *
     * @param string tableName Table name
     *
     * @return dbObject
     */
    public static function table($tableName)
    {
        $tableName = preg_replace('/[^-a-z0-9_]+/i', '', $tableName);
        if (!class_exists($tableName)) {
            eval("class $tableName extends dbObject {}");
        }

        return new $tableName();
    }

    /**
     * @return mixed insert id or false in case of failure
     */
    public function insert()
    {
        if (!empty($this->timestamps) and in_array('createdAt', $this->timestamps)) {
            $this->createdAt = date('Y-m-d H:i:s');
        }
        $sqlData = $this->prepareData();
        if (!$this->validateQueryData($sqlData)) {
            return false;
        }
        $id = $this->db->insert($this->dbTable, $sqlData);
        if (!empty($this->primaryKey) and empty($this->data[$this->primaryKey])) {
            $this->data[$this->primaryKey] = $id;
        }
        $this->isNew = false;
        $this->original_data = $this->data;

        return $id;
    }

    /**
     * @param array $data Optional update data to apply to the object
     */
    public function update($data = null)
    {
        if (empty($this->dbFields)) {
            return false;
        }
        if (empty($this->data[$this->primaryKey])) {
            return false;
        }
        if ($data) {
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }
        }
        if (!empty($this->timestamps) and in_array('updatedAt', $this->timestamps)) {
            $this->updatedAt = date('Y-m-d H:i:s');
        }
        $sqlData = $this->prepareData();
        if (!$this->validateQueryData($sqlData)) {
            return false;
        }

        $newdata = $this->compareData($sqlData, $this->original_data);
        if ($newdata) {
            $this->db->where($this->primaryKey, $this->data[$this->primaryKey]);
            if ($this->db->update($this->dbTable, $newdata)) {
                $this->original_data = $sqlData;

                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * Save or Update object.
     *
     * @return mixed insert id or false in case of failure
     */
    public function save($data = null)
    {
        if ($this->isNew) {
            return $this->insert();
        }

        return $this->update($data);
    }

    /**
     * Delete method. Works only if object primaryKey is defined.
     *
     * @return bool Indicates success. 0 or 1.
     */
    public function delete()
    {
        if (empty($this->data[$this->primaryKey])) {
            return false;
        }
        $this->db->where($this->primaryKey, $this->data[$this->primaryKey]);

        return $this->db->delete($this->dbTable);
    }

    /**
     * Get object by primary key.
     *
     * @param              $id     Primary Key
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return static|null
     */
    protected function byId($id, $fields = null)
    {
        $this->db->where($this->db->prefix.$this->dbTable.'.'.$this->primaryKey, $id);

        return $this->getOne($fields);
    }

    protected function getValue($field)
    {
        $this->processHasOneWith();

        return $this->db->ArrayBuilder()->getValue($this->dbTable, $field);
    }

    /**
     * Convinient function to fetch one object. Mostly will be togeather with where().
     *
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return static|null
     */
    protected function getOne($fields = null)
    {
        $this->processHasOneWith();
        // echo($this->dbTable."\n");
        $results = $this->db->ArrayBuilder()->getOne($this->dbTable, $fields);
        if (0 == $this->db->count) {
            return null;
        }
        $this->processArrays($results);
        $this->data = $results;
        $this->original_data = $results;
        $this->processAllWith($results);
        if ('Json' == $this->returnType) {
            return json\encode($results);
        }
        if ('Array' == $this->returnType) {
            return $results;
        }
        $item = new static ($results);
        $item->isNew = false;

        return $item;
    }

    protected function has()
    {
        $this->processHasOneWith();

        return $this->db->has($this->dbTable);
    }

    /**
     * Fetch all objects.
     *
     * @param int|array    $limit  Array to define SQL limit in format Array ($count, $offset)
     *                             or only $count
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return static[] Array of dbObjects
     */
    protected function get($limit = null, $fields = null)
    {
        $objects = [];
        $this->processHasOneWith();
        $results = $this->db->ArrayBuilder()->get($this->dbTable, $limit, $fields);
        if (0 == $this->db->count) {
            return [];
        }
        foreach ($results as &$r) {
            $this->processArrays($r);
            $this->data = $r;
            $this->original_data = $r;
            $this->processAllWith($r, false);
            if ('Object' == $this->returnType) {
                $item = new static ($r);
                $item->isNew = false;
                $objects[] = $item;
            }
        }
        $this->_with = [];
        if ('Object' == $this->returnType) {
            return $objects;
        }
        if ('Json' == $this->returnType) {
            return Json\encode($results);
        }

        return $results;
    }

    /**
     * Function to set witch hasOne or hasMany objects should be loaded togeather with a main object.
     *
     * @param string $objectName Object Name
     * @param string $type       that can be INNER, LEFT, RIGHT
     *
     * @return DBObject
     */
    private function with($objectName, string $type = 'LEFT')
    {
        if (!property_exists($this, 'relations') and !isset($this->relations[$objectName])) {
            exit("No relation with name $objectName found");
        }

        $relation = $this->relations[$objectName];
        if ('hasone' == strtolower($relation[0])) {
            $relation[3] = $type;
        }
        $this->_with[$objectName] = $relation;

        return $this;
    }

    /**
     * Function to join object with another object.
     *
     * @param string $objectName Object Name
     * @param string $key        Key for a join from primary object
     * @param string $joinType   SQL join type: LEFT, RIGHT,  INNER, OUTER
     * @param string $primaryKey SQL join On Second primaryKey
     *
     * @return BBObject
     */
    private function join($objectName, $key = null, $joinType = 'LEFT', $primaryKey = null)
    {
        if (is_string($objectName)) {
            $joinObj = new $objectName();
        } elseif (is_object($objectName)) {
            $joinObj = $objectName;
        }
        if (!$key) {
            $key = $this->dbTable.'.id';
        }
        if ($primaryKey) {
            if (!strchr($primaryKey, '.')) {
                $primaryKey = $this->db->prefix.$joinObj->dbTable.".{$primaryKey}";
            }
        } else {
            $primaryKey = $this->db->prefix.$joinObj->dbTable.'.'.$joinObj->primaryKey;
        }
        if (!strchr($key, '.')) {
            $joinStr = $this->db->prefix.$this->dbTable.".{$key} = ".$primaryKey;
        } else {
            $joinStr = $this->db->prefix."{$key} = ".$primaryKey;
        }

        $this->db->join($joinObj->dbTable, $joinStr, $joinType);

        return $this;
    }

    /**
     * Function to get a total records count.
     *
     * @return int
     */
    protected function count()
    {
        $res = $this->db->ArrayBuilder()->getValue($this->dbTable, 'count(*)');
        if (!$res) {
            return 0;
        }

        return $res;
    }

    /**
     * Pagination wraper to get().
     *
     * @param int          $page   Page number
     * @param array|string $fields Array or coma separated list of fields to fetch
     *
     * @return array
     */
    private function paginate($page, $fields = null)
    {
        $objects = [];
        $this->db->pageLimit = $this->pageLimit;
        $this->processHasOneWith();
        $results = $this->db->ArrayBuilder()->paginate($this->dbTable, $page, $fields);
        if (0 == $this->db->count) {
            return [];
        }
        self::$totalPages = $this->db->totalPages;
        foreach ($results as &$r) {
            $this->processArrays($r);
            $this->data = $r;
            $this->processAllWith($r, false);
            if ('Object' == $this->returnType) {
                $item = new static ($r);
                $item->isNew = false;
                $objects[] = $item;
            }
        }
        $this->_with = [];
        if ('Object' == $this->returnType) {
            return $objects;
        }
        if ('Json' == $this->returnType) {
            return Json\encode($results);
        }

        return $results;
    }

    /**
     * Catches calls to undefined methods.
     *
     * Provides magic access to private functions of the class and native public mysqlidb functions
     *
     * @param string $method
     */
    public function __call($method, $arg)
    {
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $arg);
        }
        call_user_func_array([$this->db, $method], $arg);

        return $this;
    }

    /**
     * Catches calls to undefined static methods.
     *
     * Transparently creating dbObject class to provide smooth API like name::get() name::orderBy()->get()
     *
     * @param string $method
     */
    public static function __callStatic($method, $arg)
    {
        $obj = new static();
        $result = call_user_func_array([$obj, $method], $arg);
        if (method_exists($obj, $method)) {
            return $result;
        }

        return $obj;
    }

    /**
     * Converts object data to an associative array.
     *
     * @return array Converted data
     */
    public function toArray($recursive = false)
    {
        $data = $this->data;
        $this->processAllWith($data);
        if (!is_array($data)) {
            $data = [];
        }

        /** It's a dummy idea, but works! */
        if (isset($data['userpanel_users'])) {
            unset($data['userpanel_users']['password'], $data['userpanel_users']['remember_token']);
            trigger_error("DBObject:toArray: find 'userpanel_users' index! you should fix this!");
        }

        foreach ($data as $key => $d) {
            if (is_array($d)) {
                foreach ($d as $key2 => $val2) {
                    if ($val2 instanceof DBObject) {
                        $data[$key][$key2] = $val2->toArray($recursive);
                    }
                }
            } elseif (!is_object($d) and isset($this->relations[$key]) and 'hasone' == strtolower($this->relations[$key][0]) and null != $d) {
                $model = new $this->relations[$key][1]();
                $model->where($model->primaryKey, $d);
                $this->data[$key] = $d = $model->getOne() ?? $d;
            }
            if (is_object($d) and $d instanceof DBObject) {
                if ($recursive) {
                    $data[$key] = $d->toArray($recursive);
                } else {
                    $primaryKey = $d->getPrimaryKey();
                    $data[$key] = $d->$primaryKey;
                }
            }
        }

        return $data;
    }

    /**
     * Converts object data to a JSON string.
     *
     * @return string Converted data
     */
    public function toJson()
    {
        return json\encode($this->toArray());
    }

    /**
     * Converts object data to a JSON string.
     *
     * @return string Converted data
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Function queries hasMany relations if needed and also converts hasOne object names.
     *
     * @param array $data
     */
    private function processAllWith(&$data, $shouldReset = true)
    {
        if (0 == count($this->_with)) {
            return;
        }
        foreach ($this->_with as $name => $opts) {
            $relationType = strtolower($opts[0]);
            $modelName = $opts[1];
            if ('hasone' == $relationType) {
                $obj = new $modelName();
                $table = $obj->dbTable;
                $primaryKey = $obj->primaryKey;

                if (!isset($data[$table])) {
                    $data[$name] = $this->$name;
                    continue;
                }
                if (null === $data[$table][$primaryKey]) {
                    $data[$name] = null;
                } else {
                    if ('Object' == $this->returnType) {
                        $item = new $modelName($data[$table]);
                        $item->returnType = $this->returnType;
                        $item->isNew = false;
                        $data[$name] = $item;
                    } else {
                        $data[$name] = $data[$table];
                    }
                }
                unset($data[$table]);
            } else {
                $data[$name] = $this->$name;
            }
        }
        if ($shouldReset) {
            $this->_with = [];
        }
    }

    /*
     * Function building hasOne joins for get/getOne method
     */
    private function processHasOneWith()
    {
        if (0 == count($this->_with)) {
            return;
        }
        foreach ($this->_with as $name => $opts) {
            $relationType = strtolower($opts[0]);
            $modelName = $opts[1];
            $key = null;
            if (isset($opts[2])) {
                $key = $opts[2];
            }
            if ('hasone' == $relationType) {
                $this->db->setQueryOption('MYSQLI_NESTJOIN');
                $this->join($modelName, $key, $opts[3] ?? 'LEFT');
            }
        }
    }

    /**
     * @param array $data
     */
    private function processArrays(&$data)
    {
        if (isset($this->jsonFields) and is_array($this->jsonFields)) {
            foreach ($this->jsonFields as $key) {
                if (!array_key_exists($key, $data)) {
                    continue;
                }
                if (is_string($data[$key])) {
                    $firstChars = substr($data[$key], 0, 1);
                    $lastChar = substr($data[$key], -1);
                    if (
                        ('{' == $firstChars and '}' == $lastChar)
                        or ('[' == $firstChars and ']' == $lastChar)
                    ) {
                        try {
                            $data[$key] = Json\decode($data[$key]);
                        } catch (Json\JsonException $e) {
                            // So we pass data without decoding
                        }
                    }
                }
            }
        }
        if (isset($this->serializeFields) and is_array($this->serializeFields)) {
            foreach ($this->serializeFields as $key) {
                if (isset($data[$key])) {
                    if (is_string($data[$key]) and preg_match('/^(?:(?:a|i|s|C|O|b|d)\:\d+|N;)/', $data[$key])) {
                        $data[$key] = unserialize($data[$key]);
                    }
                } else {
                    $data[$key] = null;
                }
            }
        }
        if (isset($this->arrayFields) and is_array($this->arrayFields)) {
            foreach ($this->arrayFields as $key) {
                if (is_string($data[$key])) {
                    $data[$key] = explode('|', $data[$key]);
                }
            }
        }
    }

    private function compareData($new, $old)
    {
        $return = [];
        foreach ($new as $key => $value) {
            if (array_key_exists($key, $old) and $old[$key] instanceof self) {
                $pkey = $old[$key]->getPrimaryKey();
                $old[$key] = $old[$key]->$pkey;
            }
            if (!array_key_exists($key, $old) or $value != $old[$key]) {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * @param array $data
     */
    private function validateQueryData($data)
    {
        if (!$this->dbFields) {
            return true;
        }
        $dbFields = $this->dbFields;
        if (!isset($dbFields[$this->primaryKey])) {
            $dbFields[$this->primaryKey] = [
                'type' => 'int',
            ];
        }
        foreach ($dbFields as $key => $desc) {
            $type = isset($desc['type']) ? $desc['type'] : null;
            $required = (isset($desc['required']) and $desc['required']);
            $unique = (isset($desc['unique']) and $desc['unique']);
            $value = isset($data[$key]) ? $data[$key] : null;

            if (is_array($value)) {
                continue;
            }
            if ($required and null === $value) {
                throw new InputRequired($key);
            }
            if ($unique and !empty($value)) {
                if ($this->primaryKey != $key and isset($this->data[$this->primaryKey])) {
                    $this->db->where($this->primaryKey, $this->data[$this->primaryKey], '!=');
                }
                $this->db->where($key, $value);
                if ($this->db->has($this->dbTable)) {
                    throw new DuplicateRecord($key);
                }
            }
            if (null == $value) {
                continue;
            }
            switch ($type) {
                case 'text':
                    $regexp = null;
                    break;
                case 'int':
                    $regexp = '/^-?[0-9]*$/';
                    break;
                case 'double':
                    $regexp = "/^-?[0-9\.]+(?:E-[0-9]+)?$/";
                    break;
                case 'bool':
                    $regexp = '/^[yes|no|0|1|true|false]$/i';
                    break;
                case 'datetime':
                    $regexp = '/^[0-9a-zA-Z -:]*$/';
                    break;
                default:
                    $regexp = $type;
                    break;
            }
            if (!$regexp) {
                continue;
            }
            if (!preg_match($regexp, $value)) {
                throw new InputDataType($key);
            }
        }

        return true;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    private function prepareData()
    {
        $this->errors = [];
        $sqlData = [];
        if (0 == count($this->data)) {
            return [];
        }
        if (method_exists($this, 'preLoad')) {
            $this->data = $this->preLoad($this->data);
        }
        if (!$this->dbFields) {
            return $this->data;
        }
        foreach ($this->data as $key => $value) {
            if (!in_array($key, array_keys($this->dbFields)) and $this->primaryKey != $key) {
                continue;
            }
            if (is_object($value) and $value instanceof dbObject and true == $value->isNew and isset($this->relations[$key])) {
                $id = $value->save();
                if ($id) {
                    $value = $id;
                } else {
                    $this->errors = array_merge($this->errors, $value->errors);
                }
            }
            if (property_exists($this, 'jsonFields') and in_array($key, $this->jsonFields)) {
                if (is_array($value) or is_object($value)) {
                    if ($value instanceof dbObject) {
                        $value = $value->toArray($value->isNew);
                    }
                    $sqlData[$key] = json\encode($value);
                } else {
                    $sqlData[$key] = $value;
                }
            } elseif (property_exists($this, 'serializeFields') and in_array($key, $this->serializeFields) and (is_array($value) or is_object($value))) {
                $sqlData[$key] = serialize($value);
            } else {
                if (is_object($value) and $value instanceof dbObject and true == !$value->isNew) {
                    $pkey = $value->getPrimaryKey();
                    $value = $value->$pkey;
                }
                $sqlData[$key] = $value;
            }
        }

        return $sqlData;
    }

    public static function objectToArray($array, $recursive = false)
    {
        $return = [];
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if (is_object($val) and $val instanceof dbObject) {
                    $return[$key] = $val->toArray($recursive);
                } else {
                    $return[$key] = $val;
                }
            }
        }

        return $return;
    }

    public function getFields()
    {
        return property_exists($this, 'dbFields') ? $this->dbFields : [];
    }

    public function getRelations()
    {
        return property_exists($this, 'relations') ? $this->relations : [];
    }

    public function __serialize(): array
    {
        $result = [];
        if ('default' != $this->connection) {
            $result['@connection'] = $this->connection;
        }
        if (self::$recursivelySerialize) {
            $fields = $this->getFields();
            $relations = $this->getRelations();
            $jsonFields = property_exists($this, 'jsonFields') ? $this->jsonFields : [];
            $arrayFields = property_exists($this, 'arrayFields') ? $this->arrayFields : [];
            $serializeFields = property_exists($this, 'serializeFields') ? $this->serializeFields : [];
            $arrayFields = array_merge($arrayFields, $jsonFields, $serializeFields);
            foreach ($this->data as $key => $value) {
                if (!is_array($value) or (isset($fields[$key]) and in_array($key, $arrayFields)) or (isset($relations[$key]) and 'hasone' == strtolower($relations[$key][0]))) {
                    $result[$key] = $value;
                }
            }
        } else {
            $result = array_merge($result, $this->toArray(false));
        }

        return $result;
    }

    public function __unserialize(array $data): void
    {
        $this->connection = isset($data['@connection']) ? $data['@connection'] : 'default';
        if ('default' == $this->connection) {
            Loader::requiredb();
        }
        $this->db = DB::connection($this->connection);

        if ($this->primaryKey and isset($data[$this->primaryKey]) and $data[$this->primaryKey]) {
            $this->isNew = false;
        }
        $this->data = $data;
        $this->original_data = $data;
    }

    /**
     * Get alias types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return [];
    }

    /**
     * Validate data to be a boolean value.
     *
     * @return packages\base\db\dbObject new value, if needed
     *
     * @throws packages\base\InputValidationException
     */
    public function validate(string $input, array $rule, $data)
    {
        if (!is_string($data) and !is_numeric($data)) {
            throw new InputValidationException($input);
        }
        if (!$data and isset($rule['empty']) and $rule['empty']) {
            return new Validator\NullValue();
        }
        $this->db->where($this->db->prefix.$this->dbTable.'.'.$this->primaryKey, $data);
        if (isset($rule['query'])) {
            $rule['query']($this);
        }
        $obj = $this->getOne($rule['fileds'] ?? null);
        if (!$obj) {
            throw new InputValidationException($input);
        }

        return $obj;
    }
}
