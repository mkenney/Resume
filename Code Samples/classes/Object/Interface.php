<?php
/**
 * Object class interface file for Bdlm_Object
 *
 * @copyright 2005 - 2008 Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 */

/**
 * Object class interface
 * @copyright 2005 - 2008 Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 * @version 0.17
 */
interface Bdlm_Object_Interface extends Iterator, ArrayAccess, Countable, Serializable {

#######################################################################################
#	API Implementations
#######################################################################################

	/**
	 * Create a list of values or add a value to an existing list.
	 *
	 * If the existing data data for the given key '$var' is already an array created using the set() method,
	 * the new data will be added to that list rather then the existing list being converted to a single entry
	 * in a new list.  This can lead to unexpected behavior if you're not paying attention.
	 * @param string $var The name of the value
	 * @param mixed $val The value to store
	 * @return bool
	 */
	public function add($var, $val);

	/**
	 * Delete a locally stored value
	 *
	 * @param string $var The variable name
	 * @return void
	 */
	public function delete($var);

	/**
	 * Get a data object
	 * @param string $var The name of the data object to return
	 * @return Bdlm_Object The data named by $var
	 */
	public function get($var);

	/**
	 * Get all the data.
	 * Shouldn't be necessary with all the iterator/ArrayAccess/etc. stuff but some functions will only accept an array, so... :(
	 * Should probably use toArray() instead anyway.
	 * @return array
	 */
	public function getData();

	/**
	 * Check to see if a value has been set.
	 * @param string $var The variable name
	 * @return bool True if set, else false
	 */
	public function has($var);

	/**
	 * Check to see if a value is considered "empty" (http://php.net/empty)
	 *
	 * @param string $var The variable name
	 * @return bool True if empty, else false
	 */
	public function isEmpty($var);

	/**
	 * Set/get read-only flag for this object
	 * If true (static) this object becomes read-only
	 * If setting the value it returns $this to chain calls
	 * @param bool $static
	 * @return bool|Bdlm_Object
	 */
	public function isStatic($static = null);

	/**
	 * Load data relevant to this object
	 * Must be implemented in child classes
	 * @return Bdlm_Object
	 * @throws Bdlm_Exception
	 */
	public function load();

	/**
	 * max get/set wrapper
	 * @param string $max
	 * @return mixed
	 * @throws Bdlm_Object_Exception
	 */
	public function max($max = null);

	/**
	 * min get/set wrapper
	 * @param string $min
	 * @return mixed
	 * @throws Bdlm_Object_Exception
	 */
	public function min($min = null);

	/**
	 * mode get/set wrapper
	 * @param string $mode
	 * @return mixed
	 * @throws Bdlm_Object_Exception
	 */
	public function mode($mode = null);

	/**
	 * name get/set wrapper
	 * @param string $name
	 * @return mixed
	 * @throws Bdlm_Object_Exception
	 */
	public function name($name = null);

	/**
	 * Delete all locally stored values
	 *
	 * @return Bdlm_Object $this
	 */
	public function reset();

	/**
	 * Store a value.
	 * @param string $var The name of the value
	 * @param mixed $val The value to store
	 * @return bool
	 */
	public function set($var, $val);

	/**
	 * Set/replace the entire $_datastor
	 * @param array $data
	 * @return bool
	 * @throws Bdlm_Exception
	 */
	public function setData($data);

	/**
	 * Return the $_data array
	 * @return array
	 */
	public function toArray();

	/**
	 * Convert the $_data array to a JSON string
	 * @return string
	 */
	public function toJson();

	/**
	 * Convert the data array to a tab-delimited text
	 * @return string
	 */
	public function toString();

	/**
	 * Convert the $_data array to XML
	 * @return string
	 */
	public function toXml();

	/**
	 * Get/set the $_type value
	 * @param string $type
	 * @return string|Bdlm_Object
	 * @throws Bdlm_Exception
	 */
	public function type($type = null);

#######################################################################################
#	Validation methods
#######################################################################################

	/**
	 * Find out if $max is valid
	 * @param string $max The max value to check
	 * @return bool
	 * @throws Bdlm_Object_Exception If $max is not a string
	 */
	public function isValidMax($max);

	/**
	 * Find out if $min is valid
	 * @param string $min The min value to check
	 * @return bool
	 * @throws Bdlm_Object_Exception If $min is not a string
	 */
	public function isValidMin($min);

	/**
	 * Find out if $name is valid
	 * @param string $name The name to check
	 * @return bool
	 * @throws Bdlm_Object_Exception If $type is not a string
	 */
	public function isValidName($name);

	/**
	 * Find out if $type is valid
	 * @param string $type The type name to check
	 * @return bool
	 * @throws Bdlm_Object_Exception If $type is not a string
	 */
	public function isValidType($type);

	/**
	 * Validate data aginst this object's _type, _max and _min values.
	 * @param mixed $data
	 * @return Bdlm_Object $this
	 * @throws Bdlm_Exception If validation fails for any reason
	 */
	public function validateData($data);

#######################################################################################
#	Magic Implementations
#######################################################################################

	/**
	 * Implement the magic...
	 * @return mixed
	 */
	public function __get($var);

	/**
	 * Implement the magic...
	 * @return void
	 */
	public function __set($var, $val);

	/**
	 * Implement the magic...
	 * @return string
	 */
	public function __toString();

	/**
	 * Implement the magic...
	 * @return bool
	 */
	public function __isset($var);

	/**
	 * Implement the magic...
	 * @return void
	 */
	public function __unset($var);
}