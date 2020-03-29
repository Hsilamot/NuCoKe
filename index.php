<?php
/**
 * Index File for N.u.C.o.K.e. v 4.0.1
 * © Hsilamot 2020
 * +----------------------------------------------------------------------+
 * |                      N.U.C.O.K.E. API v 4.0.1                        |
 * |             released under Creative Commons BY-NC-SA 3.0             |
 * |          http://creativecommons.org/licenses/by-nc-sa/3.0/           |
 * +----------------------------------------------------------------------+
 * @author Hsilamot <php@hsilamot.com>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @version 4.0.1
 */
require_once('vendor/autoload.php');
spl_autoload_register(function($className) {
	$className = str_replace('Tomalish\\','',$className);
	if (file_exists($className .'.php')) {
		require_once($className .'.php');
	}
});

#require_once('src/NuCoKe.php');
use Tomalish\NuCoKe;
use Tomalish\Laira;

$nucoke = new NuCoKe(
						array(
							'name'					=>	'Tomalish.Networks'				/* name of the project */
							,'version'				=>	'9.0'							/* version of the project */
							,'path'					=>	'/user/tomalish/nucoke'			/* path of the project */
							,'charset'				=>	'UTF-8'							/* charset of the files */
							,'timezone'				=>	'America/Mexico_City'			/* timezone of the project*/
							,'errorprint'			=>	true 							/* This is a Debug Line*/
							,'db_default'			=>	'tomalish'
							)
					);

$result = $nucoke->db_add('tomalish',array(
												 'socket'		=> '/var/lib/mysql/mysql.sock'
												,'user'			=> 'tomalish_user'
												,'pass'			=> 'nMBt10qWEI4qRqBvrI'
												,'database'		=> 'tomalish_db'
												,'log_queries'	=> true /* log SQL Queries */
											));

$laira = new Laira();
$laira->version = array(1,0,5);

$laira->end();

$result = $nucoke->sql('SELECT NOW() as `ahora`');
echo 'No debería ser fatal';
var_dump($result);
echo chr(10);