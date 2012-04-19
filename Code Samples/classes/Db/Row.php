<?php
/**
 * Class file for the Bdlm_Db_Row definition
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version $Id$
 */

/**
 * Representation of a single, complete row in a database.
 * Contains methods for saving, loading, etc.
 *
 * Compatible tables must have a single primary/unique key column named 'id', though
 * multi-field keys may be used to find and load rows.  See @todo for plans to handle
 * multiple rows matching a single "key" filter
 *
 * @todo Add iterator methods (nextRow, etc.) to manipulate the Bdlm_Db_Query
 * object and move forward/back through rows.  Bdlm_Db_Query already implements
 * Iterator, ArrayAccess and Countable so take advantage of those.
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version 1.27
 */
class Bdlm_Db_Row extends Bdlm_Object implements Bdlm_Db_Row_Interface {

	/**
	 * Is the key column auto-incrementing?
	 * @var bool $_autokey
	 */
	protected $_autokey = true;

	/**
	 * Dirty flag.
	 * @var bool $_dirty
	 */
	protected $_dirty = false;

	/**
	 * Store error messages for later
	 * @var array $_error_messages
	 */
	protected $_error_messages = array();

	/**
	 * Columns used for the primary key to select a SINGLE record
	 * This data is _always_ used to find and load a row.  For example, if loading a row by id
	 * this should contain the array array('id' => 1)
	 * @var array $_key
	 */
	protected $_key = null;

	/**
	 * The origial "clean" data loaded by load();
	 * This is used by the reset() method.
	 * @var array $_clean_data
	 */
	protected $_clean_data = array();

	/**
	 * The Bdlm_Db_Query object from load();
	 * @var Bdlm_Db_Query $_query;
	 */
	protected $_query = null;

	/**
	 * Insert vs replace query for new rows.
	 * @var boolean $_replace
	 */
	protected $_replace = false;

	/**
	 * Table instance
	 * @var Bdlm_Db_Table $_table
	 */
	protected $_table = null;

	/**
	 * Set data and initialize
	 *
	 * @param Bdlm_Db_Table $table Object representing this row's table
	 * @param int|string|array $id The value of the primary key; array for multiple.
	 * @param string|array $key The name of the primary key; array for multiple.
	 * @param bool $load If true, attempt to load the row from the database.
	 * @return Bdlm_Db_Row
	 * @todo Finish managing $key decisions.  Just use id()?
	 */
	public function __construct(Bdlm_Db_Table $table, $key = null, $load = true) {
		parent::__construct(); // Construct superclass.

		$this->table($table);
		$this->name($this->table()->name());

		if (!is_null($key) && !is_array($key)) {
 			//$key = array('id' => $key);
			$this->id($key);
		}

		//$this->rowKey($key);

		//
		// Define this row's default data fields
		//
		foreach ($this->table()->fields() as $field => $info) {
			if (strtolower($info['Extra']) === 'auto_increment') {
				$this->set($field, 0);
			} elseif (isset($info['Default'])) {
				$this->set($field, $info['Default']);
			} else {
				$this->set($field, null);
			}
		}

		//
		// If this is a multi-key table, the key column(s) aren't auto-incrementing.
		//
		if (count($this->rowKey()) > 1) {
			$this->_autokey = false;
		}

		//
		// If we have an ID, load the row.
		//
		// If a key name is given, use it-- otherwise check whether the ID is singular.
		// If so, use the first key column (key()), and if not, use them all.
		//
		if (!is_null($this->rowKey()) && true === $load) {
			$this->load();
		}
	}

	/**
	 * Copy this row
	 * This assumes that resetting the id column and then forcing a save will create a new row.
	 * @return bool
	 */
	public function copy() {
		$this->id(0); // Reset the ID.
		return $this->save(true); // Force creation of a new row.
	}

	/**
	 * Get/set the Db instance for this TABLE
	 * @param Bdlm_Db $db
	 * @return Bdlm_Db
	 */
	public function db(Bdlm_Db $db = null) {
		return $this->table()->db($db);
	}

	/**
	 * Delete this row.
	 * If the table contains a field named "status", it's assumed marking status
	 * as "X" will delete this row for the application. If $real_delete is true
	 * or no status field exists, a standard SQL delete is performed.
	 *
	 * @param boolean $real_delete
	 * @return void
	 */
	public function delete($real_delete = false) {

		// If there's a status field, mark this row as deleted.
		if ($this->has("status") && $real_delete === false) {
			$this->set('status', 'X');
			$this->save();

		// Otherwise do a real delete.
		} else {
			$this->table()->deleteRows($this->rowKey(), 1);
		}
	}

	/**
	 * Describe this row by returning a list of the columns as a comma-delimited string.
	 * Useful for inserting into queries.
	 *
	 * @return string A string containing the column names.
	 */
	public function describe() {
		return implode(", ", array_keys($this->getData()));
	}

	/**
	 * Create an SQL dump file which can be loaded into a new database.
	 * @return string A SQL insert statement
	 */
	public function dumpsql() {
		$fields = '';
		$values = '';
		foreach ($this->getData() as $key => $value) {
			$key = $this->table()->db()->connection()->real_escape_string($key);
			$value = $this->table()->db()->connection()->real_escape_string($value);
			$fields .= ($fields ? ", " : '')."`$key`";
			$values .= ($values ? ", " : '') . "'$value'";
		}
		return "INSERT INTO `{$this->table()->name()}` ($fields) VALUES ($values);";
	}

	/**
	 * Get/set error messages.
	 * @param string $message
	 * @return array All messages
	 */
	public function errorMessages($message = null) {
		if (!is_null($message)) {
			$this->_error_messages[] = (string) $message;
		}
		return $this->_error_messages;
	}

	/**
	 * Escape a query value.
	 *
	 * @param string $value the query value.
	 * @return string The "escaped" query value.
	 */
	public function escape($value) {
		return $this->db()->connection()->real_escape_string($value);
	}

	/**
	 * Get the field names for this table.
	 *
	 * @return array An array containing the field names.
	 */
	public function fields() {
		return array_keys($this->getData());
	}

	/**
	 * Get the value of a specific field IF it exists in this table
	 *
	 * @param string $field The field name.
	 * @param string $default The default value, if nothing is set.
	 * @return string The value of that field.
	 */
	public function get($field) {
		$ret_val = null;
		if ($this->table()->has($field)) {
			$ret_val = parent::get($field);
		}
		return $ret_val;
	}

	/**
	 * Get the unique identifier (primary key) for this record.
	 *
	 * @return int|string The unique ID, or a string of all keys if it's multi-keyed.
	 * @throws Bdlm_Exception
	 */
	public function id($id = null) {
		if (!is_null($id) && (int) $this->get('id') < 1) {
			if ($this->table()->has('id')) {
				$this->set('id', (int) $id);
				$this->rowKey(array('id' => $this->get('id')));
			} else {
				throw new Bdlm_Exception("This table ({$this->name()}) does not have a column named 'id'");
			}
		}
		return $this->get('id');
	}

	/**
	 * Load the specified row from the database.
	 *
	 * @param int|string|array $value The value of the key field (may be an array).
	 * @param string|array $key The name of the key field (must be a matching array if $value is an array).
	 * @return Bdlm_Db_Row
	 * @todo make sure this is working right... :o
	 */
	public function load() {
		$where = array();
		foreach ($this->rowKey() as $k => $v) {
			$k = $this->db()->connection()->real_escape_string($k);
			$where[] = "`$k` = ':$k'";
		}
		$sql = new Bdlm_Db_Statement("
			SELECT *
			FROM `".$this->name()."`
			WHERE
				".implode(' AND ', $where)."
		", $this->rowKey());
		$this->query($this->db()->query($sql));
		if (1 < (int) $this->query()->size()) {
			throw new Bdlm_Exception('Invalid result set for key '.print_r($this->rowKey(), true));
		} else {
			$data = $this->query()->nextAssoc();
			if (!$this->has('id')) {
				throw new Bdlm_Exception('All Bdlm_Db_Row datasets must have a primary key field named "id"');
			}
			$this->setData($data);
			$this->_clean_data = $data;
		}
		return $this;
	}

	/**
	 * Get/set the name of this row's table
	 * @param string $name
	 * @return string
	 */
	public function name($name = null) {
		return $this->table()->name($name);
	}

	/**
	 * Get/set this row's Bdlm_Db_Query instance
	 * @param Bdlm_Db_Query $query
	 * @return Bdlm_Db_Query
	 */
	public function query(Bdlm_Db_Query $query = null) {
		if (!is_null($query)) {
			$this->_query = $query;
		}
		return $this->_query;
	}

	/**
	 * Determine the replace functionality.  If enabled, new rows will use
	 * the 'replace' query which will replace an existing row whose primary
	 * key(s) match, and insert into the table otherwise.  If disabled, new
	 * rows will always be inserted.
	 *
	 * @param $replace If true, use a replace query rather than an insert.
	 * @return void
	 */
	public function replace($replace = false) {
		$this->_replace = (bool) $replace;
	}

	/**
	 * Clear data.
	 * This will set all fields to an empty string, except certain "standard" fields (id, status, etc.).
	 * @param string $field The name of a specific field to reset.  <b>If left out, all fields will be reset</b>.
	 * @return bool True on success, else false
	 */
	public function reset($field = null) {
		$ret_val = false;
		if (!is_null($field)) {
			if ($this->table()->isField($field)) {

				//
				// Set to original value loaded from the database
				//
				if (isset($this->_clean_data[$field])) {
					$this->set($field, $this->_clean_data[$field]);

				//
				// Set to database field default value if there hasn't been a load() call yet.
				//
				} else {
					$info = $this->table()->fields($field);
					if (strtolower($info['Extra']) === 'auto_increment') {
						$this->set($field, 0);
					} else {
						$this->set($field, $info['Default']);
					}
				}
				$ret_val = true;
			}
		} else {
			foreach ($this->getData() as $k => $v) {
				if (
					!in_array(
						$k
						, array(
							'id',
							'status',
							'created_on',
							'created_by',
							'modified_on',
							'modified_by',
						)
					)
				) {

					//
					// Set to original value loaded from the database
					//
					if (isset($this->_clean_data[$k])) {
						$this->set($k, $this->_clean_data[$k]);

					//
					// Set to database field default value if there hasn't been a load() call yet.
					//
					} else {
						$info = $this->table()->fields($k);
						if (strtolower($info['Extra']) === 'auto_increment') {
							$this->set($k, 0);
						} else {
							$this->set($k, $info['Default']);
						}
					}
				}
				$ret_val = true;
			}
		}
		return $ret_val;
	}

	/**
	 * Get/set the current row ident info
	 * @param array $key
	 * @return array
	 */
	public function rowKey($key = null) {
		if (!is_null($key)) {
			$this->_key = (array) $key;
		}
		return $this->_key;
	}

	/**
	 * Save this row to the database.
	 * If it's an existing row (already loaded) then saving will overwrite the data.
	 * If it's new, saving will create a new row and update the key field with the
	 * new unique identifier.
	 *
	 * Note that the data will NOT be saved if it has not been changed; that is,
	 * if the dirty flag is still 'false'.
	 *
	 * @param bool $as_new If true, force an insert rather than updating.
	 * @param bool $update_modification_date Default is true
	 * @return string Mysqli::info string
	 */
	public function save($as_new = false, $update_modification_date = true) {

		$as_new = (bool) $as_new;

		if (!$this->_dirty && !$as_new) {
			return false; // No change, so no save.  I know this breaks the 1 return per function rule :-P
		}

		if ($as_new) {
			$this->set('id', 0);
		}

		$now = date('Y-m-d H:i:s');

		foreach ($this as $k => $v) {
			if ('now()' === strtolower(trim($v))) {
				$this[$k] = $now;
			}
		}

		// Created on
		if (
			$this->has('created_on')
				&& (
					!$this->get("created_on")
					|| '0000-00-00 00:00:00' === $this->get("created_on")
					|| is_null($this->get('created_on'))
				)
		) {
			$this->set("created_on", $now);
		}

		// Modified on
		if ($this->has('modified_on') && true === $update_modification_date) {
			$this->set("modified_on", $now);
		}

		// Created by
		if (
			$this->has('created_by')
			&& (
				!$this->get("id")
				|| !$this->get('created_by')
			)
		) {
			if ($GLOBALS['user'] instanceof User && $GLOBALS['user']->id()) {
				$this->set('created_by', $GLOBALS['user']->id());
			} else {
				$this->set('created_by', 0);
			}
		}

		// Modified by
		if ($this->has('modified_by')) {
			if ($GLOBALS['user'] instanceof User && $GLOBALS['user']->id()) {
				$this->set('modified_by', $GLOBALS['user']->id());
			}
		}

		//
		// Update existing records
		//
		$update_existing_record = (!$as_new && ((int) $this->id() > 0));
		$table_name = substr(uniqid('Bdlm_Db___'), 0, 20); // ... probably won't ever have a field with this name :o
		if ($update_existing_record) {

			$query = "UPDATE `:$table_name` SET ";

			//
			// Build key-value list.
			//
			$comma = '';
			foreach ($this->getData() as $field => $data) {
				$query .= "$comma $field = ':$field'";
				$comma = ',';
			}
			$query .= " WHERE id = :id LIMIT 1";

			$sql = new Bdlm_Db_Statement($query);

		//
		// No existing row or $as_new so create a new record.
		//
		} else {
			$this->id(0);
			$action = ($this->replace() ? 'REPLACE' : 'INSERT');
			$sql = new Bdlm_Db_Statement("{$action} INTO `:$table_name` (
				".implode(', ', array_keys($this->getData()))."
			) VALUES (
				':".implode("', ':", array_keys($this->getData()))."'
			)");
		}
		$data = $this->getData();
		$data[$table_name] = $this->table()->name();

		$sql->data($data);        // Add the data to the prepaired statement
		$this->db()->query($sql); // Run the query to update the database.

		// Update the primary key for new objects.
		if (!$update_existing_record) {
			$this->id($this->db()->lastId());
		}

		return $this->db()->info();
	}

	/**
	 * Set the value of a field.
	 *
	 * If no such field name exists, then call the Object set() method to set
	 * the value of a key.
	 *
	 * @param string $field The field name.
	 * @param string $value The value to store in the field.
	 * @return bool True on assignment or false if no change was made.
	 */
	public function set($field, $value) {
		$ret_val = $this;

		//
		// Only set if the key actually exists in this table and the value is different
		//
		if ($this->table()->has($field)) {
			if (parent::get($field) !== $value) {
				$this->_dirty = true;
				parent::set($field, $value);
			}
		} else {
			throw new Bdlm_Exception("The field '$field' does not exist in the table '{$this->table()->name()}'");
		}
		return $ret_val;
	}

	/**
	 * Get/set the table instance for this row
	 *
	 * @param Bdlm_Db_Table $table
	 * @return Bdlm_Db_Table
	 */
	public function table(Bdlm_Db_Table $table = null) {
		if (!is_null($table)) {
			$this->_table = $table;
		}
		return $this->_table;
	}
}
