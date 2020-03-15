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
		$this->start		= microtime(true);			/* time we started the class */
		$this->errorprint	= false;					/* if we should print out the errors */
		$this->timezone		= 'America/Mexico_City';	/* timezone we will set the PHP (http://www.php.net/manual/en/timezones.php) */
		$this->name			= 'NuCoKe';					/* this is the version of the user's proyect'*/
		$this->version		= array(1,0,0);				/* this is the version of the user's proyect'*/
		$this->charset		= 'UTF-8';					/* the Charset we will be working on */
		$this->path			= getcwd();					/* the Main path we will be working on */
		/* we will parse any configuration added from user end */
		if (!is_array($config)) { $config = array(); trigger_error('NuCoKe: Unknown configuration type passed on __initialize($config)',E_USER_NOTICE); }
		foreach ($config as $name => $value) {
			switch ($name) {
				case 'errorprint':
					if ($value) { $this->errorprint = true; } else { $this->errorprint = false; } break;
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
		return true;
	}
}