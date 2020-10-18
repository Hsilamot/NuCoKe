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
use Tomalish\Database;

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
	 * get remote address user agent if available
	 * @access public
	 * @version 1.0.2
	 * @return string remote address
	 */
	public static function agent_get() {
		if (php_sapi_name()=='cli') {
			$agent = '';
			if (isset($_SERVER['USER'])) { $agent .= $_SERVER['USER'].'@'; } else { $agent .= 'unknown@'; }
			if (isset($_SERVER['HOSTNAME'])) { $agent .= $_SERVER['HOSTNAME'].' ('; } else { $agent .= '::1 ('; }
			if (isset($_SERVER['SSH_TTY'])) { $agent .= $_SERVER['SSH_TTY'].';'; } else { $agent .= 'unknowntty;'; }
			if (isset($_SERVER['SSH_CONNECTION'])) { $agent .= $_SERVER['SSH_CONNECTION'].')'; } else { $agent .= 'unknown)'; }
			return $agent;
		} else {
			if (isset($_SERVER['HTTP_USER_AGENT'])) {
				return $_SERVER['HTTP_USER_AGENT'];
			} else {
				return 'unknown (unknown)';
			}
		}
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
	 * will return the host to which the request was sent
	 * @access public
	 * @version 1.0.1
	 * @return string returns the host (either FQDN or IP)
	 */
	public static function host_get() {
		$host = '::1';
		if (php_sapi_name()=='cli') {
			if (isset($_SERVER['HOSTNAME'])) {
				$host = $_SERVER['HOSTNAME'];
			} elseif (isset($_SERVER['SSH_CONNECTION'])) {
				$host = explode(' ',$_SERVER['SSH_CONNECTION']);
				if (count($host)>2) {
					$host = $host[2];
				}
			}
		} else {
			if (isset($_SERVER['HTTP_HOST'])) {
				$host = $_SERVER['HTTP_HOST'];
			} elseif (isset($_SERVER['SERVER_NAME'])) {
				$host = $_SERVER['SERVER_NAME'];
			} elseif (isset($_SERVER['SERVER_ADDR'])) {
				$host = $_SERVER['SERVER_ADDR'];
			}
		}
		return $host;
	}
	/**
	 * will return the integer of the port to which the connection was stablished
	 * @access public
	 * @version 1.0.1
	 * @return int port number
	 */
	public static function host_port_get() {
		$port = 0;
		if (php_sapi_name()=='cli') {
			if (isset($_SERVER['SSH_CONNECTION'])) {
				$port = explode(' ',$_SERVER['SSH_CONNECTION']);
				if (count($port)>3) {
					$port = $port[3];
				}
			}
		} else {
			if (isset($_SERVER['SERVER_PORT'])) {
				$port = $_SERVER['SERVER_PORT'];
			}
		}
		return $port;
	}
	/**
	 * will decode a 16-byte binary ip into normal string
	 * 2019-08-22 added IPv6 Compatible encoding
	 * @access public
	 * @version 1.1.44
	 * @param binary $ip 4-byte IP
	 * @return string return full decoded ip IPv6 format
	 */
	public static function ip_decode($ip) {
		return inet_ntop($ip);
	}
	/**
	 * will encode a string IP into a hexadecimal string
	 * 2019-08-22 added IPv6 Compatible encoding
	 * @access public
	 * @version 1.1.86
	 * @param string $ip normal x.x.x.x ip
	 * @return string return 32x 0 to F hexadecimal string
	 */
	public static function ip_encode($ip) {
		$pack = inet_pton($ip);
		if (strlen($pack)<16&&strlen($pack)==4) {
			$pack = str_repeat(chr(0), 10).str_repeat(chr(255), 2).$pack;
		}
		return $pack;
		// código viejo
		$pack = bin2hex($pack);
		if (strlen($pack)==32) {
			return '0x'.$pack;
		} else {
			return '0x'.str_pad('ffff'.$pack,32,'0',STR_PAD_LEFT);
		}
	}
	/**
	 * get remote address if available, localhost on fail
	 * @access public
	 * @version 1.0.13
	 * @return string remote address
	 */
	public static function ip_get() {
		$ip = '0000:0000:0000:0000:0000:0000:0000:0001';
		if (php_sapi_name()=='cli') {
			if (isset($_SERVER['SSH_CLIENT'])) {
				$ip = explode(' ',$_SERVER['SSH_CLIENT']);
				$ip = $ip[0];
			}
		} else {
			if (isset($_SERVER['REMOTE_ADDR'])) {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
		}
		$ip = inet_ntop(NuCoKe::ip_encode($ip));
		return $ip;
	}
	/**
	 * return the working dir path
	 * @access public
	 * @version 1.0.1
	 * @return string with the working dir path
	 */
	public function path_get() {
		return $this->path;
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
	/**
	 * will convert a decimal number to a hexadecimal representation for SQL insertion
	 * @access public
	 * @version 1.0.38
	 * @param string $string representation of the string
	 * @return string with hexadecimal representation
	 */
	public static function sql_dechex($value) {
		if (strlen($value)>0) {
			return '0x'.dechex(intval($value));
		} else {
			return 'NULL';
		}
	}
	/**
	 * will convert a text to a hexadecimal representation for SQL insertion
	 * @access public
	 * @version 1.0.2
	 * @param string $string representation of the string
	 * @return string with hexadecimal representation or null if empty
	 */
	public static function sql_texthex($string) {
		if (strlen($string)>0) {
			return '0x'.bin2hex($string);
		} else {
			return 'NULL';
		}
	}
	/**
	 * will kill anything that can destroy our precious SQL sentences
	 * @access public
	 * @version 1.0.5
	 * @param string $string part of the query to vaccine
	 * @return string with vaccioned string
	 */
	public static function sql_vaccine($string) {
		$end = str_replace(chr(92).chr(39),chr(92).chr(92).chr(39),$string);
		$end = str_replace(chr(39),chr(92).chr(39),$end);
		return $end;
	}
	/**
	 * will get the current url
	 * @access public
	 * @version 1.0.5
	 * @param string $string part of the query to vaccine
	 * @return string with vaccioned string
	 */
	public static function url_get($params=array()) {
		$unparsed_url = '';

		$default_params = array();
		$default_params['host'] = NuCoKe::host_get();
		$default_params['port'] = NuCoKe::host_port_get();

		if (php_sapi_name()=='cli') {
			$default_params['scheme'] = 'ssh';
			if (isset($_SERVER['PWD'])) {
				$default_params['path'] = $_SERVER['PWD'];
				if (isset($_SERVER['argv'])&&isset($_SERVER['argv'][0])) {
					$default_params['path'] .= '/'.$_SERVER['argv'][0];
				}
			}
			if (isset($_SERVER['argv'])&&count($_SERVER['argv'])>1) {
				$argv = $_SERVER['argv'];
				array_shift($argv);
				$default_params['query'] = http_build_query($argv);
			}
		} else {
			if (isset($_SERVER['REQUEST_SCHEME'])) { $default_params['scheme'] = $_SERVER['REQUEST_SCHEME']; } else { $default_params['scheme'] = 'unknown'; }
			if (isset($_SERVER['REQUEST_URI'])) {
				$extract = parse_url('https://0.0.0.0'.$_SERVER['REQUEST_URI']);
				if (isset($extract['path'])) {
					$default_params['path'] = $extract['path'];
				}
				if (isset($extract['query'])) {
					$default_params['query'] = $extract['query'];
				}
				if (isset($extract['fragment'])) {
					$default_params['fragment'] = $extract['fragment'];
				}
			}
		}

		$params = array_merge(
			$default_params,
			$params
		);

		if (isset($params['scheme'])) {
			$unparsed_url .= $params['scheme'].'://';
		}

		if (isset($params['user'])) {
			$unparsed_url .= $params['user'];
			if (isset($params['pass'])) {
				$unparsed_url .= ':'.$params['pass'];
			}
			$unparsed_url .= '@';
		}

		if (isset($params['host'])) {
			$unparsed_url .= $params['host'];
		}

		if (isset($params['port'])) {
			if (!isset($params['scheme']) //si no hay scheme ponemos el puerto
				||$params['scheme']=='http'&&$params['port']!=80 // solo si no es 80 y viene por http
				||$params['scheme']=='https'&&$params['port']!=443 // solo si no es 443 y viene por https
				||$params['scheme']!='http'&&$params['scheme']!='https'
				) {
				$unparsed_url .= ':'.$params['port'];
			}
		}

		if (isset($params['path'])) {
			$unparsed_url .= $params['path'];
		}

		if (isset($params['query'])) {
			$unparsed_url .= '?'.$params['query'];
		}

		if (isset($params['fragment'])) {
			$unparsed_url .= '#'.$params['fragment'];
		}

		return $unparsed_url;
	}
}