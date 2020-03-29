<?php
/**
 * Database Class
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
namespace Tomalish;
use \PDO as PDO;
use \PDOException as PDOException;

class Database {
	/**
	 * PDO Object
	 * @access private
	 * @var object
	 */
	private $object;
	/**
	 * This defines if the Database is currently connected or not
	 * @access private
	 * @var bool
	 */
	private $connected;
	/**
	 * The Current Retry attempt at connection
	 * @access private
	 * @var int
	 */
	private $retry;
	/**
	 * The unix timestamp of the last retry
	 * @access private
	 * @var int
	 */
	private $retry_ts;
	/**
	 * The ammount of queries that this database has proccessed
	 * @access private
	 * @var int
	 */
	private $queries;
	/**
	 * defines if the connection is persistent or not
	 * @access public
	 * @var bool
	 */
	public $persistent;
	/**
	 * defines the database host to connect to
	 * @access public
	 * @var string
	 */
	public $host;
	/**
	 * defines the port number to connect to
	 * @access public
	 * @var int
	 */
	public $port;
	/**
	 * defines the path to the UNIX socket
	 * @access public
	 * @var string
	 */
	public $socket;
	/**
	 * defines which PDO engine will be used
	 * @access public
	 * @var string
	 */
	public $engine;
	/**
	 * defines the username of the database
	 * @access public
	 * @var string
	 */
	public $user;
	/**
	 * defines the password for the database
	 * @access public
	 * @var string
	 */
	public $pass;
	/**
	 * defines the name of the database
	 * @access public
	 * @var string
	 */
	public $database;
	/**
	 * defines the charset of the database
	 * @access public
	 * @var string
	 */
	public $charset;
	/**
	 * defines the prefix for the database tables
	 * @access public
	 * @var string
	 */
	public $prefix;
	/**
	 * defines if the queries should be logged into a trigger_error to be catched up by the error handler
	 * @access public
	 * @var bool
	 */
	public $log_queries;
	/**
	 * defines the ammount of retries before giving up on a database connection
	 * @access protected
	 * @var int
	 */
	protected $retries;
	/**
	 * defines the ammount of time to wait between connection retries
	 * @access protected
	 * @var int
	 */
	protected $wait;
	/**
	 * Constructs the database object to be used
	 * @access public
	 * @version 2.0.1
	 * @return bool return true if success
	 */
	public function __construct() {
		/* we will now prepare the Object for the parameters of the DataBase */
		/* SYSTEM */
		$this->object			= false;		/* here we will store the PDO object */
		$this->connected		= false;		/* here we will define if the Database is connected or not */
		$this->retry			= 0;			/* The Current Retry */
		$this->retry_ts			= 0;			/* The Last Retry in time() (we use this to determine if $between has passed) */
		$this->queries			= 0;			/* Number of Queries sent to the Database */
		/* User Options */
		$this->persistent	= false;			/* Will the connection be persistent? */
		$this->host			= '127.0.0.1';		/* Host to connect to if $socket is false */
		$this->port			= 3306;				/* Port to connect to if $socket is false */
		$this->socket		= false;			/* if this is not false we will try to connect to this UNIX Socket*/
		$this->engine		= 'mysql';			/* Engine to use */
		$this->user			= '';				/* Username of the Database */
		$this->pass			= '';				/* Passworf of the Database */
		$this->database		= '';				/* Name of the Database */
		$this->charset		= 'UTF8';			/* Charset of the database */
		$this->prefix		= '';				/* Prefix of the database (not implemented) */
		$this->log_queries	= false;			/* If we should log SQL Queries */
		/* Misc Options */
		$this->retries		= 3;				/* Number of retries before we give up connecting */
		$this->wait			= 1;				/* Number of seconds to wait between Retries */
		$this->between		= 5;				/* Number of seconds to wait before retrying connection */
		/* even tough wait and between sound very similar, the wait parameter only defines
		 * how many seconds to SLEEP while retrying the same QUERY, but after the amount of
		 * retries it will give up and return false every QUERY, until the BETWEEN parameter
		 * has elapsed, then it will retry once again the connection, this is useful to use
		 * when in a daemon or long-run mode
		 * if connection fails, it will invalidate database queries for up between amount of time */
		return true;
	}
	/**
	 * will attempt to connect to the database, unless an attempt was made
	 * recently with no success
	 * @access public
	 * @version 3.0.1
	 * @return bool on success
	 */
	public function connect() {
		if ($this->retry>=$this->retries) {
			$tiempo = time()-$this->retry_ts;
			if ($tiempo<=$this->between) {
				return false;
			}
			$this->retry = 0;
		}
		$dsn = $this->engine.':';
		if ($this->socket!==false) {
			$dsn .= 'unix_socket='.$this->socket.';';
		} else {
			switch ($this->engine) {
				case 'dblib':
					$dsn .= 'host='.$this->host.':';
					$dsn .= 'port='.$this->port.';';
					break;
				case 'mysql':
					$dsn .= 'host='.$this->host.':';
					$dsn .= 'port='.$this->port.';';
					break;
				default:
					trigger_error('Database: Unknown engine ('.$this->engine.')',E_USER_NOTICE);
					return false;
					break;
			}
		}
		if (strlen($this->database)>0) {
			$dsn .= 'dbname='.$this->database.';';
		}
		$options = array(PDO::ATTR_PERSISTENT => $this->persistent);
		try {
			$this->connected = true;
			$this->object = new PDO($dsn,$this->user,$this->pass,$options);
		} catch (PDOException $e) {
			$this->object = false;
			$this->connected = false;
			trigger_error('Database: Error Connecting to database ('.$this->user.'@'.$this->host.') Message: '.$e->getMessage(),E_USER_NOTICE);
		}
		if ($this->connected) {
			$pdo = &$this->object;
			try {
				$pdo->query('SET SESSION `character_set_results` = \''.$this->charset.'\'');
			} catch (PDOException $e) {
				$pdo = false;
				$this->connected = false;
				trigger_error('Database: Unknown error setting charset \''.$this->charset.'\' ('.$this->name.':'.$this->user.'@'.$this->host.') Message: '.$e->getMessage(),E_USER_NOTICE);
			}
			try {
				$pdo->query('SET SESSION `character_set_client` = \''.$this->charset.'\'');
			} catch (PDOException $e) {
				$pdo = false;
				$this->connected = false;
				trigger_error('Database: Unknown error setting charset \''.$this->charset.'\' ('.$this->name.':'.$this->user.'@'.$this->host.') Message: '.$e->getMessage(),E_USER_NOTICE);
			}
			$this->retry = 0;
			return true;
		}
		$this->retry++;
		$this->retry_ts = time();
		if ($this->retry<=$this->retries) {
			trigger_error('Database: Database Connection not stablished... sleeping for '.$this->wait.' seconds...',E_USER_NOTICE);
			sleep($this->wait);
			return $this->connect();
		}
		trigger_error('Database: We reached the end of the script, something is very wrong!',E_USER_NOTICE);
		return false;
	}
	/**
	 * Will execute the $query
	 * @access public
	 * @version 2.0.1
	 * @param string $query query to execute
	 * @return array with the result of the query
	 */
	public function query($query) {
		$call_string = 'Database::query('.var_export($query,true).') ';
		$db = &$this->object;
		if (!$this->connected) {
			$connection = $this->connect($db);
			if (!$connection) {
				trigger_error($call_string.'could not connect to database',E_USER_NOTICE);
				return false;
			}
		}
		if (!is_object($this->object)) {
			trigger_error($call_string.'for some strange reason we think we are connected but object is not an object',E_USER_NOTICE);
			$this->connected = false;
			return false;
		}
		try {
			$result = $db->query($query);
		} catch (Exception $e) {
			trigger_error($call_string.$e->getMessage(),E_USER_NOTICE);
			if (is_object($this->object)) {
				$status = $this->object->getAttribute(PDO::ATTR_SERVER_INFO);
				if ($status===false) {
					$this->connected = false;
				}
			} else {
				$this->connected = false;
			}
		}
		$this->queries++;
		if ($result) {
			if (
				substr($query,0,6)!='UPDATE'
			 && substr($query,0,6)!='INSERT'
			 && substr($query,0,7)!='REPLACE'
			 && substr($query,0,6)!='CREATE'
			 && substr($query,0,6)!='DELETE'
			) {
				$return = array();
				foreach ($result as $rows) {
					$row = array();
					foreach ($rows as $name => $value) {
						if (!is_int($name)) {
							$row[$name] = $value;
						}
					}
					$return[] = $row;
				}
				return $return;
			}
			if (substr($query,0,6)=='INSERT') {
				return $db->lastInsertId();
			}
			foreach ($query as $row) {
				trigger_error($call_string.'Unknown rowtype?: '.var_export($row,true),E_USER_NOTICE);
			}
			return false;
		}
		return false;
	}
}