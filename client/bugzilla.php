#!/usr/bin/php -f
<?php

require_once "$IP/includes/Defines.php";
require_once "$IP/includes/AutoLoader.php";
require_once "$IP/includes/GlobalFunctions.php";
require_once "$IP/includes/DefaultSettings.php";
require_once 'jsonRPCClient.php';

class BugzillaBug {
	static private $bugs;
	private $bz = null;
	private $id = null;
	private $data = null;
	private $dependency = null;

	private function inCache( ) {
		return isset( self::$bugs[$this->id] );
	}

	private function fromCache( ) {
		$this->data = self::$bugs[ $this->id ]->data;
	}

	public function __construct( $id, $bz, $noFetch = false ) {
		$this->id = $id;
		$this->bz = $bz;
		if( !$this->inCache( ) && !$noFetch ) {
			$this->data = $this->bz->search( array( "id" => $id ) );
						$this->data = $this->data['bugs'][0];
			self::$bugs[$id] = $this;
		} else if( $this->inCache( ) ){
			$this->fromCache( );
		}
	}


	static public function newFromQuery( $bz, $data ) {
		$bug = new BugzillaBug( $data['id'], $bz, true );

		$bug->data = $data;
		return $bug;
	}

	/**
	 * Returns true if the bug is resolvd
	 */
	public function isResolved( ) {
		return !$this->data['is_open'];
	}

	public function isOpen( ) {
		return $this->data['is_open'];
	}

	public function getPriority( ) {
		$p = $this->data["priority"];
		if( $p == "Lowest"  ) return 2;
		if( $p == "Low"     ) return 1;
		if( $p == "Normal"  ) return 0;
		if( $p == "High"    ) return -1;
		if( $p == "Highest" ) return -2;
	}

	public function getProduct( ) {
		return $this->data["product"];
	}

	public function getComponent( ) {
		return $this->data["component"];
	}

	public function getAssignee( ) {
		return $this->data["assigned_to"];
	}

	public function getPriorityText( ) {
		return $this->data["priority"];
	}

	public function getStatus( ) {
		return $this->data['status'];
	}

	public function getID( ) {
		return $this->id;
	}

	public function getSummary( ) {
		return $this->data['summary'];
	}

	public function getHistory( ) {
		return $this->bz->getHistory( $this->id );
	}

	/* bz 4 reveals this info more easily */
	public function getDependencies( ) {
		return $this->data['depends_on'];

		/* Here's what was needed for Bz 3 */
		$dep = array();
		if(!$this->dependency) {
			$hist = $this->getHistory( );
			$changes = $hist['bugs'][0]['history'];
			foreach($changes as $b) {
				foreach($b['changes'] as $i => $desc) {
					if($desc['field_name'] == 'dependson') {
						if($desc['added']) {
							$dep[$desc['added']] = true;
						}
						if($desc['removed']) {
							unset($dep[$desc['removed']]);
						}
					}
				}
			}
			foreach($dep as $id => $none) {
				$this->dependency[] = new BugzillaBug( $id, $this->bz );
			}
		}
		return $this->dependency;
	}
}

class BugzillaSearchIterator implements Iterator {
	private $conditions;
	private $bz;
	private $data = array();
	private $limit = 20;
	private $offset = 0;
	private $eol = false;

	public function __construct( $bz, $conditions ) {
		$this->bz = $bz;
		$this->conditions = $conditions;

		$this->conditions['limit'] = $this->limit;
		$this->conditions['offset'] = $this->offset;
	}

	private function fetchNext( ) {
		if( $this->offset == count( $this->data ) && !$this->eol && $this->offset % $this->limit === 0 ) {
			$results = $this->bz->search( $this->conditions );

			$this->conditions['offset'] += $this->limit;

			if( count( $results['bugs'] ) < $this->limit ) {
				$this->eol = true;
			}

			foreach($results['bugs'] as $bug) {
				$this->data[] = BugzillaBug::newFromQuery($this->bz, $bug);
			}
		}
	}

	public function current( )  {
		return $this->data[$this->offset];
	}

	public function key ( )  {
		return $this->offset;
	}

	public function next ( ) {
		$this->fetchNext();
		if($this->offset < count($this->data)) $this->offset++;
	}

	public function rewind ( ) {
		$this->offset = 0;
	}

	public function valid ( ) {
		$this->fetchNext();
		return isset( $this->data[ $this->offset ] );
	}
}

class BugzillaWebClient {
	private $bz = null;

	public function __construct( $url, $user = null, $password = null, $debug = false ) {
		$this->bz = new jsonRPCClient( $url, $debug );
		if($user && $password) {
			$this->bz->__call( "User.login", array( "login" => $user, "password" => $password ) );
		}
	}

	public function getById( $id ) {
		return new BugzillaBug( $id, $this );
	}

	public function getFields( ) {
		/* Weird thing we have to do to keep bz from barfing */
		return $this->bz->__call( "Bug.fields", array( "" => "" ) );
	}

	public function search( $conditions ) {
		if(is_array($conditions)) {
			return $this->bz->__call( "Bug.search", $conditions );
		} else {
			throw new Exception("Search called without an array of conditions");
		}
	}

	public function getBugHistory( $id ) {
		return $this->getHistory( $id );
	}

	public function getHistory( $id ) {
		/* By casting to an array this will work if $id is a single bug or a list of bugs */
		return $this->bz->__call( "Bug.history", array( "ids" => (array)$id ) );
	}

	public function getDependencies( $id ) {
		$b = $this->getById( $id );
		return $b->getDependencies();
	}

	public function getResolved( $resolution ) {
		if(!is_array($resolution)) $resolution = array($resolution);
		return $this->search(array("resolution" => $resolution, "limit" => 10));
	}
}
