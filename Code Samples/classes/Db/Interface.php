<?php
/**
 * Database connection manager interface file for Bedlam CORE
 *
 * @copyright 2005 - 2008 Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 */

/**
 * Database connection manager interface for Bedlam CORE
 *
 * @copyright 2005 - 2008 Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version 0.2
 */
interface Bdlm_Db_Interface {

	/**
	 * Store the connection information, name the instance and mark as read-only
	 * $database is the first arg to make multi-db apps less of a pain, assuming the other info is the same
	 * @param string $database The database namne
	 * @param string $server The server hostname
	 * @param string $user The database user name
	 * @param string $password The database user's password
	 * @param string $adapter Not used, placeholder for future PDO
	 * @return Bdlm_Db
	 */
	public function __construct($database = null, $server = null, $user = null, $password = null, $adapter = null);

	/**
	 * Close the database connection.
	 * @return bool MySQLi::close()
	 */
	public function close();

	/**
	 * Open a conection to the database
	 * Checks whether there is already a connection and if so it doesn't
	 * try again unless $force_reconnect is true.
	 * @param bool $force_reconnect
	 * @return Bdlm_Db
	 */
	public function connect($force_reconnect = false);

	/**
	 * Get/set the current Db adapter instance
	 * Always returns an adapter instance, even if no connection has been established
	 * @todo This should not be MySQLi specific, write a wrapper class that can be used for other apapters
	 * @param Mysqli $mysqli
	 * @return Mysqli
	 */
	public function connection(Mysqli $mysqli = null);

	/**
	 * Semantic opposite of connect()
	 * This is an alias to close() above
	 * @return bool MySQLi::close()
	 */
	public function disconnect();

	/**
	 * Return a description of the last error.
	 * @return string
	 */
	public function error();

	/**
	 * Return the error code for the most recent function call
	 * @return int
	 */
	public function errno();

	/**
	 * Get the current conneciton information.
	 * @return array
	 */
	public function info();

	/**
	 * Check to see if '$table' is a valid table name in the current database.
	 * @param string $table The name of the table to check for
	 * @return bool
	 */
	public function isTable($table);

	/**
	 * Get the last insert ID generated on an auto-increment column in the database.
	 * This is maintained on a per-connection basis, so it will return the ID from
	 * this particular application thread.
	 * @return int The last insert Id
	 */
	public function lastId();

	/**
	 * List existing tables
	 * @param string $prefix A prefix to use for filtering table names
	 * @return array
	 */
	public function listTables($prefix);

	/**
	 * Run a SQL query against the database.
	 * @param string $query - a valid SQL query.
	 * @return Bdlm_Db_Query A Bdlm_Db_Query object which can be used to parse the results.
	 */
	public function query(Bdlm_Db_Statement $query);

}