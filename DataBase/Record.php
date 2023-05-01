<?php
/**
 * DataBase Record Object Class
 * © Hsilamot 2020-2020
 * developed by Hidalgo Rionda
 *
 * +----------------------------------------------------------------------+
 * |                   DataBase Record Object v 1.0.1                     |
 * |             released under Creative Commons BY-NC-SA 3.0             |
 * |          http://creativecommons.org/licenses/by-nc-sa/3.0/           |
 * +----------------------------------------------------------------------+
 *
 * +----------------------------------------------------------------------+
 * |                              changelog                               |
 * | v1.0.1 (Hsilamot) Creation on 2020                                   |
 * +----------------------------------------------------------------------+
 *
 * @access public
 * @author Hsilamot <php@hsilamot.com>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @version 1.0.1
 */
namespace Tomalish\DataBase;
use Tomalish\NuCoKe;

class Record implements \JsonSerializable {
	/**
	 * This variable is intended to store the Initialized NuCoKe class
	 * @access protected
	 * @var object NuCoKe object
	 */
	protected $nucoke;
	/**
	 * This is an expected list of column names that cannot be updated using the direct $class->property method
	 * @access protected
	 * @var array Array with names
	 */
	//protected $immutable;
	/**
	 * This is the object who defines the database structure for table creation and migration
	 * @access protected
	 * @var object Tomalish\DataBase\Table object
	 */
	//protected $table;
	/**
	 * This variable contains the current data with local modifications
	 * @access private
	 * @var array SQL Data
	 */
	private $data;
	/**
	 * This variable contains the original data fetched from the SQL Server
	 * @access private
	 * @var array SQL Data
	 */
	private $data_original;
	/**
	 * This will initialize the class and will import the NuCoKe object
	 * @access public
	 * @version 1.0.1
	 * @return boolean if success
	 */
	public function __construct() {
		if (!defined('NuCoKe')) {
			throw new \Exception('A constant named NuCoKe containing the name of the variable which contains the initialized NuCoKe class is needed');
		}
		global ${NuCoKe};
		$this->nucoke = &${NuCoKe};
		$this->data = array();
		$this->data_original = array();
		return true;
	}
	/**
	 * This is an example function which will be called when loadin the column name
	 * check for column_{COLUMN_NAME} existence and if exists call it to process the data
	 * @access protected
	 * @version 1.0.1
	 * @param string $data data to be processed
	 * @return string processed data
	 */
	// protected function column_register_ip($data) {
	// 	return $this->nucoke->ip_decode($data);
	// }
	/**
	 * This function will return a list with general searches.
	 * @access public
	 * @version 1.0.1
	 * @param object PDOResult object
	 * @return bool success state
	 */
	public function list($parameters=array()) {
		$options = array();
		$options['columns'] = array('t.*');
		$options['filters'] = array();
		$options['order'] = array();
		$options['page'] = 1;
		$options['limit'] = 100;
		foreach ($options as $name => $value) {
			if (isset($parameters[$name])) {
				$options[$name] = $value;
			}
		}
		$sql  = 'SELECT '.implode(',',$options['columns']).'';
		$sql .= ' FROM `'.$this->table->name.'` t';
		$result = $this->nucoke->sql($sql);
		$return = array();
		foreach ($result as $preobject) {
			$myself = get_called_class();
			$newObject = new $myself(false,false,json_encode($preobject));
			$return[] = $newObject;
		}
		return $return;
	}
	/**
	 * This will load the data trying to use the default key
	 * @access public
	 * @version 1.0.1
	 * @return bool success state
	 */
	protected function load($primary=array()) {
		$nucoke = &$this->nucoke;
		$columns = array();
		foreach ($this->table->columns as $name => $column) {
			$columns[] = 't.`'.$name.'`';
		}
		$where_clause = array();
		foreach ($this->table->primary_keys as $primary_key) {
			$where_clause[] = ' t.`'.$primary_key.'` = '.$this->sql_sanitize($primary_key,$primary[$primary_key]);
		}
		$sql  = 'SELECT ';
		$sql .= implode(',',$columns);
		$sql .= ' FROM `'.$this->table->name.'` t';
		$sql .= ' WHERE ';
		$sql .= implode(' AND',$where_clause);
		$sql .= ' LIMIT 1;';
		$result = $nucoke->sql($sql);
		if (count($result)>0) {
			$this->loadData($result);
		} else {
			throw new \Exception('Could not find the associated data');
		}
		return true;
	}
	/**
	 * This function will load the data from a PDOResult object
	 * @access public
	 * @version 1.0.1
	 * @param object PDOResult object
	 * @return bool success state
	 */
	protected function loadData($PDOResult,$isJSON=false) {
		if ($isJSON==false) {
			$result = $PDOResult[0];
		} else {
			$result = json_decode($PDOResult);
		}
		$this->data = array();
		foreach ($result as $name => $value) {
			if (method_exists($this,'column_load_'.$name)) {
				$this->data[$name] = call_user_func(array($this,'column_load_'.$name),$value);
			} else {
				$this->data[$name] = $value;
			}
			$this->data_original[$name] = $this->data[$name];
		}
		return true;
	}
	/**
	 * This will load default data in case there is no such data already loaded
	 * @access public
	 * @version 1.0.1
	 * @return bool success state
	 */
	protected function loadDefaultData() {
		/* we verify if we have a table definition and if so load the default values into the object */
		if (isset($this->table)&&isset($this->table->columns)) {
			foreach ($this->table->columns as $name => $column) {
				if (!isset($this->data[$name])) {
					if (isset($column->null)&&$column->null==true) {
						if (isset($column->default)&&$column->default=='NULL') {
							$value = null;
						} else {
							$value = null;
						}
					} else {
						if (isset($column->default)) {
							$value = $column->default;
						} else {
							$value = null;
						}
					}
					if (method_exists($this,'column_load_'.$name)) {
						$this->data[$name] = call_user_func(array($this,'column_load_'.$name),$value);
					} else {
						$this->data[$name] = $value;
					}
					$this->data_original[$name] = $this->data[$name];
				}
			}
		}
		return true;
	}
	/**
	 * This function will commit the changes and save the record and in case of being enabled save the history
	 * @access public
	 * @version 1.0.1
	 * @return int id saved
	 */
	public function save() {
		if (is_object($this->table)&&count($this->table->primary_keys)>0) {
			if (count($this->data_original)>0) {
				$sql  = 'UPDATE `'.$this->table->name.'` t';
				$toupdate = array();
				if (isset($this->table->log_table_name)&&strlen($this->table->log_table_name)>0) {
					$sql_log  = 'INSERT INTO `'.$this->table->log_table_name.'`';
					$sql_log .= ' (';
					$sql_log .= '`'.$this->table->log_table_primarykey.'`';
					foreach ($this->table->columns as $name => $column) {
						$sql_log .= ',`'.$name.'`';
					}
					$sql_log .= ')';
					$sql_log .= ' SELECT';
					$sql_log .= ' logid.`'.$this->table->log_table_primarykey.'`';
					foreach ($this->table->columns as $name => $column) {
						$sql_log .= ',t.`'.$name.'`';
					}
					$sql_log .= ' FROM `'.$this->table->name.'` t';
					$sql_log .= ' LEFT JOIN (';
					$sql_log .= '	SELECT COUNT(logtable.`'.$this->table->log_table_primarykey.'`)+1 as `'.$this->table->log_table_primarykey.'`';
					$sql_log .= '	FROM `'.$this->table->log_table_name.'` logtable';
					$sql_log .= '	WHERE ';
					$where_clause = array();
					foreach ($this->table->primary_keys as $primary_key) {
						$where_clause[] = ' logtable.`'.$primary_key.'` = '.$this->sql_sanitize($primary_key,$this->data[$primary_key]);
					}
					$sql_log .= implode(' AND ',$where_clause);
					$sql_log .= ') logid on 1';
					$sql_log .= ' WHERE ';
					$where_clause = array();
					foreach ($this->table->primary_keys as $primary_key) {
						$where_clause[] = ' t.`'.$primary_key.'` = '.$this->sql_sanitize($primary_key,$this->data[$primary_key]);
					}
					$sql_log .= implode(' AND ',$where_clause);
					$this->nucoke->sql($sql_log); //save the snapshot
					if (isset($this->table->log_name_current_log)&&strlen($this->table->log_name_current_log)>0) {
						$prepare  = 't.`'.$this->table->log_name_current_log.'` = (';
						$prepare .= 'SELECT';
						$prepare .= ' log.`'.$this->table->log_table_primarykey.'`';
						$prepare .= ' FROM `'.$this->table->log_table_name.'` log';
						$prepare .= ' WHERE';
						$where_clause = array();
						foreach ($this->table->primary_keys as $primary_key) {
							$where_clause[] = ' log.`'.$primary_key.'` = '.$this->sql_sanitize($primary_key,$this->data[$primary_key]);
						}
						$prepare .= implode(' AND',$where_clause);
						$prepare .= ' ORDER BY log.`'.$this->table->log_table_primarykey.'` DESC';
						$prepare .= ' LIMIT 1';
						$prepare .= ')';
						$toupdate[] = $prepare;
					}
				}
				foreach ($this->table->columns as $name => $column) {
					if (isset($this->data[$name])&&$this->data[$name]!=$this->data_original[$name]) {
						if (method_exists($this,'column_save_'.$name)) {
							$toupdate[] = 't.`'.$name.'` = '.$this->sql_sanitize($name,call_user_func(array($this,'column_save_'.$name),$this->data[$name]));
						} else {
							$toupdate[] = 't.`'.$name.'` = '.$this->sql_sanitize($name,$this->data[$name]);
						}
					}
				}
				$sql .= ' SET '.implode(', ',$toupdate);
				$sql .= ' WHERE';
				$where_clause = array();
				foreach ($this->table->primary_keys as $primary_key) {
					$where_clause[] = ' t.`'.$primary_key.'` = '.$this->sql_sanitize($primary_key,$this->data[$primary_key]);
				}
				$sql .= implode(' AND',$where_clause);
				$sql .= ' LIMIT 1;';
				if (count($toupdate)==0) {
					return false;
				}
			} else {
				$sql  = 'INSERT INTO `'.$this->table->name.'`';
				$fields = array();
				$values = array();
				foreach ($this->table->columns as $name => $column) {
					if (isset($this->data[$name])) {
						if (method_exists($this,'column_save_'.$name)) {
							$fields[] = '`'.$name.'`';
							$values[] = $this->sql_sanitize($name,call_user_func(array($this,'column_save_'.$name),$this->data[$name]));
						} else {
							$fields[] = '`'.$name.'`';
							$values[] = $this->sql_sanitize($name,$this->data[$name]);
						}
					}
				}
				$sql .= ' ('.implode(', ',$fields).') VALUES';
				$sql .= ' ('.implode(', ',$values).');';
			}
			$return = $this->nucoke->sql($sql);
		} else {
			throw new \Exception('A save was triggered but no table or column is defined to update');
		}
		return $return;
	}
	/**
	 * This function will convert the $value into the correct format according to $name(Column) type
	 * @access public
	 * @version 1.0.1
	 * @param string $name Column's name to identify the column type
	 * @param string $value The data to be processed
	 * @return string sanitized data
	 */
	public function sql_sanitize($name,$value) {
		$return = '';
		switch ($this->table->columns->{$name}->type) {
				case 'tinyint':
				case 'mediumint':
				case 'int':
				case 'bigint':
					$return = NuCoKe::sql_dechex($value); break;
				case 'tinytext':
				case 'tinyblob':
					if (strlen($value)>255) {
						trigger_error('I cannot put more than 255 bytes of data into a '.$this->table->columns->{$name}->type.' column!',E_USER_NOTICE);
						return false;
					}
					$return = NuCoKe::sql_texthex($value);
					break;
				case 'text':
				case 'blob':
					if (strlen($value)>65535) {
						trigger_error('I cannot put more than 65535 bytes of data into a '.$this->table->columns->{$name}->type.' column!',E_USER_NOTICE);
						return false;
					}
					$return = NuCoKe::sql_texthex($value);
					break;
				case 'mediumtext':
				case 'mediumblob':
					if (strlen($value)>16777215) {
						trigger_error('I cannot put more than 16777215 bytes of data into a '.$this->table->columns->{$name}->type.' column!',E_USER_NOTICE);
						return false;
					}
					$return = NuCoKe::sql_texthex($value);
					break;
				case 'longtext':
				case 'longblob':
					if (strlen($value)>4294967295) {
						trigger_error('I cannot put more than 4294967295 bytes of data into a '.$this->table->columns->{$name}->type.' column!',E_USER_NOTICE);
						return false;
					}
					$return = NuCoKe::sql_texthex($value);
					break;
				case 'char':
				case 'varchar':
					if (strlen($value)>$this->table->columns->{$name}->length) {
						trigger_error('I cannot put more than '.$this->table->columns->{$name}->length.' bytes of data into a '.$this->table->columns->{$name}->type.'('.$this->table->columns->{$name}->length.') column!',E_USER_NOTICE);
						return false;
					}
					$return = NuCoKe::sql_texthex($value);
					break;
				case 'binary':
				case 'varbinary':
					if (strlen($value)>$this->table->columns->{$name}->length) {
						trigger_error('I cannot put more than '.$this->table->columns->{$name}->length.' bytes of data into a '.$this->table->columns->{$name}->type.'('.$this->table->columns->{$name}->length.') column!',E_USER_NOTICE);
						return false;
					}
					$return = NuCoKe::sql_texthex($value);
					break;
				case 'datetime':
					$return = '\''.date('Y-m-d H:i:s',strtotime($value)).'\'';
					break;
				default:
					trigger_error('I don\'t know how to update this type of record: '.$this->table->columns->{$name}->type,E_USER_NOTICE);
		}
		return $return;
	}
	/**
	 * This function will try to set and save to the SQL server the sent data
	 * @access public
	 * @version 1.0.1
	 * @param string $name The property who is requested to be set
	 * @param string $value The value to be set onto
	 * @return bool if success
	 */
	public function __set($name, $value) {
		if (array_key_exists($name,$this->data)||isset($this->table->columns->{$name})) {
			if (isset($this->immutable)) {
				if (in_array($name,$this->immutable)) {
					return false;
				}
			}
			if ($this->table->columns->{$name}->type=='datetime') {
				$time = strtotime($value);
				if (!$time) {
					$time = $value;
				}
				$this->data[$name] = date('Y-m-d H:i:s',$time);
			} else {
				$this->data[$name] = $value;
			}
			return true;
		} else {
			return false;
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
}