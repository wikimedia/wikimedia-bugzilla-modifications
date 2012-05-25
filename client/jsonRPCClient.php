<?php
/*
					COPYRIGHT

Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>

This file is part of JSON-RPC PHP.

JSON-RPC PHP is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

JSON-RPC PHP is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with JSON-RPC PHP; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */

class jsonRPCClient {

	/**
	 * Debug state
	 *
	 * @var boolean
	 */
	private $debug = false;

	/**
	 * The server URL
	 *
	 * @var string
	 */
	private $url;
	/**
	 * The request id
	 *
	 * @var integer
	 */
	private $id;
	/**
	 * If true, notifications are performed instead of requests
	 *
	 * @var boolean
	 */
	private $notification = false;

	private $cookieJar = array();

	/**
	 * Takes the connection parameters
	 *
	 * @param string $url
	 * @param boolean $debug
	 */
	public function __construct($url,$debug = false) {
		// server URL
		$this->url = $url;
		// proxy
		empty($proxy) ? $this->proxy = '' : $this->proxy = $proxy;
		// debug state
		if($debug) $this->debug = true;
		// message id
		$this->id = 1;
	}

	/**
	 * Sets the notification state of the object. In this state, notifications are performed, instead of requests.
	 *
	 * @param boolean $notification
	 */
	public function setRPCNotification($notification) {
		empty($notification) ?
			$this->notification = false
			:
			$this->notification = true;
	}

	/**
	 * Performs a jsonRCP request and gets the results as an array
	 *
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	public function __call($method, $params = null) {
		// sets notification or request task
		if ($this->notification) {
			$currentId = NULL;
		} else {
			$currentId = $this->id;
		}
		// prepares the request
		$request = array(
			'version' => '1.1',
			'method' => $method,
		);
		if( $params ) {
			$request['params'] = $params;
		}
		$request = json_encode($request);
		$this->debug && $this->debug='***** Request *****'."\n".$request."\n".'***** End Of request *****'."\n\n";

		// performs the HTTP POST
		$h = MWHttpRequest::factory($this->url, array('method' => "POST", 'postData' => $request));
		$h->setHeader('Content-Type', "application/json");

		$status = $this->executeWithCookies( $h );

		$response = $h->getContent();

		if ( $status->isOk() ) {
			$this->debug && $this->debug.='***** Server response *****'."\n".$response.'***** End of server response *****'."\n";
			$response = json_decode($response,true);
		} else {
			throw new Exception(html_entity_decode(preg_replace("#<[^<>]*>#", "", $h->getContent())));
		}

		// debug output
		if ($this->debug) {
			echo $this->debug;
		}

		// final checks and return
		if (!$this->notification) {
			// check
			if (isset($response['id']) && $response['id'] != $currentId) {
				throw new Exception('Incorrect response id (request id: '.$currentId.', response id: '.$response['id'].')');
			}
			if (isset($response['error']) && !is_null($response['error'])) {
				$err = $response['error'];
				if(isset($err['message']) && isset($err['code'])) {
					throw new Exception($err['message'], $err['code']);
				} else if(isset($err['message'])) {
					throw new Exception('Request error: '.$err['message']);
				} else {
					throw new Exception('Request error: '.var_export($err, true));
				}
			}

			return $response['result'];
		} else {
			return true;
		}
	}

	public function executeWithCookies( $handle ) {
		if(!$this->cookieJar) $this->cookieJar = $handle->getCookieJar();
		$handle->setCookieJar($this->cookieJar);
		$status = $handle->execute();
		$this->cookieJar = $handle->getCookieJar();
		return $status;
	}
}