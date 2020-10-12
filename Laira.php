<?php
/**
 * Laira API Class
 * © Hsilamot 2014-2020
 * developed by Hidalgo Rionda
 *
 * +----------------------------------------------------------------------+
 * |                           Laira v 1.0.1                              |
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
use Firebase\JWT as JWT;
use \JsonException as JsonException;

class Laira {
	/**
	 * This contains the HTTP Response code to end the call
	 * @access private
	 * @var int
	 */
	private $http_code;
	/**
	 * This contains an array with the Human readable messages to send back to the client
	 * @access private
	 * @var array
	 */
	private $messages;
	/**
	 * This contains an array with the response to send to the client
	 * @access private
	 * @var array
	 */
	private $payload;
	/**
	 * This tells the client if the result was successful or not
	 * @access public
	 * @var result
	 */
	public $result;
	/**
	 * This contains an array with the API Version
	 * @access public
	 * @var array
	 */
	public $version;
	public function __construct() {
		$this->http_code	= false;		/* The HTTP Result Code */
		$this->result		= false;		/* If the request was successful */
		$this->messages		= array();		/* This contains an array of messages to show */
		$this->version		= array(0,0,0); /* The Version of the API */
		$this->payload		= array();		/* Payload Contents */
		return true;
	}
	public function msg($type='info',$msg='') {
		$this->messages[] = array('type'=>$type,'message'=>$msg);
		return true;
	}
	public function addPayload($name='',$content='') {
		$this->payload[$name] = $content;
		return true;
	}
	public function end($code=false,$msg=false,$return=false) {
		/* PREFLIGHT CHECK */
		header('Access-Control-Allow-Origin: *'); /* Dejamos que las solicitudes vengan de cualquier dominio */
		header("Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS"); /* Aceptamos los metodos */
		header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, appid, version'); /* Permitimos cabeceras */
		if ($code) {
			$this->http_code = $code;
		}
		if ($msg) {
			$this->msg('unknown',$msg);
		}
		if ($return) {
			$this->result = true;
		}
		if (isset($_SERVER['SERVER_PROTOCOL'])) {
			$server_protocol = $_SERVER['SERVER_PROTOCOL'];
		} else {
			$server_protocol = 'CLI';
		}
		if (isset($_SERVER['REQUEST_METHOD'])) {
			$request_method = $_SERVER['REQUEST_METHOD'];
		} else {
			$request_method = 'CLI';
		}
		if($request_method == 'OPTIONS'){
			/* Estamos en preflight, respondemos apropiadamente... */
			header($server_protocol.' 200 OK');
			exit(0);
		}
		switch ($this->http_code) {
			case 200: header($server_protocol.' 200 OK'); break;
			case 400: header($server_protocol.' 400 Bad Request'); break;
			case 401: header($server_protocol.' 401 Unauthorized'); break;
			case 402: header($server_protocol.' 402 Payment Required'); break;
			case 403: header($server_protocol.' 403 Forbidden'); break;
			case 404: header($server_protocol.' 404 Not Found'); break;
			case 405: header($server_protocol.' 405 Method Not Allowed'); break;
			case 406: header($server_protocol.' 406 Not Acceptable'); break;
			case 407: header($server_protocol.' 407 Proxy Authentication Required'); break;
			case 408: header($server_protocol.' 408 Request Timeout'); break;
			case 409: header($server_protocol.' 409 Conflict'); break;
			case 410: header($server_protocol.' 410 Gone'); break;
			case 412: header($server_protocol.' 412 Precondition Failed'); break;
			case 413: header($server_protocol.' 413 Request Entity Too Large'); break;
			case 415: header($server_protocol.' 415 Unsupported Media Type'); break;
			case 418: header($server_protocol.' 418 I\'m a teapot'); break;
			case 420: header($server_protocol.' 420 Enhance Your Calm'); break;
			case 423: header($server_protocol.' 423 Locked'); break;
			case 428: header($server_protocol.' 428 Precondition Required'); break;
			case 429: header($server_protocol.' 429 Too Many Requests'); break;
			case 451: header($server_protocol.' 451 Unavailable For Legal Reasons'); break;
			case 500: header($server_protocol.' 500 Internal Server Error'); break;
			case 501: header($server_protocol.' 501 Not Implemented'); break;
			case 502: header($server_protocol.' 502 Bad Gateway'); break;
			case 503: header($server_protocol.' 503 Service Unavailable'); break;
			case 504: header($server_protocol.' 504 Gateway Timeout'); break;
			case 505: header($server_protocol.' 505 HTTP Version Not Supported'); break;
			case 507: header($server_protocol.' 507 Insufficient Storage'); break;
			case 508: header($server_protocol.' 508 Loop Detected'); break;
			case 509: header($server_protocol.' 509 Bandwidth Limit Exceeded'); break;
			case 510: header($server_protocol.' 510 Not Extended'); break;
			default:  header($server_protocol.' 500 Internal Server Error'); break;
		}
		header('Content-Type: application/json'); // Las respuestas se proporcionan en JSON
		$payload = $this->payload;
		$payload['messages'] = $this->messages;
		$payload['result'] = $this->result;
		$payload['version'] = $this->version;
		try {
			$options = JSON_THROW_ON_ERROR;
			if ($server_protocol=='CLI') {
				$options &= JSON_PRETTY_PRINT;
			}
			$string = json_encode($payload,$options);
		} catch (JsonException $e) {
			$msg = 'JSON Exception Message: '.$e->getMessage();
			trigger_error($msg,E_USER_NOTICE);
			$this->result	= false;
			$this->msg('error',$msg);
			$this->payload	= array();
			$this->end();
		}
		echo $string;
		if ($server_protocol=='CLI') {
			echo chr(10);
		}
		exit(0);
	}
}