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
	 * This variable contains the actual data fetched from the SQL Server
	 * @access private
	 * @var array SQL Data
	 */
	private $data;
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
	protected function loadData($PDOResult) {
		$result = $PDOResult[0];
		$this->data = array();
		foreach ($result as $name => $value) {
			if (method_exists($this,'column_'.$name)) {
				$this->data[$name] = call_user_func(array($this,'column_'.$name),$value);
			} else {
				$this->data[$name] = $value;
			}
		}
		return true;
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
				case 'int':
					$return = NuCoKe::sql_dechex($value); break;
				case 'tinytext':
					if (strlen($value)>255) {
						trigger_error('I cannot put more than 255 bytes of data into a tinytext column!',E_USER_NOTICE);
						return false;
					}
					$return = NuCoKe::sql_texthex($value);
					break;
				case 'varchar':
					if (strlen($value)>$this->table->columns->{$name}->length) {
						trigger_error('I cannot put more than '.$this->table->columns->{$name}->length.' bytes of data into a varchar('.$this->table->columns->{$name}->length.') column!',E_USER_NOTICE);
						return false;
					}
					$return = NuCoKe::sql_texthex($value);
					break;
				case 'binary':
					if (strlen($value)>$this->table->columns->{$name}->length) {
						trigger_error('I cannot put more than '.$this->table->columns->{$name}->length.' bytes of data into a binary('.$this->table->columns->{$name}->length.') column!',E_USER_NOTICE);
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
		if (array_key_exists($name,$this->data)) {
			if (isset($this->immutable)) {
				if (in_array($name,$this->immutable)) {
					return false;
				}
			}
			if ($this->table->columns->{$name}->type=='datetime') {
				$this->data[$name] = date('Y-m-d H:i:s',strtotime($value));
			} else {
				$this->data[$name] = $value;
			}
			if (is_object($this->table)&&is_object($this->table->columns->{$name})&&count($this->table->primary_keys)>0) {
				$sql  = 'UPDATE `'.$this->table->name.'`';
				$sql .= ' SET `'.$name.'` = ';
				$sql .= $this->sql_sanitize($name,$value);
				$sql .= ' WHERE';
				$where_clause = array();
				foreach ($this->table->primary_keys as $primary_key) {
					$where_clause[] = ' `'.$primary_key.'` = '.$this->sql_sanitize($primary_key,$this->data[$primary_key]);
				}
				$sql .= implode(' AND ',$where_clause);
				$sql .= ' LIMIT 1;';
				$this->nucoke->sql($sql);
			} else {
				trigger_error('An update was triggered but no table or column is defined to update',E_USER_NOTICE);
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