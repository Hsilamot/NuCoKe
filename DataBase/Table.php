<?php
/**
 * Databasde Table Object Class
 * © Hsilamot 2020-2020
 * developed by Hidalgo Rionda
 *
 * +----------------------------------------------------------------------+
 * |                    Database Table Object v 1.0.1                     |
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

class Table {
	public $name;
	public $engine;
	public $charset;
	public $comment;
	public $columns;
	public $log_table_name;
	public $log_table_primarykey;
	public $log_name_current_log;
	public $primary_keys;
	
	public function __construct() {
		$this->name = 'DefaultName';
		$this->engine = 'InnoDB';
		$this->charset = 'utf8';
		$this->comment = 'Default Comment on Table';
		$this->columns = new \stdClass();
		$this->primary_keys = array();
	}
}