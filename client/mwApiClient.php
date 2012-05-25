#!/usr/bin/php -f
<?php

require_once "$IP/includes/Defines.php";
require_once "$IP/includes/AutoLoader.php";
require_once "$IP/includes/GlobalFunctions.php";
require_once "$IP/includes/DefaultSettings.php";

class mwApiClientIterator implements Iterator { 
	private $api;
	private $data;
	private $limit = 50;
	private $offset = null;
	private $items = array();
	private $method = "GET";
	private $state = "new";

	public function __construct( mwApiClient $api, $offset = null ) {
		$this->api = $api;

		$this->data['lelimit'] = $this->limit;
		$this->data['lestart'] = $offset;
		$this->offset = $offset;
	}

	public function findState( $type ) {
		$this->method = "POST";
		$this->state = $type;
	}

	public function rewind( ) {
		$this->ptr = 0;
		$this->fetchNext();
		return true;
	}

	public function current( ) {
		return $this->items[$this->ptr];
	}

	function key( ) {
		return $this->ptr + $this->offset - 1;
	}

	function next( ) {
		$this->ptr++;
		if( $this->ptr >= count( $this->items ) ) {
			$this->fetchNext();
		}
	}

	function valid( ) {
		return isset( $this->items[$this->ptr] );
	}

	private function fetchNext( ) {
		if( $this->offset !== false ) {
			$this->data['lestart'] = $this->offset;
			$status = $this->api->request( $this->data );

			$res = $this->api->getResult( $this->method, $this->state );
			if( isset( $res['query-continue']['logevents']['lestart'] ) ) {
				$this->offset = $res['query-continue']['logevents']['lestart'];
			} else {
				$this->offset = false;
			}

			$events = array();

			if( isset( $res['query'] ) ) { 
				foreach( $res['query']['logevents'] as $e ) {
					if($e['pageid'] == 0) {
						$ret = $this->resolveTitle( $e['title'] );
					}

					$events[] = $e;
				}
			}
			$this->fetchImageInfo( $events );
		}
	}

	private function fetchImageInfo( $partial, $depth = 0 ) {
		$images = array();

		$f = function ($v) {return $v['pageid'];};

		$q = implode("|", array_map($f, $partial));

		$res = $this->api->request(
			array("action" => "query",
				"pageids" => $q,
				"prop" => "imageinfo",
				"format" => "json",
				"iiprop" => "size") );
		if( !isset( $res ) ) {
			throw new Exception( var_export( $res ) );
		}
		if( !isset($res['query']) || !isset($res['query']['pages']) ) {
			var_dump($res);
			throw new Exception("Strangeness!");
		}
		foreach(array_values($res['query']['pages']) as $file) {
			if( isset( $file['missing'] ) && $file['missing'] === "" && isset( $file['title'] ) )  {
				$ret = $this->resolveTitle( $file['title'] );
				if(!isset($ret['imageinfo'][0]['size'])) $ret['imageinfo'][0]['size'] = 0;
				$images[$file['pageid']] = $this->fileToArray( $ret );

			} else if( isset( $file['missing'] ) && !isset( $file['title'] ) ) {
				# nothing here
			} else if( !isset( $file['imagerepository'] ) || $file['imagerepository'] === "" ) {
				# probably deleted
			} else if( !isset( $file['imageinfo'] ) ) {
				var_dump($file);
				var_dump($res);
				throw new Exception("Missing ImageInfo");
			} else
				$images[$file['pageid']]['imageinfo'] = $file['imageinfo'];
		}
		foreach($partial as $file) {
			if(isset($file['pageid']) && isset($images[$file['pageid']]['imageinfo']) && $images[$file['pageid']]['imageinfo']) {
				$images[$file['pageid']] = array_merge( $images[$file['pageid']], $this->fileToArray( $file ));

			} else if ($file['pageid'] !== 0 && isset($file['imageinfo'])) {
				var_dump($file);
				throw new Exception("How did I end up here?");

			} else if( !isset($file['imageinfo']) ) {
				$ret = $this->resolveTitle( $file['title'] );
				$images[$file['pageid']] = $this->fileToArray( $ret );

			} else {
				echo "Incomplete: {$file['title']}/{$file['pageid']}\n";
			}

			if( $file['pageid'] == 0 && substr($images[0]['comment'],0,7) !== "DELETE:" ) {
				/* TODO Figure out something better */
				unset($images[0]);
			}

			if( !isset( $file['title'] ) ) {
				var_dump($file);
				throw new Exception("Missing Title");
			}
		}
		krsort($images);
		$this->items = array_merge( $this->items, array_values($images) );
	}

	private function fileToArray( $file ) {
		$fields = array( "title", "timestamp", "pageid", "user", "comment" );
		$ret = array();

		foreach($fields as $f) {
			if(!isset($file[$f])) {
				echo "Missing field '$f'\n";
				$ret[$f] = "";
			} else {
				$ret[$f] = $file[$f];
			}
		}
		if( isset($file['imageinfo']) ) {
			$ret['imageinfo'] = $file['imageinfo'];
		}
		return $ret;
	}

	private function resolveTitle( $title, $imageinfo = true ) {
		$ret = array();
		$res = $this->api->request(
			array("action" => "query",
				"list" => "logevents",
				"letitle" => "$title",
				"ledir" => "newer",
				"format" => "json") );

		foreach($res['query']['logevents'] as $l) {
			$ret['timestamp'] = $l['timestamp'];
			$ret['user'] = $l['user'];
			$ret['title'] =  $l['title'];
			$ret['pageid'] = $l['pageid'];
			$ret['comment'] = $l['comment'];
			if( isset( $l['action'] ) ) {
				if($l['action'] == "move") {
					$ret['comment'] = "MOVED: {$l['move']['new_title']} -- {$ret['comment']}";
					echo "MOVED: $title to {$l['move']['new_title']}\n";
					if($imageinfo) {
						list($ret['pageid'], $ret['imageinfo']) = $this->getImageInfo($l['move']['new_title']);
						if(!isset($ret['imageinfo'][0]['size'])) $ret['imageinfo'][0]['size'] = 0;
					} else {
						$r = $this->resolveTitle($l['move']['new_title']);
						$ret['title'] = $r['title'];
						$ret['pageid'] = $r['pageid'];
					}
				} else if($l['action'] == "upload"
						|| $l['action'] == "patrol"
						|| $l['action'] == "delete"
						|| $l['action'] == "restore"
						|| $l['action'] == "protect"
						|| $l['action'] == "unprotect"
						|| $l['action'] == 'overwrite') {
					$action = strtoupper($l['action']);

					$ret['comment'] = "$action: {$ret['comment']}";
					if(!isset($ret['imageinfo'])) $ret['imageinfo'][0]['size'] = 0;
					echo "$action: $title\n";
				} else {
					$ret['comment'] = "UNKNOWN ACTION: {$l['action']} -- {$ret['comment']}";
					$ret['imageinfo'][0]['size'] = 0;

					echo "??? {$l['action']}: $title\n";
				}
			}
		}

		return $ret;
	}

	private function getImageInfo( $title ) {
		$res = $this->api->request(
			array("action" => "query",
				"titles" => $title,
				"prop" => "imageinfo",
				"format" => "json",
				"iiprop" => "size") );

		$page = array_values($res['query']['pages']);
		if ( isset($page['imageinfo'][0]) ) {
			return array($page['pageid'], $page['imageinfo']);
		} else if ( isset($page[0]['imageinfo']) ) {
			return array($page[0]['pageid'], $page[0]['imageinfo']);
		} else if ( isset( $page[0]['imagerepository'] ) && $page[0]['imagerepository'] === "" ){
			return array($page[0]['pageid'], array("size" => 0 ) );
		} else {
			var_dump($page);
			throw new Exception("Should never get here");
		}
	}
}

class mwApiClient {
	private $api = null;
	private $url = null;
	private $cookieJar = null;
	private $result;
	private static $productList = array();

	public function __construct( $url, $user = null, $password = null, $debug = false ) {

		$this->url = $url;

		if($user && $password) {
			$this->request(
				array("action" => "login",
					"lgname" => $user,
					"format" => "json",
					"lgpassword" => $password ) );

			$res = $this->result;
			if( isset($res['login']['result']) && $res['login']['result'] == 'NeedToken' ) {
				$this->request(
					array("action" => "login",
						"lgname" => $user,
						"format" => "json",
						"lgtoken" => $res['login']['token'],
						"lgpassword" => $password ) );

			}
		}
	}

	public function request( $request ) {
		$h = MWHttpRequest::factory($this->url, array('method' => "POST", 'postData' => $request));

		if(!$this->cookieJar) $this->cookieJar = $h->getCookieJar();
		$h->setCookieJar($this->cookieJar);
		$status = $h->execute();

		/* Weirdly get an error here sometimes */
		if( !$status->isOK() && $h->getStatus() == 200 ) {
			sleep(10);
			$status = $h->execute();
		}
		$this->cookieJar = $h->getCookieJar();
		$this->result = json_decode( $h->getContent(), true );

		if( !$status->isOK() ) {
			throw new Exception("Problem with request: ". $h->getContent(). ": ". $h->getStatus());
		}

		if( isset( $this->result['error']['info'] ) ) {
			throw new Exception("error: ". $this->result['error']['info']);
		}
		return $this->result;
	}

	public function getResult( ) {
		return $this->result;
	}

	public function listUploadsFrom($userName) {
		return new mwApiClientIterator($this, array(
				"action" => "query",
				"list" => "logevents",
				"leuser" => $userName,
				"leaction" => "upload/upload",
				"prop" => "info",
				"format" => "json"
			)
		);
	}

	public function listAllUploads( $offset = null ) {
		return new mwApiClientIterator($this, array(
				"action" => "query",
				"list" => "logevents",
				"leaction" => "upload/upload",
				"format" => "json"
			), $offset );
	}
}