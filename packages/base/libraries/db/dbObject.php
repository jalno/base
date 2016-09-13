<?php
namespace packages\base\db;
use \packages\base\loader;
use \packages\base\db;
use \packages\base\json;
/**
 * Mysqli Model wrapper
 *
 * @category  Database Access
 * @package   MysqliDb
 * @author	Alexander V. Butenko <a.butenka@gmail.com>
 * @copyright Copyright (c) 2015
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link	  http://github.com/joshcam/PHP-MySQLi-Database-Class
 * @version   2.6-master
 *
 * @method int count ()
 * @method dbObject ArrayBuilder()
 * @method dbObject JsonBuilder()
 * @method dbObject ObjectBuilder()
 * @method mixed byId (string $id, mixed $fields)
 * @method mixed get (mixed $limit, mixed $fields)
 * @method mixed getOne (mixed $fields)
 * @method mixed paginate (int $page, array $fields)
 * @method dbObject query ($query, $numRows)
 * @method dbObject rawQuery ($query, $bindParams, $sanitize)
 * @method dbObject join (string $objectName, string $key, string $joinType, string $primaryKey)
 * @method dbObject with (string $objectName)
 * @method dbObject groupBy (string $groupByField)
 * @method dbObject orderBy ($orderByField, $orderbyDirection, $customFields)
 * @method dbObject where ($whereProp, $whereValue, $operator)
 * @method dbObject orWhere ($whereProp, $whereValue, $operator)
 * @method dbObject setQueryOption ($options)
 * @method dbObject setTrace ($enabled, $stripPrefix)
 * @method dbObject withTotalCount ()
 * @method dbObject startTransaction ()
 * @method dbObject commit ()
 * @method dbObject rollback ()
 * @method dbObject ping ()
 * @method string getLastError ()
 * @method string getLastQuery ()
 **/
class dbObject {
	/**
	 * Working instance of MysqliDb created earlier
	 *
	 * @var MysqliDb
	 */
	private $db;
	/**
	 * Models path
	 *
	 * @var modelPath
	 */
	protected static $modelPath;
	/**
	 * An array that holds object data
	 *
	 * @var array
	 */
	public $data;
	/**
	 * Flag to define is object is new or loaded from database
	 *
	 * @var boolean
	 */
	public $isNew = true;
	/**
	 * Return type: 'Array' to return results as array, 'Object' as object
	 * 'Json' as json string
	 *
	 * @var string
	 */
	public $returnType = 'Object';
	/**
	 * An array that holds has* objects which should be loaded togeather with main
	 * object togeather with main object
	 *
	 * @var string
	 */
	private $_with = array();
	/**
	 * Per page limit for pagination
	 *
	 * @var int
	 */
	public static $pageLimit = 20;
	/**
	 * Variable that holds total pages count of last paginate() query
	 *
	 * @var int
	 */
	public static $totalPages = 0;
	/**
	 * An array that holds insert/update/select errors
	 *
	 * @var array
	 */
	public $errors = null;
	/**
	 * Primary key for an object. 'id' is a default value.
	 *
	 * @var stating
	 */
	protected $primaryKey = 'id';
	/**
	 * Table name for an object. Class name will be used by default
	 *
	 * @var stating
	 */
	protected $dbTable;
	/**
	 * @param array $data Data to preload on object creation
	 */
	public function __construct ($data = null, $connection = 'default') {
		if($connection == 'default'){
    		loader::requiredb();
		}
		$this->db = db::connection($connection);;
		if (empty ($this->dbTable))
			$this->dbTable = get_class ($this);
		if ($data){
			if(is_object($data) and $data instanceof dbObject){
				$data = $data->toArray();
			}
			if(is_array($data) and $this->primaryKey and isset($data[$this->primaryKey]) and $data[$this->primaryKey]){
				$this->isNew = false;
			}
			$this->data = $data;
		}
	}
	/**
	 * Magic setter function
	 *
	 * @return mixed
	 */
	public function __set ($name, $value) {
		$this->data[$name] = $value;
	}
	/**
	 * Magic getter function
	 *
	 * @param $name Variable name
	 *
	 * @return mixed
	 */
	public function __get ($name) {
		if (isset ($this->data[$name]) && $this->data[$name] instanceof dbObject)
			return $this->data[$name];
		if (property_exists ($this, 'relations') && isset ($this->relations[$name])) {
			$relationType = strtolower ($this->relations[$name][0]);
			$modelName = $this->relations[$name][1];
			switch ($relationType) {
				case 'hasone':
					$key = isset ($this->relations[$name][2]) ? $this->relations[$name][2] : $name;
					$obj = new $modelName;
					$obj->returnType = $this->returnType;
					return $this->data[$name] = $obj->byId($this->data[$key]);
					break;
				case 'hasmany':
					$key = $this->relations[$name][2];
					$obj = new $modelName;
					$obj->returnType = $this->returnType;
					$this->data[$name] = $obj->where($key, $this->data[$this->primaryKey])->get();
					if(!$this->data[$name]){
						$this->data[$name] = array();
					}
					return $this->data[$name];
					break;
				default:
					break;
			}
		}
		if (isset ($this->data[$name])) {
			return $this->data[$name];
		}
		if (property_exists ($this->db, $name))
			return $this->db->$name;
	}
	public function __isset ($name) {
		if (isset ($this->data[$name]))
			return isset ($this->data[$name]);
		if (property_exists ($this->db, $name))
			return isset ($this->db->$name);
	}
	public function __unset ($name) {
		unset ($this->data[$name]);
	}
	/**
	 * Helper function to create dbObject with Json return type
	 *
	 * @return dbObject
	 */
	private function JsonBuilder () {
		$this->returnType = 'Json';
		return $this;
	}
	/**
	 * Helper function to create dbObject with Array return type
	 *
	 * @return dbObject
	 */
	private function ArrayBuilder () {
		$this->returnType = 'Array';
		return $this;
	}
	/**
	 * Helper function to create dbObject with Object return type.
	 * Added for consistency. Works same way as new $objname ()
	 *
	 * @return dbObject
	 */
	private function ObjectBuilder () {
		$this->returnType = 'Object';
		return $this;
	}
	/**
	 * Helper function to create a virtual table class
	 *
	 * @param string tableName Table name
	 * @return dbObject
	 */
	public static function table ($tableName) {
		$tableName = preg_replace ("/[^-a-z0-9_]+/i",'', $tableName);
		if (!class_exists ($tableName))
			eval ("class $tableName extends dbObject {}");
		return new $tableName ();
	}
	/**
	 * @return mixed insert id or false in case of failure
	 */
	public function insert () {
		if (!empty ($this->timestamps) && in_array ("createdAt", $this->timestamps))
			$this->createdAt = date("Y-m-d H:i:s");
		$sqlData = $this->prepareData ();
		if (!$this->validate ($sqlData))
			return false;
		$id = $this->db->insert ($this->dbTable, $sqlData);
		if (!empty ($this->primaryKey) && empty ($this->data[$this->primaryKey]))
			$this->data[$this->primaryKey] = $id;
		$this->isNew = false;
		return $id;
	}
	/**
	 * @param array $data Optional update data to apply to the object
	 */
	public function update ($data = null) {
		if (empty ($this->dbFields))
			return false;
		if (empty ($this->data[$this->primaryKey]))
			return false;
		if ($data) {
			foreach ($data as $k => $v)
				$this->$k = $v;
		}
		if (!empty ($this->timestamps) && in_array ("updatedAt", $this->timestamps))
			$this->updatedAt = date("Y-m-d H:i:s");
		$sqlData = $this->prepareData ();
		if (!$this->validate ($sqlData))
			return false;
		$this->db->where ($this->primaryKey, $this->data[$this->primaryKey]);
		return $this->db->update ($this->dbTable, $sqlData);
	}
	/**
	 * Save or Update object
	 *
	 * @return mixed insert id or false in case of failure
	 */
	public function save ($data = null) {
		if ($this->isNew)
			return $this->insert();
		return $this->update ($data);
	}
	/**
	 * Delete method. Works only if object primaryKey is defined
	 *
	 * @return boolean Indicates success. 0 or 1.
	 */
	public function delete () {
		if (empty ($this->data[$this->primaryKey]))
			return false;
		$this->db->where ($this->primaryKey, $this->data[$this->primaryKey]);
		return $this->db->delete ($this->dbTable);
	}
	/**
	 * Get object by primary key.
	 *
	 * @access public
	 * @param $id Primary Key
	 * @param array|string $fields Array or coma separated list of fields to fetch
	 *
	 * @return dbObject|array
	 */
	protected function byId ($id, $fields = null) {
		$this->db->where ($this->db->prefix . $this->dbTable . '.' . $this->primaryKey, $id);
		return $this->getOne ($fields);
	}
	/**
	 * Convinient function to fetch one object. Mostly will be togeather with where()
	 *
	 * @access public
	 * @param array|string $fields Array or coma separated list of fields to fetch
	 *
	 * @return dbObject
	 */
	protected function getOne ($fields = null) {
		$this->processHasOneWith ();
		//echo($this->dbTable."\n");
		$results = $this->db->ArrayBuilder()->getOne ($this->dbTable, $fields);
		if ($this->db->count == 0)
			return null;
		$this->processArrays ($results);
		$this->data = $results;
		$this->processAllWith ($results);
		if ($this->returnType == 'Json')
			return json\encode ($results);
		if ($this->returnType == 'Array')
			return $results;
		$item = new static ($results);
		$item->isNew = false;
		return $item;
	}
	protected function has(){
		$this->processHasOneWith ();
		return $this->db->has($this->dbTable);
	}
	/**
	 * Fetch all objects
	 *
	 * @access public
	 * @param integer|array $limit Array to define SQL limit in format Array ($count, $offset)
	 *							 or only $count
	 * @param array|string $fields Array or coma separated list of fields to fetch
	 *
	 * @return array Array of dbObjects
	 */
	protected function get ($limit = null, $fields = null) {
		$objects = array();
		$this->processHasOneWith ();
		$results = $this->db->ArrayBuilder()->get ($this->dbTable, $limit, $fields);
		if ($this->db->count == 0)
			return null;
		foreach ($results as &$r) {
			$this->processArrays ($r);
			$this->data = $r;
			$this->processAllWith ($r, false);
			if ($this->returnType == 'Object') {
				$item = new static ($r);
				$item->isNew = false;
				$objects[] = $item;
			}
		}
		$this->_with = array();
		if ($this->returnType == 'Object')
			return $objects;
		if ($this->returnType == 'Json')
			return json\encode ($results);
		return $results;
	}
	/**
	 * Function to set witch hasOne or hasMany objects should be loaded togeather with a main object
	 *
	 * @access public
	 * @param string $objectName Object Name
	 *
	 * @return dbObject
	 */
	private function with ($objectName) {
		if (!property_exists ($this, 'relations') && !isset ($this->relations[$name]))
			die ("No relation with name $objectName found");
		$this->_with[$objectName] = $this->relations[$objectName];
		return $this;
	}
	/**
	 * Function to join object with another object.
	 *
	 * @access public
	 * @param string $objectName Object Name
	 * @param string $key Key for a join from primary object
	 * @param string $joinType SQL join type: LEFT, RIGHT,  INNER, OUTER
	 * @param string $primaryKey SQL join On Second primaryKey
	 *
	 * @return dbObject
	 */
	private function join ($objectName, $key = null, $joinType = 'LEFT', $primaryKey = null) {
		if(is_string($objectName)){
			$joinObj = new $objectName;
		}elseif(is_object($objectName)){
			$joinObj =$objectName;
		}
		if (!$key)
			$key = $this->dbTable. ".id";
		if ($primaryKey){
			if (!strchr ($primaryKey, '.')){
				$primaryKey = $this->db->prefix . $joinObj->dbTable . ".{$primaryKey}";
			}
		}else{
			$primaryKey = $this->db->prefix . $joinObj->dbTable . "." . $joinObj->primaryKey;
		}
		if (!strchr ($key, '.'))
			$joinStr = $this->db->prefix . $this->dbTable . ".{$key} = " . $primaryKey;
		else
			$joinStr = $this->db->prefix . "{$key} = " . $primaryKey;



		$this->db->join ($joinObj->dbTable, $joinStr, $joinType);
		return $this;
	}
	/**
	 * Function to get a total records count
	 *
	 * @return int
	 */
	protected function count () {
		$res = $this->db->ArrayBuilder()->getValue ($this->dbTable, "count(*)");
		if (!$res)
			return 0;
		return $res;
	}
	/**
	 * Pagination wraper to get()
	 *
	 * @access public
	 * @param int $page Page number
	 * @param array|string $fields Array or coma separated list of fields to fetch
	 * @return array
	 */
	private function paginate ($page, $fields = null) {
		$objects = array ();
		$this->db->pageLimit = self::$pageLimit;
		$this->processHasOneWith ();
		$results = $this->db->ArrayBuilder()->paginate($this->dbTable, $page, $fields);
		if ($this->db->count == 0)
			return null;
		self::$totalPages = $this->db->totalPages;
		foreach ($results as &$r) {
			$this->processArrays ($r);
			$this->data = $r;
			$this->processAllWith ($r, false);
			if ($this->returnType == 'Object') {
				$item = new static ($r);
				$item->isNew = false;
				$objects[] = $item;
			}
		}
		$this->_with = array();
		if ($this->returnType == 'Object')
			return $objects;
		if ($this->returnType == 'Json')
			return json\encode ($results);
		return $results;
	}
	/**
	 * Catches calls to undefined methods.
	 *
	 * Provides magic access to private functions of the class and native public mysqlidb functions
	 *
	 * @param string $method
	 * @param mixed $arg
	 *
	 * @return mixed
	 */
	public function __call ($method, $arg) {
		if (method_exists ($this, $method))
			return call_user_func_array (array ($this, $method), $arg);
		call_user_func_array (array ($this->db, $method), $arg);
		return $this;
	}
	/**
	 * Catches calls to undefined static methods.
	 *
	 * Transparently creating dbObject class to provide smooth API like name::get() name::orderBy()->get()
	 *
	 * @param string $method
	 * @param mixed $arg
	 *
	 * @return mixed
	 */
	public static function __callStatic ($method, $arg) {
		$obj = new static;
		$result = call_user_func_array (array ($obj, $method), $arg);
		if (method_exists ($obj, $method))
			return $result;
		return $obj;
	}
	/**
	 * Converts object data to an associative array.
	 *
	 * @return array Converted data
	 */
	public function toArray () {
		$data = $this->data;
		$this->processAllWith ($data);
		foreach ($data as $key => $d) {
			if(is_array($d)){
				foreach($d as $key2 => $val2){
					if ($val2 instanceof dbObject){
						$data[$key][$key2] = $val2->toArray();
					}
				}
			}elseif(is_object($d) and $d instanceof dbObject){
				$primaryKey= $d->getPrimaryKey();
				$data[$key] = $d->$primaryKey;
			}
		}
		return $data;
	}
	/**
	 * Converts object data to a JSON string.
	 *
	 * @return string Converted data
	 */
	public function toJson () {
		return json\encode ($this->toArray());
	}
	/**
	 * Converts object data to a JSON string.
	 *
	 * @return string Converted data
	 */
	public function __toString () {
		return $this->toJson ();
	}
	/**
	 * Function queries hasMany relations if needed and also converts hasOne object names
	 *
	 * @param array $data
	 */
	private function processAllWith (&$data, $shouldReset = true) {
		if (count ($this->_with) == 0)
			return;
		foreach ($this->_with as $name => $opts) {
			$relationType = strtolower ($opts[0]);
			$modelName = $opts[1];
			if ($relationType == 'hasone') {
				$obj = new $modelName;
				$table = $obj->dbTable;
				$primaryKey = $obj->primaryKey;

				if (!isset ($data[$table])) {
					$data[$name] = $this->$name;
					continue;
				}
				if ($data[$table][$primaryKey] === null) {
					$data[$name] = null;
				} else {
					if ($this->returnType == 'Object') {
						$item = new $modelName ($data[$table]);
						$item->returnType = $this->returnType;
						$item->isNew = false;
						$data[$name] = $item;
					} else {
						$data[$name] = $data[$table];
					}
				}
				unset ($data[$table]);
			}
			else
				$data[$name] = $this->$name;
		}
		if ($shouldReset)
			$this->_with = array();
	}
	/*
	 * Function building hasOne joins for get/getOne method
	 */
	private function processHasOneWith () {
		if (count ($this->_with) == 0)
			return;
		foreach ($this->_with as $name => $opts) {
			$relationType = strtolower ($opts[0]);
			$modelName = $opts[1];
			$key = null;
			if (isset ($opts[2]))
				$key = $opts[2];
			if ($relationType == 'hasone') {
				$this->db->setQueryOption ("MYSQLI_NESTJOIN");
				$this->join ($modelName, $key);
			}
		}
	}
	/**
	 * @param array $data
	 */
	private function processArrays (&$data) {
		if (isset ($this->jsonFields) && is_array ($this->jsonFields)) {
			foreach ($this->jsonFields as $key){
				$firstChars  = substr($data[$key], 0,1);
				if($firstChars == '{' or $firstChars == '['){
					$data[$key] = json\decode ($data[$key]);
				}
			}
		}
		if (isset ($this->serializeFields) && is_array ($this->serializeFields)) {

			foreach ($this->serializeFields as $key){
				if($data[$key]){
					$data[$key] = unserialize ($data[$key]);
				}
			}
		}
		if (isset ($this->arrayFields) && is_array($this->arrayFields)) {
			foreach ($this->arrayFields as $key)
				$data[$key] = explode ("|", $data[$key]);
		}
	}
	/**
	 * @param array $data
	 */
	private function validate ($data) {
		if (!$this->dbFields)
			return true;
		foreach ($this->dbFields as $key => $desc) {
			$type = isset($desc['type']) ? $desc['type'] : null;
			$required = (isset($desc['required']) and $desc['required']);
			$unique = (isset($desc['unique']) and $desc['unique']);
			$value = isset ($data[$key]) ? $data[$key] : null;

			if (is_array ($value))
				continue;
			if ($required and $value === null) {
				throw new InputRequired($key);
			}
			if($unique and !empty($value)){
				if($this->primaryKey != $key and isset($this->data[$this->primaryKey])){
					$this->db->where($this->primaryKey, $this->data[$this->primaryKey], '!=');
				}
				$this->db->where($key, $value);
				if($this->db->has($this->dbTable)){
					throw new duplicateRecord($key);
				}
			}
			if ($value == null)
				continue;
			switch ($type) {
				case "text";
					$regexp = null;
					break;
				case "int":
					$regexp = "/^[0-9]*$/";
					break;
				case "double":
					$regexp = "/^[0-9\.]*$/";
					break;
				case "bool":
					$regexp = '/^[yes|no|0|1|true|false]$/i';
					break;
				case "datetime":
					$regexp = "/^[0-9a-zA-Z -:]*$/";
					break;
				default:
					$regexp = $type;
					break;
			}
			if (!$regexp)
				continue;
			if (!preg_match ($regexp, $value)) {
				throw new InputDataType($key);
			}
		}
		return true;
	}
	public function getPrimaryKey(){
		return $this->primaryKey;
	}
	private function prepareData () {
		$this->errors = array();
		$sqlData = array();
		if (count ($this->data) == 0)
			return array();
		if (method_exists ($this, "preLoad"))
			$this->data = $this->preLoad ($this->data);
		if (!$this->dbFields)
			return $this->data;
		foreach ($this->data as $key => &$value) {
			if ($value instanceof dbObject) {
				if($value->isNew == true){
					$id = $value->save();
					if ($id){
						$value = $id;
					}else{
						$this->errors = array_merge ($this->errors, $value->errors);
					}
				}else{
					$pkey = $value->getPrimaryKey();
					$sqlData[$key] = $value->$pkey;
					continue;
				}
			}
			if (!in_array ($key, array_keys ($this->dbFields)))
				continue;
			if (!is_array($value) and !is_object($value)) {
				$sqlData[$key] = $value;
				continue;
			}
			if (isset ($this->jsonFields) && in_array ($key, $this->jsonFields)){
				if(is_array($value) or is_object($value)){
					$sqlData[$key] = json\encode($value);
				}else{
					$sqlData[$key] = $value;
				}
			}else if (isset ($this->arrayFields) && in_array ($key, $this->arrayFields)){
				$sqlData[$key] = implode ("|", $value);
			}elseif (isset ($this->serializeFields) && in_array ($key, $this->serializeFields)){
				$sqlData[$key] = serialize($value);
			}else{
				$sqlData[$key] = $value;
			}
		}
		return $sqlData;
	}
	static function objectToArray($array){
		$return = array();
		foreach($array as $key => $val){
			if(is_object($val) and $val instanceof dbObject){
				$return[$key] = $val->toArray();
			}else{
				$return[$key] = $val;
			}
		}
		return $return;
	}
}
