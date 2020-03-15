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
require_once('src/index.php');
use Tomalish\NuCoKe;

$nucoke = new NuCoKe(
						array(
							'name'				=>	'Tomalish.Networks'					/* name of the project */
							,'version'				=>	'9.0'							/* version of the project */
							,'path'					=>	'/user/tomalish/nucoke'			/* path of the project */
							,'charset'				=>	'UTF-8'							/* charset of the files */
							,'timezone'				=>	'America/Mexico_City'			/* timezone of the project*/
							,'errorprint'			=>	true 							/* This is a Debug Line*/
							)
					);