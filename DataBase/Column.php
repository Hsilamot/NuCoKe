<?php
/**
 * Database Column Object Class
 * © Hsilamot 2020-2020
 * developed by Hidalgo Rionda
 *
 * +----------------------------------------------------------------------+
 * |                   Database Column Object v 1.0.1                     |
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

class Column {
	private $type;
	private $length;
	private $signed;
	private $zerofill;
	private $null;
	private $default;
	private $comment;
	private $autoincrement;
	
	public function __construct() {
		$this->type = 'int';
		$this->length = 10;
		$this->signed = false;
		$this->zerofill = false;
		$this->null = true;
		$this->default = '';
		$this->comment = 'Default Comment';
		$this->autoincrement = false;
	}

	public function __set($name, $value) {
		if (isset($this->{$name})) {
			switch ($name) {
				case 'type':
					switch ($value) {
						case 'int':
							$this->type = 'int'; break;
						case 'varchar':
							$this->type = 'varchar'; break;
						case 'datetime':
							$this->type = 'datetime'; break;
						case 'binary':
							$this->type = 'binary'; break;
						case 'tinytext':
							$this->type = 'tinytext'; break;
						default:
							trigger_error('Unimplemented data type: '.$value,E_USER_NOTICE);
					}
					break;
				case 'length':
					switch ($this->type) {
						case 'int':
						case 'varchar':
						case 'binary':
							$this->length = intval($value);
							break;
						default:
							trigger_error('Unimplemented length type: '.$this->type,E_USER_NOTICE);
					}
					break;
				case 'signed':
					if ($value) {
						$this->signed = true;
					} else {
						$this->signed = false;
					}
					break;
				case 'zerofill':
					if ($value) {
						$this->zerofill = true;
					} else {
						$this->zerofill = false;
					}
					break;
				case 'null':
					if ($value) {
						$this->null = true;
					} else {
						$this->null = false;
					}
					break;
				case 'default':
					$this->default = $value;
					break;
				case 'comment':
					$this->comment = $value;
					break;
				case 'autoincrement':
					$this->autoincrement = $value;
					break;
				default:
					trigger_error('Unimplemented propety '.$name,E_USER_NOTICE);
					return false;
			}
		}
		return true;
	}
	public function __get($name) {
		if (isset($this->{$name})) {
			return $this->{$name};
		} else {
			return null;
		}
	}
}