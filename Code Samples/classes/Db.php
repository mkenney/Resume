<?php
/**
 * Class definition and configuration for Bdlm_Db
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 */

if (file_exists(APP_CONF_DIR.'db.php')) {
	require_once(APP_CONF_DIR.'db.php');
}
require_once(CONF_DIR.'db.php');

/**
 * A representation of a database connection.
 * This is just a simple wrapper for the MySQLi database connection functions
 * @todo This is MySQL-centric, abstract for use with other DBs to be identified by DB_ADAPTER
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version 1.1
 */
class Bdlm_Db extends Bdlm_Object implements Bdlm_Db_Interface {

	/**
	 * Connection information and other data storage (see Bdlm_Object())
	 * @var array $_data
	 */
	protected $_data = array(
		'server' => '',
		'username' => '',
		'password' => '',
		'database' => '',
		'adapter' => '',
	);

	/**
	 * MySQLi instance
	 * @var MySQLi $_adapter
	 */
	protected $_adapter = null;

	/**
	 * Log activity?
	 * @var bool $_log
	 */
	protected $_log = true;

	/**
	 * Store the connection information, name the instance and mark as read-only
	 * $database is the first arg to make multi-db apps less of a pain, assuming the other info is the same
	 * @param string $database The database namne
	 * @param string $server The server hostname
	 * @param string $user The database user name
	 * @param string $password The database user's password
	 * @@param string $adapter Not used, placeholder for future PDO
	 * @return void
	 */
	public function __construct($database = null, $server = null, $user = null, $password = null, $adapter = null) {
		parent::__construct();

		if (is_null($server))   {$server = DB_SERVER;}
		if (is_null($user))     {$user = DB_USERNAME;}
		if (is_null($password)) {$password = DB_PASSWORD;}
		if (is_null($database)) {$database = DB_DATABASE;}
		if (is_null($adapter))  {$adapter = DB_ADAPTER;}


		$this->set('server', $server);
		$this->set('username', $user);
		$this->set('password', $password);
		$this->set('database', $database);
		$this->set('adapter', $adapter);
		$this->connect(true);

		$this->name($database);
		$this->type('Bdlm_Db');

		// Lock it down
		$this->isStatic(true);
	}

	/**
	 * Close the database connection.
	 * @return bool MySQLi::close()
	 */
	public function close() {
		return $this->connection()->close();
	}

	/**
	 * Open a conection to the database
	 * Checks whether there is already a connection and if so it doesn't
	 * try again unless $force_reconnect is true.
	 * @param bool $force_reconnect
	 * @return Bdlm_Db
	 */
	public function connect($force_reconnect = false) {

		if (is_null(@$this->connection()->sqlstate) || true === $force_reconnect) { // If sqlstate isn't set this produces a warning.  I don't care so suppress it.

			$this->connection()->connect(
				$this->get('server')
				, $this->get('username')
				, $this->get('password')
				, $this->get('database')
			);

			if ($this->connection()->connect_error) {
				if ($this->_log) {
					event('error', "Unable to open database '{$this['database']}' on host '{$this['server']}' as user '{$this['username']}'! (".$this->connection()->connect_errno.": ".$this->connection()->connect_error.")");
				}
			}
		}

		return $this;
	}

	/**
	 * Get/set the current Mysqli instance
	 * Always returns a Mysqli instance, even if no connection has been established
	 * @param Mysqli $mysqli
	 * @return Mysqli
	 */
	public function connection(Mysqli $mysqli = null) {
		if (!is_null($mysqli)) {
			$this->_adapter = $mysqli;
		}
		if (is_null($this->_adapter)) {
			$this->_adapter = new Mysqli();
		}
		return $this->_adapter;
	}

	/**
	 * Semantic opposite of connect()
	 * This is an alias to Dblm_Db::close()
	 * @return bool MySQLi::close()
	 */
	public function disconnect() {
		return $this->close();
	}

	/**
	 * Return a description of the last error.
	 * @return string
	 */
	public function error() {
		return $this->connection()->error;
	}

	/**
	 * Get the current conneciton information.
	 * @return array
	 */
	public function info() {
		return $this->connection()->info;
	}

	/**
	 * Check to see if '$table' is a valid table name in the current database.
	 * @param string $table The name of the table to check for
	 * @return bool
	 */
	public function isTable($table) {
		$result = $this->query(new Bdlm_Db_Statement("SHOW TABLES LIKE ':table'", array('table' => $table), $this));
		return (1 === $result->size());
	}

	/**
	 * Get the last insert ID generated on an auto-increment column in the database.
	 * This is maintained on a per-connection basis, so it will return the ID from
	 * this particular application thread.
	 * @return int The last insert Id
	 */
	public function lastId() {
		return (int) $this->connection()->insert_id;
	}

	/**
	 * List existing tables
	 * @param string $prefix A prefix to use for filtering table names
	 * @return array
	 */
	public function listTables($prefix) {
		$result = $this->query(new Bdlm_Db_Statement("SHOW TABLES LIKE ':prefix%'", array('prefix' => $prefix), $this));
		return $result->asList();
	}

	/**
	 * Run a SQL query against the database.
	 * @param string $query - a valid SQL query.
	 * @return Bdlm_Db_Query A Bdlm_Db_Query object which can be used to parse the results.
	 */
	public function query(Bdlm_Db_Statement $query) {
		// Always ensure a live connection before executing a query
		$this->connect();
		return new Bdlm_Db_Query($query, $this);
	}

}