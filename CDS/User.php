<?php
/**
 * CDS User Class Tomalish.Networks
 * © Hsilamot 2014-2020
 * developed by Hidalgo Rionda
 *
 * +----------------------------------------------------------------------+
 * |                         User Class v 1.0.1                           |
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

namespace Tomalish\CDS;
use Tomalish\Abstracts\Model;
use Tomalish\DataBase\Table;
use Tomalish\DataBase\Column;
use Tomalish\NuCoKe;
use \Exception;

class User extends Model {
	/**
	 * The init method is called from the parent __construct,
	 * we will add the Table structure to the class to be used on
	 * the instanced object
	 * @access protected
	 * @version 1.0.1
	 * @return void
	 */
	protected function init() {
		$this->table = new Table();
		$this->table->name = 'CDS';
		$this->table->engine = 'InnoDB';
		$this->table->charset = 'utf8';
		$this->table->comment = 'CDS Table with the users of the system';
		$this->table->primary_keys = array('ID');

		$column = new Column();
		$column->type = 'int';
		$column->length = 4;
		$column->signed = false;
		$column->zerofill = true;
		$column->null = true;
		$column->comment = 'ID of the user';
		$column->autoincrement = 1000;
		$this->columns->ID = $column;

		$column = new Column();
		$column->type = 'varchar';
		$column->length = 72;
		$column->null = true;
		$column->default = 'NULL';
		$column->comment = 'The username of the user';
		$this->columns->username = $column;

		$column = new Column();
		$column->type = 'varchar';
		$column->length = 140;
		$column->null = true;
		$column->default = 'NULL';
		$column->comment = 'The name given to the user';
		$this->columns->name = $column;

		$column = new Column();
		$column->type = 'set';
		$column->length = array('root','admin','developer','user','guest','guestonly','ban');
		$column->default = 'ban';
		$column->comment = 'Access granted to the user';
		$this->columns->access = $column;

		$column = new Column();
		$column->type = 'int';
		$column->length = 10;
		$column->signed = false;
		$column->default = '0';
		$column->comment = 'Amount of logins of the user';
		$this->columns->logins = $column;

		$column = new Column();
		$column->type = 'int';
		$column->length = 10;
		$column->signed = false;
		$column->default = '0';
		$column->comment = 'Amount of logouts of the user';
		$this->columns->logouts = $column;

		$column = new Column();
		$column->type = 'datetime';
		$column->null = true;
		$column->default = 'NULL';
		$column->comment = 'Last Login timestamp of the user';
		$this->columns->login_ts = $column;

		$column = new Column();
		$column->type = 'datetime';
		$column->null = true;
		$column->default = 'NULL';
		$column->comment = 'Last Logout timestamp of the user';
		$this->columns->logout_ts = $column;

		$column = new Column();
		$column->type = 'datetime';
		$column->default = 'CURRENT_TIMESTAMP';
		$column->comment = 'Registration timestamp';
		$this->columns->register_ts = $column;

		$column = new Column();
		$column->type = 'binary';
		$column->length = 16;
		$column->comment = 'Registration IP address';
		$this->columns->register_ip = $column;

		$column = new Column();
		$column->type = 'tinytext';
		$column->comment = 'Registration Agent of the user';
		$this->columns->register_agent = $column;
	}

	public static function access($user,$accessLevel) {
		$access = false;
		if (!is_object($user)) {
			trigger_error('$user should be an object',E_USER_NOTICE);
			return false;
		}
		if (!is_array($user->access)) {
			throw new Exception('Access it not defined accesslists');
			return false;
		}
		if (in_array($accessLevel,$user->access)) {
			$access = true;
		}
		return $access;
	}
	/**
	 * Authenticate a User Object
	 * @access protected
	 * @version 1.0.1
	 * @return bool
	 */
	public static function auth($auth_object) {
		if (!defined('NuCoKe')) {
			throw new \Exception('A constant named NuCoKe containing the name of the variable which contains the initialized NuCoKe class is needed');
		}
		global ${NuCoKe};
		$nucoke = &${NuCoKe};

		$sql  = 'SELECT';
		$sql .= ' cds.`ID` as `user_id`';
		$sql .= ',cdsauth.`type` as `auth_type`';
		$sql .= ',cdsauth.`value` as `auth_value`';
		$sql .= ' FROM `CDS` as cds';
		$sql .= ' LEFT JOIN `CDS_Auth` as cdsauth ON cds.`ID` = cdsauth.`CDS` AND cdsauth.`deleted_user` IS NULL';
		$sql .= ' WHERE cds.`username` = '.$nucoke->sql_texthex($auth_object->user);

		$auth_methods = $nucoke->sql($sql);
		$authenticated = false;
		foreach ($auth_methods as $method) {
			if ($authenticated) { continue; }
			switch ($method['auth_type']) {
				case 'password':
					break;
				case 'password_md5':
					if (md5($auth_object->pass)===$method['auth_value']) {
						$authenticated = $method['user_id'];
					}
					break;
				case 'password_sha1':
					if (sha1($auth_object->pass)===$method['auth_value']) {
						$authenticated = $method['user_id'];
					}
					break;
				case 'password_plain':
					if ($auth_object->pass===$method['auth_value']) {
						$authenticated = $method['user_id'];
					}
					break;
			}
		}
		return $authenticated;
	}
	/**
	 * Validate data before destroying a record
	 * @access protected
	 * @version 1.0.1
	 * @return bool
	 */
	protected function preDestroy() {
		return true;
	}
	/**
	 * Validate before saving the record
	 * @access protected
	 * @version 1.0.1
	 * @return bool
	 */
	protected function preSave() {
		return true;
	}
	protected function load_column_access($data) {
		return explode(',',$data);
	}
	protected function save_column_access($data) {
		return implode(',',$data);
	}
	protected function load_column_register_ip($data) {
		return NuCoKe::ip_decode($data);
	}
	protected function save_column_register_ip($data) {
		return NuCoKe::ip_encode($data);
	}

}