<?php
/**
 * Class file for the Bdlm_Db_Statement class
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 */

/**
 * Representation of a database query, handles escaping, etc.
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 * @version 0.8
 */
class Bdlm_Db_Statement extends Bdlm_Object {

	/**
	 * Store all args
	 * @param string $query A SQL query with variables
	 * @param array $data An associative array with variable definitions
	 * @param Bdlm_Db A database connection to use for queries
	 * @return void
	 */
	public function __construct($query = null, $data = array(), Bdlm_Db $db = null) {
		$this->query($query);
		$this->data($data);
		$this->db($db);
	}

	/**
	 * Set/get the autoQuote flag
	 * @param bool $bool
	 * @return bool
	 */
	public function autoQuote($bool = false) {
		if (true === (bool) $bool) {
			$this->set('auto_quote', true);
		} else {
			$this->set('auto_quote', false);
		}
		return $this->get('auto_quote');
	}

	/**
	 * Get/set the data array
	 * @param array $data
	 * @return array
	 */
	public function data($data) {
		if (is_array($data)) {
			$this->set("data", $data);
		}
		return $this->get("data");
	}

	/**
	 * Get/set the database instance to use for MySQLi escaping
	 * @param Bdlm_Db $db
	 * @return Bdlm_Db
	 */
	public function db(Bdlm_Db $db = null) {
		if (!is_null($db)) {
			$this->set('db', $db);
		}
		if (!$this->get('db') instanceof Bdlm_Db) {
			$this->set('db', $GLOBALS['db']);
		}
		if (!$this->get('db') instanceof Bdlm_Db) {
			$this->set('db', new Bdlm_Db());
		}
		return $this->get('db');
	}

	/**
	 * Set/get the SQL query to use and optionally the data
	 * @param string|Bdlm_Db_Statement $query The SQL query to use
	 * @param array $data The data to merge into the query
	 * @return string The current query.
	 */
	public function query($query = null, $data = array(), Bdlm_Db $db = null) {
		if (!is_null($query)) {
			$this->set("query", (string) $query);
		}
		if (count($this->get("data")) < 1 || count($data) > 0) {
			$this->set("data", (array) $data);
		}
		$this->db($db);
		return (string) $this->get("query");
	}

	/**
	 * Get the full query with data
	 * @return string SQL query
	 */
	public function __toString() {
		if ('' === trim($this->get("query"))) {
			throw new Bdlm_Exception("No SQL query given");
		}

		// Don't modify the original query
		$query = $this->get("query");

		foreach ($this->get("data") as $k => $v) {
			$quote_char = "";

			//
			// If it's an array then assume it's going to be used as a list, as in an IN('1', '2') clause for example.
			// Always quote the values
			//
			if (is_array($v)) {
				foreach ($v as $_tmp_k => $_tmp_v) {
					$v[$_tmp_k] = $this->db()->connection()->escape_string($_tmp_v);
				}
				$v = "'".implode("', '", $v)."'";

			} else {
				if ($this->get('auto_quote')) {
					if (
						!is_numeric($v)

						// If it has '(' and ends in ')' assume it's a function and do not quote...
						// @todo this could be more robust, it works well enough for now.
						&& !(
							strstr($v, '(') !== false
							&& substr(trim($v), -1) === ')'
						)
					) {
						$quote_char = "'";
					}
				}
				$v = $this->db()->connection()->escape_string($v);
			}

			//
			// This might be faster with native string functions
			// @todo can it work with string functions?  I doubt it :(  Check it out (and benchmark), this will be called a LOT
			//
			$query = preg_replace("/:".addslashes($k)."(\\b)/", $quote_char.$v.$quote_char.'\\1', $query);
		}
		return $query;
	}

}