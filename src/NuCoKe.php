<?php
/**
 * N.U.C.O.K.E. Main Central Development Core
 * © Hsilamot 2014-2020
 * developed by Hidalgo Rionda
 *
 * +----------------------------------------------------------------------+
 * |                        N.U.C.O.K.E. v 4.0.1                          |
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
 * @version 4.0.1
 */
namespace Tomalish;

class NuCoKe {
	/**
	 * start of execution of the class
	 * @access private
	 * @var float unix timestamp with microtime
	 */
	private $start;
	/**
	 * This property defines if variables should be printed out or not
	 * @access private
	 * @var bool if errors should be printed out
	 */
	private $errorprint;
	/**
	 * This property saves the objects of the databases.
	 * @access private
	 * @var array objects
	 */
	private $database;
	/**
	 * This property defines the TimeZone used on the project
	 * @access private
	 * @var string TimeZone string
	 */
	private $timezone;
	/**
	 * This property saves the project's name
	 * @access private
	 * @var string Name of the project
	 */
	private $name;
	/**
	 * This property contains the project's version
	 * @access private
	 * @var array Array containing the version
	 */
	private $version;
	/**
	 * This property defines the charset to use on the project
	 * @access private
	 * @var string Chartset to use
	 */
	private $charset;
	/**
	 * This property defines the project's path
	 * @access private
	 * @var string Path of the project
	 */
	private $path;
	/**
	 * This property defines the tmp folder path
	 * @access private
	 * @var string Temporal folder path
	 */
	private $tmp_path;
	/**
	 * This is the filename for the logger
	 * @access private
	 * @var string Logger filename
	 */
	private $error_file;
	/**
	 * will initialize the class
	 * @access public
	 * @version 2.0.1
	 * @param array $config array containing any extra configuration from the user
	 * @return boolean if success
	 */
	public function __construct($config=array()) {
		/* we set the error handler */
		set_error_handler(array($this, 'error_handler'));
		error_reporting(-1);
		/* we will now set the default configuration */
		$this->start		= microtime(true);						/* time we started the class */
		$this->errorprint	= false;								/* if we should print out the errors */
		$this->timezone		= 'America/Mexico_City';				/* timezone we will set the PHP (http://www.php.net/manual/en/timezones.php) */
		$this->name			= 'NuCoKe';								/* this is the version of the user's proyect'*/
		$this->version		= array(1,0,0);							/* this is the version of the user's proyect'*/
		$this->charset		= 'UTF-8';								/* the Charset we will be working on */
		$this->path			= getcwd();								/* the Main path we will be working on */
		$this->tmp_path		= getcwd().'/tmp';						/* the tmp folder path */
		$this->error_file	= '/logs/error_log.'.date('YW').'.log';	/* this defines a file to save the log errors */
		$this->db_default	= 'nucoke';								/* this is default database for the core */
		/* we will parse any configuration added from user end */
		if (!is_array($config)) { $config = array(); trigger_error('NuCoKe: Unknown configuration type passed on __initialize($config)',E_USER_NOTICE); }
		foreach ($config as $name => $value) {
			switch ($name) {
				case 'errorprint':
					if ($value) { $this->errorprint = true; } else { $this->errorprint = false; } break;
				case 'db_default':
					$this->db_default = $value; break;
				case 'timezone':
					$thistimezone = $value; break;
				case 'name':
					$this->name = $value; break;
				case 'version':
					if (is_array($value)) { $this->version = $value; } break;
				case 'charset':
					$this->charset = $value; break;
				case 'path':
					$this->path = $value; break;
				case 'tmp_path':
					$this->tmp_path = $value; break;
				default:
					trigger_error('NuCoKe: Unknown configuration name '.var_export($name,true).'='.var_export($value,true),E_USER_NOTICE); break;
			}
		}
		if (!date_default_timezone_set($this->timezone)) { trigger_error('TimeZone invalid!'); } /* we set the timezone */
		/* we now change the work directory path */
		if (is_dir($this->path)) {
			$chdir = chdir($this->path);
			if (!$chdir) {
				trigger_error('NuCoKe: Could not set working directory to \''.$this->path.'\'',E_USER_ERROR);
			}
		} else {
			trigger_error('NuCoKe: The directory \''.$this->path.'\' does not exist, could not change work directory',E_USER_ERROR);
		}
		if (!is_dir($this->tmp_path)) {
			mkdir($this->tmp_path);
		}
		return true;
	}
	/**
	 * Adds a database to the database array
	 * @access public
	 * @version 2.0.1
	 * @param string $name name of the database
	 * @param array $params parameters of the database
	 * @return bool return true if success
	 */
	public function db_add($name,$params) {
		$this->database[$name] = new Database($params);	/* This will be the database object */
		$db = &$this->database[$name];
		foreach ($params as $name => $value) {
			if (isset($db->{$name})) { $db->{$name} = $value; }
		}
		return true;
	}
	/**
	 * Handles errors
	 * @access public
	 * @version 1.0.64
	 * @return bool return true if success
	 */
	public function error_handler($errno,$errstr,$errfile,$errline) {
		$file  = $this->tmp_path;
		$file .= $this->error_file;
		$pathcreate = explode('/',$this->error_file);
		array_pop($pathcreate);
		$creation_path = $this->tmp_path;
		foreach ($pathcreate as $folder) {
			$creation_path .= '/'.$folder;
			if (!is_dir($creation_path)) { mkdir($creation_path); }
		}
		$msg = $errfile.':'.$errline.'#'.$errno.' '.$errstr.chr(13).chr(10);
		error_log($msg,3,$file);
		if ($this->errorprint) {
			echo $msg;
			flush();
		}
		return true;
	}
	/**
	 * Will execute the $query on the specified $database or the default $database if not specified
	 * @access public
	 * @version 2.0.1
	 * @param string $query query to execute
	 * @param string $database name of database to use
	 * @return array with the result of the query
	 */
	public function sql($query,$database=null) {
		$call_string = 'NuCoKe::sql('.var_export($query,true).','.var_export($database,true).') ';
		if ($database===null) {
			return $this->sql($query,$this->db_default);
		} else {
			if (isset($this->database[$database])) {
				$database = &$this->database[$database];
			} else {
				trigger_error($call_string.'database not found...',E_USER_NOTICE);
				return false;
			}
		}
		return $database->query($query);
	}
}