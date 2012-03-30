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
#	Validation methods
#######################################################################################

	/**
	 * Find out if $type is valid
	 * @param string $type The type name to check
	 * @return bool
	 * @throws Bdlm_Object_Exception If $type is not a string
	 */
	public function isValidType($type);

	/**
	 * Find out if $name is valid
	 * @param string $name The name to check
	 * @return bool
	 * @throws Bdlm_Object_Exception If $type is not a string
	 */
	public function isValidName($name);

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
	 * Validate data aginst this field's _type, _max_length and _min_length settings.
	 * @param string $type The type name to check
	 * @return Bdlm_Object
	 * @throws Bdlm_Object_Exception If $type is not a string
	 */
	public function isValidData($data);

#######################################################################################
#	API Implementations
#######################################################################################

	/**
	 * Get a data object
	 * @param string $var The name of the data object to return
	 * @return Bdlm_Object The data named by $var
	 */
	public function get($var);

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
	 * Delete a locally stored value
	 *
	 * @param string $var The variable name
	 * @return void
	 */
	public function delete($var);

	/**
	 * Delete all locally stored values
	 *
	 * @return Bdlm_Object $this
	 */
	public function reset();

	/**
	 * name get/set wrapper
	 * @param string $name
	 * @return mixed
	 * @throws Bdlm_Object_Exception
	 */
	public function name($name = null);

	/**
	 * min get/set wrapper
	 * @param string $min
	 * @return mixed
	 * @throws Bdlm_Object_Exception
	 */
	public function min($min = null);

	/**
	 * max get/set wrapper
	 * @param string $max
	 * @return mixed
	 * @throws Bdlm_Object_Exception
	 */
	public function max($max = null);

	/**
	 * mode get/set wrapper
	 * @param string $mode
	 * @return mixed
	 * @throws Bdlm_Object_Exception
	 */
	public function mode($mode = null);

	/**
	 * Alias __tostring()
	 * @return string
	 */
	public function toString();

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
	 * Convert the $_data array to XML
	 * @return string
	 */
	public function toXml();

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