<?php
/**
 * Class file for the Bdlm_Db_Query class
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version $Id$
 */

/**
 * A representation of a SQL query.
 * Various convenient result traversal methods.  This implements Iterator, ArrayAccess
 * and Countable however the ArrayAccess write methods (offsetSet() and offsetUnset()) do
 * not work.  That could be... dangerous.
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version 1.21
 *
 * @todo add memcached calls
 */
class Bdlm_Db_Query extends Bdlm_Object {

	/**
	 * Database connection
	 * @var Bdlm_Db $_db
	 */
	protected $_db = null;

	/**
	 * The SQL query object
	 * @var Bdlm_Db_Statement $_query
	 */
	protected $_query = null;

	/**
	 * Mysqli result object
	 * @var MySQLi_Result $_result
	 */
	protected $_result = null;

	/**
	 * Current row pointer, required by iterator interface
	 * @var int $_current_row
	 */
	protected $_current_row = 0;

	/**
	 * Store the query information and execute it
	 * @param Bdlm_Db_Statement $query A valid SQL query object.
	 * @param Bdlm_Db $db The database connection object
	 * @return void
	 * @todo Delay query execution until the data is requested
	 */
	public function __construct(Bdlm_Db_Statement $query, Bdlm_Db $db) {
		parent::__construct(); // Construct superclass.

		$this->query($query);
		$this->db($db);
		$query->db($db); // For escaping strings

		//
		// Execute the query and store the result.  Run connect() to ensure a live connection before attempting to run a query.
		//
		$this->db()->connect();
		$this->result($this->db()->connection()->query($this->query()));
		if (false === $this->result()) {
			throw new Bdlm_Exception("The following query '\n{$this->query()}\n' failed.  Reason: #{$this->db()->errno()} {$this->db()->error()}");
		}
	}

	/**
	 * Fetch the entire result set as an associative array (hash).
	 * This creates a hash of the first two included fields only, typically a key and a value.
	 * Keys should be unique or you'll loose data, it just puts it in an array.
	 * @return array An array containing the results, or an empty array if it failed.
	 */
	public function toHash() {
		$ret_val = array();
		$this->rewind(); // Reset to the beginning
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			while ($row = $this->result()->fetch_array(MYSQLI_NUM)) {
				$ret_val[$row[0]] = $row[1];
				$this->_current_row++;
			}
		}
		$this->rewind(); // Reset to the beginning
		return $ret_val;
	}

	/**
	 * Fetch the entire result set and return it as a numerically indexed array (list).
	 * @return array An array containing the results
	 */
	public function toList() {
		$ret_val = array();
		$this->rewind(); // Reset to the beginning
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			while ($row = $this->result()->fetch_array(MYSQLI_NUM)) {
				$ret_val[] = $row[0];
				$this->_current_row++;
			}
		}
		$this->rewind(); // Reset to the beginning
		return $ret_val;
	}

	/**
	 * Fetch the next result row and return it as an array of fields
	 * Does not reset the row pointer
	 * @return array|bool An array containing the results in column order else false.
	 */
	public function toRow() {
		$ret_val = false;
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			$ret_val = $this->result()->fetch_array(MYSQLI_NUM);
			$this->_current_row++;
		}
		return $ret_val;
	}

	/**
	 * Fetch the next result row and return the first field as a single string (word).
	 * Does not reset the row pointer
	 * @return string A string containing the result
	 */
	public function toWord() {
		$ret_val = '';
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			$data = $this->result()->fetch_array(MYSQLI_NUM);
			$ret_val = (string) $data[0];
			$this->_current_row++;
		}
		return $ret_val;
	}

	/**
	 * Get/set the database connection instance
	 * @param Bdlm_Db $db
	 * @return Bdlm_Db
	 */
	public function db(Bdlm_Db $db = null) {
		if (!is_null($db)) {
			$this->_db = $db;
		}
		return $this->_db;
	}

	/**
	 * Get the field names, as an array, from the result-set.
	 * @return array An array containing the fields, in column order.
	 */
	public function fields() {
		$ret_val = array();
		$this->rewind();
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			$ret_val = $this->nextAssoc();
		}
		$this->seek($this->_current_row);
		return array_keys($ret_val);
	}

	/**
	 * Fetch the next result row and return it as an array of fields
	 * This is just an alias of toRow()
	 * @return array An array containing the results, in column order.
	 */
	public function nextRow() {
		return $this->toRow();
	}

	/**
	 * Same as nextRow(), but returns an associative array
	 * Column names should be unique or you will loose data.
	 * @return array An associative array using column names as keys.
	 */
	public function nextAssoc() {
		$ret_val = array();
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			$ret_val = $this->result()->fetch_array(MYSQLI_ASSOC);
			$this->_current_row++;
		}
		return $ret_val;
	}

	/**
	 * Get/set the SQL query to use.
	 * Typically the query will be set via the constructor
	 * @param Bdlm_Db_Statement $query A valid SQL query object
	 * @return Bdlm_Db_Statement The current query object
	 */
	public function query(Bdlm_Db_Statement $query = null) {
		if (!is_null($query)) {
			$this->set('query', $query); // use local storage instead of a property so this can be serialized and restored
		}
		return $this->get('query');
	}

	/**
	 * Render the results of a query as a table or tab-delimited text.
	 * Column names are used as headers.
	 * @param string $format Output format (html, txt).
	 * @return string
	 */
	public function render($format = 'html') {
		$this->reset();
		$ret_val = '';

		//
		// Select/describe/etc. should display table contents, everything else just returns a row count
		//
		if (
			(strtolower(substr($this->query()->__toString(), 0, 6)) == "select")
			|| (strtolower(substr($this->query()->__toString(), 0, 8)) == "describe")
			|| (strtolower(substr($this->query()->__toString(), 0, 4)) == "show")
			|| (strtolower(substr($this->query()->__toString(), 0, 7)) == "explain")
		) {

			$rowcount = 0;

			//
			// Render as HTML
			//
			if ('html' === $format) {
				while ($data = $this->nextAssoc()) {
					$ret_val .= "<table class=\"sqldata\" id=\"sqldata\">\n";
					if (0 === $rowcount) {
						$ret_val .= "<tr><td>".implode('</td><td>', array_keys($data))."</td>\n</tr>\n";
					}
					$ret_val .= "<tr><td>".implode('</td><td>', $data)."</td>\n</tr>\n";
					$rowcount++;
				}
				$ret_val .= "</table>\n";

			//
			// Render as text.
			//
			} elseif ($format == 'txt') {
				while ($data = $this->nextAssoc()) {
					if (0 === $rowcount) {
						$ret_val .= implode("\t", array_keys($data))."\n";
					}
					$ret_val .= implode("\t", $data)."\n";
					$rowcount++;
				}

			}

		//
		// Everything else just returns the number of affected rows.
		//
		} else {
			$ret_val = $this->size();
		}

		$this->seek($this->_current_row);

		return $ret_val;
	}

	/**
	 * Get/set the current MySQLi_Result object
	 * @param MySQLi_Result $result
	 * @return MySQLi_Result
	 */
	public function result($result = null) {
		// Mysqli::query() can return bool as well as a result object
		if (is_bool($result) || $result instanceof MySQLi_Result) {
			$this->_result = $result;
		}
		return $this->_result;
	}

	/**
	 * Reset the data pointer to the beginning of the result set.
	 * @return bool
	 */
	public function reset() {
		$ret_val = false;
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			$ret_val = $this->seek(0);
		}
		return $ret_val;
	}

	/**
	 * Moves the internal result pointer to a specified rownum.
	 * @param int $row The row the pointer needs to point to.
	 * @return bool
	 */
	public function seek($row) {
		$ret_val = false;
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			$ret_val = $this->result()->data_seek((int) $row);
			$this->_current_row = (int) $row;
		}
		return $ret_val;
	}

	/**
	 * Get the number of rows in the current result set.
	 * @return int The number of rows, or zero if the set is empty.
	 */
	public function size() {
		$ret_val = 0;
		if (!is_null($this->query()) && $this->result() instanceof MySQLi_Result) {
			$ret_val = (int) $this->result()->num_rows;
		}
		return $ret_val;
	}

#######################################################################################
#	Iterator Implementations
#######################################################################################

	/**
	 * Iterator implementation for current()
	 * @return bool|mixed See http://php.net/current
	 */
	public function current() {
		$data = $this->nextAssoc();
		$this->seek(--$this->_current_row);
		return $data;
	}

	/**
	 * Iterator implementation for each()
	 * @return bool|mixed See http://php.net/each
	 */
	public function each() {
		return $this->nextAssoc();
	}

	/**
	 * Iterator implementation for end()
	 * @return mixed See http://php.net/end
	 */
	public function end() {
		return $this->seek($this->size() - 1);
	}

	/**
	 * Iterator implementation for key()
	 * @return mixed See http://php.net/key
	 */
	public function key() {
		return $this->_current_row;
	}

	/**
	 * Iterator implementation for next()
	 * @return bool|mixed See http://php.net/next
	 */
	public function next() {
		return $this->nextAssoc();
	}

	/**
	 * Iterator implementation for prev()
	 * @return bool|mixed See http://php.net/prev
	 */
	public function prev() {
		$this->seek(--$this->_current_row);
		return $this->nextAssoc();
	}

	/**
	 * Iterator implementation for rewind()
	 * @return bool|mixed See http://php.net/rewind
	 */
	public function rewind() {
		$this->reset();
		$data = (0 === $this->size() ? false : $this->nextRow());
		$this->reset();
		return $data;
	}

	/**
	 * Iterator implementation for valid()
	 * @return bool True if valid else false
	 */
	public function valid() {
		$ret_val = false;
		$data = $this->nextAssoc();
		$this->seek(--$this->_current_row);
		if (is_array($data) && count($data) > 0) {
			$ret_val = true;
		}
		return $ret_val;
	}

#######################################################################################
#	ArrayAccess Implementations
#	Do not use the set() / get() API as those methods may be overriden in child classes
#######################################################################################

	/**
	 * ArrayAccess implementation of offsetExists()
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return ((int) $offset < $this->size());
	}

	/**
	 * ArrayAccess implementation of offsetGet()
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		$current_row = $this->_current_row;
		$this->seek((int) $offset);
		$data = $this->nextAssoc();
		$this->seek($current_row);
		return (count($data) > 0 ? $data  : null);
	}

	/**
	 * ArrayAccess implementation of offset()
	 * This doesn't apply to this object, it wouldn't be feasible to to allow writes
	 * this way if a query joins multiple tables.  I don't want to try to write
	 * that, I'll just work around it.
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		// Not implemented, do not allow database writes in this way
		return;
	}

	/**
	 * ArrayAccess implementation of offsetUnset()
	 * This doesn't apply to this object, it wouldn't be feasible to to allow writes
	 * this way if a query joins multiple tables.  I don't want to try to write
	 * that, I'll just work around it.
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		// Not implemented, do not allow database writes in this way
		return;
	}

#######################################################################################
#	Countable Implementations
#######################################################################################

	/**
	 * Countable implementation of count()
	 */
	public function count() {
		return $this->size();
	}

}