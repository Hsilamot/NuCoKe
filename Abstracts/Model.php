<?php
/**
 * Model Abstract Class
 * © Hsilamot 2014-2020
 * developed by Hidalgo Rionda
 *
 * +----------------------------------------------------------------------+
 * |                          Database v 1.0.1                            |
 * |             released under Creative Commons BY-NC-SA 3.0             |
 * |          http://creativecommons.org/licenses/by-nc-sa/3.0/           |
 * +----------------------------------------------------------------------+
 *
 * +----------------------------------------------------------------------+
 * |                              changelog                               |
 * | v4.0.1 (Hsilamot) Rework on 2020                                     |
 * +----------------------------------------------------------------------+
 *
 * @access public
 * @author Hsilamot <php@hsilamot.com>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @version 1.0.1
 */

namespace Tomalish\Abstracts;

use \Exception;

abstract class Model implements \JsonSerializable {
	/**
	 * We will save the name of the data columns here, they must correspond to
	 * the ones on the database
	 * @access private
	 * @var array
	 */
	protected $columns;
	/**
	 * This variable is intended to store the Initialized NuCoKe class
	 * @access protected
	 * @var object NuCoKe object
	 */
	protected $nucoke;
	/**
	 * We store the array of the primary keys
	 * @access private
	 * @var array
	 */
	private $columns_primary=array();
	/**
	 * We will store the database records
	 * @access private
	 * @var array
	 */
	private $data=array();
	/**
	 * We will store the original database records
	 * @access private
	 * @var array
	 */
	private $data_original=array();
	/**
	 * We store the table object on this property
	 * @access private
	 * @var string
	 */
	protected $table=null;
	/**
	 * This tells us if we have data loaded from database
	 * to determine INSERT or UPDATE methods
	 * @access private
	 * @var bool
	 */
	private $isLoaded=false;
	/**
	 * Main Constructor function, this will reset all properties and try
	 * to call init() to load the table definitions, if we receive Params
	 * we will try to load from the database, if we receive a JSON we will
	 * asume this data was previously fetched and will load it directly into
	 * the properties of the class
	 * @access public
	 * @version 1.0.1
	 * @param string $params String for ID, Array with Primary Key Values, or JSON Data
	 * @return void
	 */
	public function __construct($params=null) {
		# First we get the NuCoKe object
		if (!defined('NuCoKe')) {
			throw new \Exception('A constant named NuCoKe containing the name of the variable which contains the initialized NuCoKe class is needed');
		}
		global ${NuCoKe};
		$this->nucoke = &${NuCoKe};
		# We call init which must be defined on the class,
		# so they pass us the Table definitions for the Model
		$this->table = null;
		$this->columns = new \stdClass();
		# We call now init to set the structure
		$this->init($params);
		$this->validStructure();

		$this->columns_primary = array();
		$this->data = array();
		$this->data_original = array();
		$this->isLoaded = false;

		foreach ($this->columns as $columnName => $column) {
			$this->data[$columnName] = null;
			$this->data_original[$columnName] = null;
		}
	
		if ($params!==null) {
			if (is_string($params)&&substr($params,0,1)=='{'&&substr($params,-1)=='}') {
				$this->loadData(json_decode($params));
			} else {
				$sql = $this->load($params);
				$data = $this->nucoke->sql($sql);
				if (count($data)==1) {
					$this->loadData($data[0]);
				} else {
					throw new Exception('More than one possible result to load!');
				}
			}
		}
	}
	/**
	 * This function will retrieve the data for public access
	 * @access public
	 * @version 1.0.1
	 * @param string $name The property who is requested to be read
	 * @return the corresponding data
	 */
	public function __get($name) {
		if (isset($this->data[$name])) {
			return $this->data[$name];
		} else {
			return null;
		}
	}
	/**
	 * This function will save the value to the data
	 * @access public
	 * @version 1.0.1
	 * @param string $name The property who is requested to be set
	 * @param string $value The value to be set onto
	 * @return void
	 */
	public function __set($name, $value) {
		if (array_key_exists($name,$this->data)) {
			if (is_string($value)) {
				$this->data[$name] = trim($value);
			} else {
				$this->data[$name] = $value;
			}
		} else {
			throw new Exception('Tried to set a non-existing property: '.get_class($this).'->'.$name);
		}
	}
	/**
	 * This function will return a count of the number of times the value
	 * is encountered in the column, this function is an alias of $$$$$
	 * @access public
	 * @version 1.0.1
	 * @param string $column the name of the column to count
	 * @param string $value the value of the columnt to look for to count
	 * @return int
	 */
	public function countColumnValue($column,$value) {
		$filters = array();
		$filters =	[
						'filters'		=>	[
												[$column,'=',$value]
											]
					];
		$count = $this->count($filters);
		if ($count>0) {
			return $count;
		} else {
			return false;
		}
	}
	/**
	 * This function will return a count using the specified filters
	 * @access public
	 * @version 1.0.1
	 * @param array $params Array containing the filters or parameters to filter the results
	 * @return int
	 */
	public function count(array $params) {
		$filters = array();
		$filters =	[
						'count'		=> true
					];
		return $this->list(array_merge($filters,$params));
	}
	/**
	 * This function is for destroying the Database Record
	 * @access public
	 * @version 1.0.1
	 * @return void
	 */
	public function destroy() {
		if (!$this->isLoaded) {
			throw new Exception('Trying to destroy a record that was not loaded does not make a lot of sense!');
		}
		if (method_exists($this,'preDestroy')) {
			$proceed = call_user_func(array($this,'preDestroy'));
			if (!$proceed) {
				return false;
			}
		}
		$sql  = 'DELETE FROM `'.$this->table.'`';
		$sql .= $this->getSqlPrimary();
		$result = $this->nucoke->sql($sql);
		if ($result) {
			return true;
		} else {
			throw new Exception('Could not DELETE the record');
		}
	}
	/**
	 * This function will destroy the received objects one by one
	 * @access public
	 * @version 1.0.1
	 * @param array $elements An array containing a list of model elements to destroy
	 * @return void
	 */
	public function destroyMultipleByArray(array $elements) {
		$filters = array();
		$filters['filters'] = array();
		$condiciones = array();
		foreach ($elements as $element) {
			$wheres = array();
			foreach ($this->columns_primary as $key) {
				if ($element->{$key}===null) {
					throw new Exception('Error on primary key \''.$key.'\' fetch from child!');
				}
				$wheres[] = '`'.$key.'` = \''.$this->sql_vaccine($element->{$key}).'\'';
			}
			if (count($wheres)>0) {
				$condiciones[] = '('.implode(' AND ',$wheres).')';
			}
		}
		$filters['filters'][] = implode(' OR ',$condiciones);
		$this->destroyMultiplebByFilter($filters);
	}
	/**
	 * This function will destroy the records that are returned by the filter parameter
	 * @access public
	 * @version 1.0.1
	 * @param array $params Array containing the filters or parameters to filter the results
	 * @return void
	 */
	public function destroyMultiplebByFilter($params) {
		$sql  = 'DELETE t FROM `'.$this->table.'` t ';
		$sql .= $this->getSqlFilters($params);
		$result = $this->nucoke->sql($sql);
		if ($result) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * This function will return the main SQL Query for the
	 * database without WHERE clauses, the parameters accept 'count'
	 * as a boolean that returns the total number of results instead of
	 * the data
	 * @access private
	 * @version 1.0.1
	 * @param array $params Array containing the filters or parameters to filter the results
	 * @return string
	 */
	private function getSql($params=null) {
		$this->validStructure();
		$sql  = 'SELECT ';
		if (is_array($params)&&isset($params['count'])&&$params['count']==true) {
			$columns = array();
			$sql .= ' COUNT(*) as `total` ';
		} else {
			$columns = array();
			foreach ($this->columns as $columnName => $column) {
				$columns[] = 't.`'.$columnName.'` as `'.$columnName.'`';
			}
		}
		$sql .= implode(',',$columns);
		$sql .= ' FROM `'.$this->table->name.'` t';
		return $sql;
	}
	/**
	 * This will return the WHERE statements for the provided parameters
	 * @access private
	 * @version 1.0.1
	 * @param array $params Array containing the filters or parameters to filter the results
	 * @return string
	 */
	private function getSqlFilters($params=null) {
		$filters = array();
		$sql = '';
		if (is_array($params)) {
			if (isset($params['filters'])) {
				foreach ($params['filters'] as $filter) {
					if (is_array($filter)&&count($filter)==3) {
						$filters[] = '(t.`'.$filter[0].'` '.$filter[1].' '.db_escape($filter[2]).')';
					} elseif (is_string($filter)) {
						$filters[] = '('.$filter.')';
					} else {
						throw new Exception('Filter not recognized: '.print_r($filter,true));
					}
				}
			}
			if (isset($params['show_inactive'])) {
				if ($this->column_inactive===null) {
					throw new Exception('No inactive column has been defined, cannot use show_inactive as filter!');
				}
				if ($params['show_inactive']==false) {
					$filters[] = 't.`'.$this->column_inactive.'` = 0';
				}
			} else {
				# We didn't receive a show_inactive parameter,
				# we will try to hide inactive rows if possible.
				if ($this->column_inactive!==null) {
					$filters[] = 't.`'.$this->column_inactive.'` = 0';
				}
			}
		}
		if (count($filters)>0) {
			$sql .= ' WHERE '.implode(' AND ',$filters);
		}
		return $sql;
	}
	/**
	 * This will return the WHERE Clause to isolate a single result
	 * useful for the UPDATE or the SELECT statement in which we
	 * load the data for the class
	 * @access private
	 * @version 1.0.1
	 * @return string
	 */
	private function getSqlPrimary() {
		$sql  = ' WHERE ';
		$wheres = array();
		foreach ($this->columns_primary as $key) {
			if ($this->data_original[$key]===null) {
				throw new Exception('Error on primary key \''.$key.'\' data should not be null');
			}
			$wheres[] = '`'.$key.'` = \''.$this->sql_vaccine($this->data_original[$key]).'\'';
		}
		if (count($wheres)>0) {
			$sql .= implode(',',$wheres);
		} else {
			$sql .= '1';
		}
		$sql .= ' LIMIT 1';
		return $sql;
	}
	/**
	 * This is intented to initialize the class and retrieve
	 * the database structure into the class
	 * @access protected
	 * @version 1.0.1
	 * @return void
	 */
	abstract protected function init();
	/**
	 * This will return a new instancement of the class using an array
	 * as data origin for the class
	 * @access public
	 * @version 1.0.1
	 * @param array $params data for the instancement
	 * @return void
	 */
	static public function instanceFromArray($data) {
		$return = new static();
		$return->loadData($data);
		return $return;
	}
	/**
	 * This function is intended to return an array with objects
	 * contaning a self class. Parameters allow filters to be applied
	 * @access public
	 * @version 1.0.1
	 * @param array $params Array containing the filters or parameters to filter the results
	 * @return array
	 */
	public function list($params=array()) {
		$sql = $this->getSql($params);
		$sql .= $this->getSqlFilters($params);
		$results = $this->nucoke->sql($sql);
		$return = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				if (is_array($params)&&isset($params['count'])&&$params['count']==true&&isset($result['total'])) {
					return intval($result['total']);
				}
				$prepare =  new static();
				$prepare->loadData($result);
				$return[] = $prepare;
			}
		}
		return $return;
	}
	/**
	 * This function will start the load process, for now
	 * it will return a SQL statement to retrieve the data but
	 * it is intended to return a PDOObject with the data
	 * already fetched or compiled.
	 * @access private
	 * @version 1.0.1
	 * @param array $params Array containing the filters or parameters to filter the results
	 * @return string
	 */
	private function load($params) {
		if (count($this->columns_primary)===1&&!is_array($params)) {
			$params = array($this->columns_primary[0] => $params);
		}
		foreach ($this->columns_primary as $key) {
			if (!isset($params[$key])) {
				throw new Exception('Cannot execute load(), missing load parameter on column \''.$key.'\'');
			}
			$this->data_original[$key] = $params[$key];
		}
		$sql = $this->getSql($params);
		$sql .= $this->getSqlPrimary();
		return $sql;
	}
	/**
	 * This will load the data results from the database into the class
	 * will also check for a function called load_column_{COLUMN_NAME}
	 * and if exists call it to process the data
	 * @access protected
	 * @version 1.0.1
	 * @param string $data data to be processed
	 * @return string processed data
	 */
	public function loadData($data) {
		if (is_array($data)) {
			$result = $data;
		} elseif (get_class($data)=='mysqli_result') {
			$result = $data->fetch_array(MYSQLI_ASSOC);
			if ($result===null) {
				throw new Exception('No data was found!');
			}
		} elseif (get_class($data)=='stdClass') {
			$result = $data;
		} else {
			throw new Exception('the $data object ('.get_class($data).') it not recognized must be either mysqli_result or stdClass');
		}
		foreach ($result as $name => $value) {
			if (method_exists($this,'load_column_'.$name)) {
				$this->data[$name] = call_user_func(array($this,'load_column_'.$name),$value);
			} else {
				$this->data[$name] = $value;
			}
			$this->data_original[$name] = $this->data[$name];
		}
		$this->isLoaded = true;
	}
	/**
	 * This method will save the current data of the class into the
	 * database, and will try to either INSERT or UPDATE a Record
	 * @access public
	 * @version 1.0.1
	 * @return void
	 */
	public function save() {
		$this->validStructure();
		if (method_exists($this,'preSave')) {
			$proceed = call_user_func(array($this,'preSave'));
			if (!$proceed) {
				return false;
			}
		}
		if (!$this->isLoaded) {
			# INSERT New Record
			$columns = array();
			$values = array();
			foreach ($this->columns as $column) {
				$columns[] = '`'.$column.'`';
				if ($this->data[$column]===null) {
					$values[] = 'NULL';
				} else {
					$data = $this->data[$column];
					if (method_exists($this,'save_column_'.$column)) {
						$data = call_user_func(array($this,'save_column_'.$column),$data);
					}
					$values[] = '\''.$this->sql_vaccine($data).'\'';
				}
			}
			$sql  = 'INSERT INTO `'.$this->table.'`';
			$sql .= ' ('.implode(',',$columns).') VALUES ('.implode(',',$values).')';
			$result = $this->nucoke->sql($sql);
			if ($result) {
				return true;
			} else {
				throw new Exception('Could not INSERT the record');
			}
		} else {
			# UPDATE Record
			$sets = array();
			foreach ($this->columns as $column) {
				if ($this->data[$column]!==$this->data_original[$column]) {
					if ($this->data[$column]===null) {
						$sets[] = '`'.$column.'` = NULL';
					} else {
						$data = $this->data[$column];
						if (method_exists($this,'save_column_'.$column)) {
							$data = call_user_func(array($this,'save_column_'.$column),$data);
						}
						$sets[] = '`'.$column.'` = \''.$this->sql_vaccine($data).'\'';
					}
				}
			}
			$sql  = 'UPDATE `'.$this->table.'`';
			$sql .= ' SET ';
			$sql .= implode(',',$sets);
			$sql .= $this->getSqlPrimary();
			if (count($sets)>0) {
				# we do execute the query, if sets = 0 then we do nothing
				$result = $this->nucoke->sql($sql);
				if (isset($result)) {
					return true;
				} else {
					throw new Exception('Could not UPDATE the record');
				}
			}
			# if $sets = 0 then we do nothing and call it a good work day!
			return true; // another job well done.
		}
	}
	/**
	 * Sets the Database Structure
	 * @access protected
	 * @version 1.0.1
	 * @param array Database Structure
	 * @return void
	 */
	protected function setStructure($structure) {
		$this->columns = array();
		$this->columns_primary = array();
		if (!isset($structure['columns'])) {
			throw new Exception('a columns definition is required on the structure');
		}
		if (!isset($structure['table'])) {
			throw new Exception('a table name is required on the structure');
		}
		$this->table = $structure['table'];
		if (isset($structure['column_inactive'])) {
			$this->column_inactive = $structure['column_inactive'];
		}
		foreach ($structure['columns'] as $line => $column) {
			if (!isset($column['name'])) { throw new Exception('Line #'.$line.' a column \'name\' is required'); }
			$this->columns[] = $column['name'];
			if (isset($column['isPrimary'])&&$column['isPrimary']==true) {
				$this->columns_primary[] = $column['name'];
			}
		}
	}
	/**
	 * This function validates the structure of the Database
	 * @access public
	 * @version 1.0.1
	 * @return void
	 */
	private	function validStructure() {
		if ($this->table===null) {
			throw new Exception('Table not defined, $this->setTable() must be used on the init() function on children class.');
		}
		if (count(get_object_vars($this->columns))===0) {
			throw new Exception('No columns have been defined, $this->addColumn() must be called on the init() function on children class to set at least one column.');
		}
	}
	/**
	 * Returns if this class was loaded with data
	 * @access protected
	 * @version 1.0.1
	 * @return bool
	 */
	public function wasLoaded() {
		return $this->isLoaded;
	}
	/**
	 * This function exports the data saved on the class for its serialization
	 * @access public
	 * @version 1.0.1
	 * @return array associative array containing the pertinent data
	 */
	public function __serialize() {
		$serialized = array();
		foreach ($this->data as $name => $value) {
			$serialized[$name] = $value;
		}
		$serialized['isLoaded'] = $this->isLoaded;
		return $serialized;
	}
	/**
	 * This implements \JsonSerializable which for compatibility purposes simply calls the __serialize magic method
	 * @access public
	 * @version 1.0.1
	 * @return array associative array generated by the __serialize magic method
	 */
	public function jsonSerialize() {
		return $this->__serialize();
	}
	/*
	 * this is a temporary function while we implement mysqli real escape string
	 *  */
	public static function sql_vaccine($string) {
		$end = str_replace(chr(92).chr(39),chr(92).chr(92).chr(39),$string);
		$end = str_replace(chr(39),chr(92).chr(39),$end);
		return $end;
	}
}
