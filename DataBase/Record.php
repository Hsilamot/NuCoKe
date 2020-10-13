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
	protected $nucoke;
	//protected $immutable;
	//protected $table;
	private $data;

	public function __construct() {
		if (!defined('NuCoKe')) {
			throw new \Exception('A constant named NuCoKe containing the name of the variable which contains the initialized NuCoKe class is needed');
		}
		global ${NuCoKe};
		$this->nucoke = &${NuCoKe};
		$this->data = array();
	}
	// funcion para procesar un nombre de columna para utilizarse en la extensión de esta clase
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
	public function __get($name) {
		if (isset($this->data[$name])) {
			return $this->data[$name];
		} else {
			return null;
		}
	}
	public function __serialize() {
		$serialized = array();
		foreach ($this->data as $name => $value) {
			$serialized[$name] = $value;
		}
		return $serialized;
	}
	public function jsonSerialize() {
		return $this->__serialize();
	}
}