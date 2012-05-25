#!/usr/bin/php -f
<?php

require_once "$IP/includes/Defines.php";
require_once "$IP/includes/AutoLoader.php";
require_once "$IP/includes/GlobalFunctions.php";
require_once "$IP/includes/DefaultSettings.php";

class GmaneWebClient {
	private $url;

	function __construct( $url = "http://download.gmane.org/" ) {
		$this->url = $url;
	}

	public function getMbox( $group, $count = 100, $start = 0 ) {
		return new GmaneMbox( $group, $count, $this, $start );
	}

	public function getArchiveChunk( $group, $offset, $size ) {
		echo "\n".$this->url . $group . "/$offset/".($offset+$size), "\n";
		$h = HttpRequest::factory( $this->url . $group . "/$offset/".($offset+$size));
		$status = $h->execute();
		if( $status->isOK() ) {
			$txt = $h->getContent();
			return substr( $txt, 0, strlen( $txt ) - 1 );
		} else {
			throw new Exception($status->getMessage());
		}
	}
}

class GmaneMbox implements Iterator {
	private $gmane;
	private $chunkSize;
	private $items = array();
	private $group;
	private $ptr = 0;
	private $offset = 0;

	public function __construct( $group, $count, GmaneWebClient $gmane, $start ) {
		$this->gmane = $gmane;
		$this->chunkSize = $count;
		$this->group = $group;
		$this->offset = $start;
	}

	public function rewind( ) {
		$this->ptr = 0;
		$this->chunk = array();
		$this->fetchNext();
		return true;
	}

	public function current( ) {
		return new GmaneMessage( $this->items[$this->ptr] );
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

	static private function prefixChunk($a) {
		if( substr( $a, 0, 5 ) === "From " ) return $a;
		return "From $a";
	}

	function fetchNext( ) {
		$chunk = $this->gmane->getArchiveChunk( $this->group, $this->offset, $this->chunkSize );
		$chunks = array_map( array('self', 'prefixChunk'), preg_split("/^\n(From )/sm", $chunk, $this->chunkSize) );
		$this->items = array_merge( $this->items, $chunks );

		$this->offset += count($chunks);
		$this->ptr++;
	}
}

class GmaneMessage {
	public $header;
	public $body;
	public $raw;
	public $description;

	public function __call($name, $args) {
		if( isset( $this->header[ $name ][0] ) ) {
			return $this->header[ $name ][0];
		} elseif( isset( $this->header[ "x-bz-".strtolower($name) ][0] ) ) {
			return $this->header[ "x-bz-".strtolower($name) ][0];
		} else {
			switch( $name ) {
				case "who":
					return $this->header[ "x-bz-reportedby" ][0];
				case "version":
					return "unspecified";
			}
					/* var_dump($this); */
					/* var_dump($name); */
					/* exit; */
		}
	}

	public function __construct( $mail ) {
		list($header, $body) = explode( "\n\n", $mail, 2 );

		$this->raw = $mail;
		$this->parseHeader( $header );
		$this->body = $body;
	}

	public function parseHeader( $header ) {
		$line = explode("\n", $header);
		array_shift($line);
		$field = null;
		foreach($line as $l) {
			if( substr($l, 0, 1) === "\t" || substr($l, 0, 1) === " " ) {
				if( $field === null ) {
					throw new Exception("Don't know what to do with this line: $l\n");
				}
				$last = array_pop( array_keys( $this->header[$field] ) );
				$this->header[$field][$last] .= "\n$l";
			} else {
				list($field, $value) = explode(": ", $l, 2);
				$field = strtolower($field);
				$this->header[$field][] = $value;
			}
		}
		$subject = quoted_printable_decode( $this->header["subject"][0] );
		if(substr($subject, 0, 15) == "=?iso-8859-1?q?") {
			$subject = str_replace(array("_"), " ", substr($subject, 15));
		}
		$this->header['date'][0] =  strtotime( $this->header['date'][0] );
		$this->header['subject'][0] = preg_replace( "/[\t\n]*/", "", $subject );

		preg_match( "#^.Bug *([0-9]+)#", $this->header['subject'][0], $m );
		if( isset( $m[1] ) ) {
			$this->header['id'][0] = $m[1];
		} else if ( stristr( $this->header['from'][0], 'SourceForge.net' ) ) {
			preg_match( "#^\[ .*-([0-9]+) \]#", $this->header['subject'][0], $m );
			$this->header['id'][0] = $m[1];
		} else if ( stristr( $this->header['from'][0], 'bugzilla-daemon' ) &&
			( stristr( $this->header['subject'][0], 'Your account' ) ||
				stristr( $this->header['subject'][0], 'Bugzilla Change Password Request' ) ||
				stristr( $this->header['subject'][0], 'Password change request canceled' ) ) ) {
			$this->header['id'][0] = null;
		} else {
			var_dump($this->raw);
			exit;
		}

		if( strpos( $this->raw, "\n-- \n" ) !== false ) {
			list($keep, $trash) = explode( "\n-- \n", quoted_printable_decode( $this->raw ) );
		} else {
			$keep = $this->raw;
		}

		$part = explode("\n\n", $keep, 4);

		if( isset( $part[0] ) ) {
			$header = $part[0];
		} else {
			var_dump($part);
			echo "$count\n";
			exit;
		}

		if( isset( $part[1] ) ) {
			$url = $part[1];
		} else {
			var_dump($part);
			echo "$count\n";
			exit;
		}

		if( isset( $part[2] ) ) {
			$fields = $part[2];
		}

		if( isset( $part[3] ) ) {
			$body = $part[3];
		} else {
			$body = $part[2];
			$fields = null;
		}

		if( $fields ) {
			foreach(explode("\n", $fields) as $f) {
				preg_match( "#[ \t]+([^ \t:]+): ([^ ]+)#", $f, $m );
				if( isset( $m[2] ) ) {
					$this->header["x-bz-".strtolower($m[1])][0] = $m[2];
				}
			}
		}
		$this->description = $body;
	}
}

class BugzillaDupe {
	public function __call($name, $args) {
	}
}