#!/usr/bin/php -f
<?php

require_once "$IP/includes/Defines.php";
require_once "$IP/includes/AutoLoader.php";
require_once "$IP/includes/GlobalFunctions.php";
require_once "$IP/includes/DefaultSettings.php";
require_once 'jsonRPCClient.php';

// Relevant documentation
// http://www.bugzilla.org/docs/tip/en/html/api/Bugzilla/WebService/Bug.html

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
			self::$bugs[$id] = $this;
		} elseif( $this->inCache( ) ){
			$this->fromCache( );
		}
	}

	static public function newFromQuery( $bz, $data ) {
		$bug = new BugzillaBug( $data['id'], $bz, true );

		$bug->data = $data;
		return $bug;
	}

	public function getPatches() {
		$ret = array();
		foreach( $this->getAttachments() as $attachment ) {
			if( $attachment['content_type'] == "text/diff"
				|| substr( strtolower( $attachment['file_name'] ), -4 ) == "diff"
				|| substr( strtolower( $attachment['file_name'] ), -7 ) == "diff.gz"
				|| substr( strtolower( $attachment['file_name'] ), -8 ) == "patch.gz"
				|| substr( strtolower( $attachment['file_name'] ), -5 ) == "patch" ) {
				$ret[] = $attachment;
			} else {
				/* echo $attachment['content_type'], "\n"; */
				/* echo $attachment['file_name'], "\n"; */
				/* echo substr( strtolower( $attachment['file_name'] ), -6 ). "\n"; */
			}
		}

		return $ret;
	}

	public function getAttachments() {
		return $this->bz->getBugAttachments( $this->id );
	}

	public function getComments() {
		return $this->bz->getBugComments( $this->id );
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
		$ret = $this->bz->getHistory( $this->id );

		return $ret['bugs'][0]['history'];
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

	public function undoLastChangeIfBy( $email, $fakeIt = "" ) {
		$hist = $this->getHistory();
		$change = array_pop( $hist['bugs'][0]['history'] );
		$reverse = array();

		if( $change['who'] == $email ) {
			echo "{$this->id}: Undoing last change by $email made at {$change['when']}:\n";
			foreach($change['changes'] as $c) {
				$reverse = array_merge( $reverse, $this->addResetField( $c ) );
			}
			if( $fakeIt === "" ) {
				return $this->bz->update( $this->id, $reverse );
			}
		} else {
			return false;
		}
	}

	public function findCommentByRegexp( $regexp ) {
		foreach( $this->getComments() as $comment ) {
			$hasMatch = preg_match( $regexp, $comment['text'] );
			if( $hasMatch >= 1 ) {
				return $comment;
			}
			if( $hasMatch === false ) {
				if (preg_last_error() == PREG_INTERNAL_ERROR) {
					print 'There is an internal error!';
					exit;
				}
				elseif (preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {
					print 'Backtrack limit was exhausted!';
					exit;
				}
				elseif (preg_last_error() == PREG_RECURSION_LIMIT_ERROR) {
					print 'Recursion limit was exhausted!';
					exit;
				}
				elseif (preg_last_error() == PREG_BAD_UTF8_ERROR) {
					print 'Bad UTF8 error!';
					exit;
				}
				elseif (preg_last_error() == PREG_BAD_UTF8_OFFSET_ERROR) {
					print 'Bad UTF8 offset error!';
					exit;
				}
			}
		}

		return null;
	}

	public function deleteComment( $id ) {
		return $this->bz->deleteComment( $this->id, $id );
	}

	public function addResetField( $changeLog ) {
		if( $this->bz->isListField( $changeLog['field_name'] ) ) {
			$add    = explode( ", ", $changeLog['removed'] );
			$remove = explode( ", ", $changeLog['added'] );

			if( $add[0] !== "" ) {
				$changes["add"] = $add;
			}
			if( $remove[0] !== "" ) {
				$changes['remove'] = $remove;
			}

			return array( $changeLog['field_name'] => $changes );
		} else {
			$v = $changeLog['removed'];
			$f = $changeLog['field_name'];
			if( $f === "bug_status" && ( $v === "NEW" || $v === "ASSIGNED" ) ) {
				$v = "REOPENED";
			}
			return array( $f => $v );
		}
	}

	public function resetField( $change ) {
		if( !isset( $change['field_name'] ) )
			throw new Exception( "no field_name given!" );
		if( !isset( $change['removed'] ) )
			throw new Exception( "no removed value given!" );
		if( !isset( $change['added'] ) )
			throw new Exception( "no added value given!" );

		if( $change['removed'] == "" ) {
			return $this->removeFromFieldList( $change['field_name'], $change['added'] );
		}

		if( $change['added'] == "" ) {
			return $this->addToFieldList( $change['field_name'], $change['removed'] );
		}

		return $this->setFieldValue( $change['field_name'], $change['removed'] );
	}

	public function addToFieldList( $field, $value ) {
		return $this->bz->addToFieldList( $this->id, $field, $value );
	}

	public function removeFromFieldList( $field, $value ) {
		return $this->bz->removeFromFieldList( $this->id, $field, $value );
	}

	public function setFieldValue( $field, $value ) {
		return $this->bz->update( $this->id, array( $field => $value ) );
	}
}

class BugzillaSearchIterator implements Iterator {
	protected $conditions;
	protected $bz;
	protected $data = array();
	protected $limit = 20;
	protected $offset = 0;
	protected $eol = false;

	public function __construct( $bz, $conditions ) {
		$this->bz = $bz;
		$this->conditions = $conditions;

		if( !isset( $this->conditions['limit'] ) )  $this->conditions['limit'] = $this->limit;
		if( !isset( $this->conditions['offset'] ) ) $this->conditions['offset'] = $this->offset;
	}

	/* Override this for other iterators  */
	public function getItem( $bug ) {
		return BugzillaBug::newFromQuery($this->bz, $bug);
	}

	protected function fetchNext( ) {
		if( $this->offset == count( $this->data ) && !$this->eol && $this->offset % $this->limit === 0 ) {
			$results = $this->bz->search( $this->conditions );

			$this->conditions['offset'] += $this->limit;
			if( count( $results['bugs'] ) < $this->limit ) {
				$this->eol = true;
			}

			foreach($results['bugs'] as $bug) {
				$val = $this->getItem( $bug );
				if( $val ) {
					$this->data[] = $val;
				}
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
		while( !$this->eol && !isset($this->data[$this->offset]) ) {
			$this->fetchNext();
		}
		return isset( $this->data[ $this->offset ] );
	}
}

class BugzillaWebClient {
	private $bz = null;
	private $lists = array( "blocks", "depends_on", "cc", "groups", "keywords", "see_also" );

	public function __construct( $url, $user = null, $password = null, $debug = false ) {
		$this->bz = new jsonRPCClient( $url, $debug );
		if($user && $password) {
			$this->bz->__call( "User.login", array( "login" => $user, "password" => $password ) );
		}
	}

	public function deleteComment( $bugId, $id ) {
		$url = sprintf( "https://bugzilla.wikimedia.org/deletecomment.cgi?bug_id=%d&id=%d", $bugId, $id );
		$h = MWHttpRequest::factory( $url );
		return $this->bz->executeWithCookies( $h );
	}

	public function isListField( $field ) {
		return in_array( $field, $this->lists );
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

	public function getBugAttachments( $id, $attachments = null ) {
		$args['ids'] = (array)$id;
		if ( $attachments !== null ) {
			$args['attachment_ids'] = (array)$attachments;
		}
		$ret = $this->bz->__call(
			"Bug.attachments", $args );

		if( !is_array( $id ) ) {
			$ret = $ret['bugs'][$id];
		}

		return $ret;
	}

	public function getBugComments( $id ) {
		$ret = $this->bz->__call(
			"Bug.comments", array( "ids" => (array)$id ) );
		if( !is_array( $id ) ) {
			$ret = $ret['bugs'][$id]['comments'];
		}
		return $ret;
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

	public function addToFieldList( $ids, $field, $value ) {
		if( !in_array( $field, $this->lists ) ) {
			throw new Exception( "This field ($field) isn't a list!" );
		} else {
			return $this->update( $ids, array( $field => array( "add" => (array)$value ) ) );
		}
	}

	public function removeFromFieldList( $ids, $field, $value ) {
		if( !in_array( $field, $this->lists ) ) {
			throw new Exception( "This field ($field) isn't a list!" );
		} else {
			return $this->update( $ids, array( $field => array( "remove" => (array)$value ) ) );
		}
	}

	public function update( $ids, $fields ) {
		$fields['ids'] = (array)$ids;
		return $this->bz->__call( "Bug.update", $fields );
	}
}
