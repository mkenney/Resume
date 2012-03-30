<?php
/**
 * Class file for the Bdlm_Object class
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 */

/**
 * Arbitrary data object.
 * Provides functions for strict typing and data validation.  Can be easily extended to add custom
 * data types.  No data validation is performed unless Bdlm_Object::_type !== null.  In that case,
 * Bdlm_Object::_max and Bdlm_Object::_min are taken into account also, letting you define boundaries
 * for the data.
 *
 * The meaning of the _max/_min boundaries should be semantically dependent on the type of data.  For
 * example, a _max of 10 on a _type='int' means it's value must be <= 10, but for a _type='string' it
 * must be <= 10 characters.
 *
 * _type, _max and _min apply to each item individually, meaning that every item stored here must meet
 * those requirements.
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 * @version 1.7
 * @todo finish testing validation methods
 */
class Bdlm_Object implements Bdlm_Object_Interface {

	/**
	 * Optional, name of this data
	 * @var string
	 */
	protected $_name = null;

	/**
	 * Optional, data type, used by validation functions
	 * @var string $_type
	 */
	protected $_type = null;

	/**
	 * Optional, min length/value for this data
	 * @var int $_min
	 */
	protected $_min = null;

	/**
	 * Optional, max length/value for this data
	 * @var int $_max
	 */
	protected $_max = null;

	/**
	 * The object mode.  May be one of:
	 *  - 'list'
	 *  - 'singleton'
	 * Singleton mode should act as an array with one and only one element.
	 * @var string $_mode
	 */
	protected $_mode = 'list';

	/**
	 * The read vs read/write mode.
	 * If true, set/add/save methods should fail
	 */
	protected $_static = false;

	/**
	 * Local data storage
	 * @var array $_data
	 */
	protected $_data = array();

	/**
	 * Initialize and populate data, if any.
	 * If data is an array, it is stored as-is, otherwise it's typed as an array first.
	 * @param mixed $data The initial data to store in the new object
	 * @return void
	 */
	public function __construct($data = null) {
		if (!is_null($data)) {
			if (!is_array($data)) {
				$data = (array) $data;
			}
			$this->setData($data);
		}
	}

	/**
	 * Create a list of values or add a value to an existing list.
	 *
	 * If the existing data for the given key '$var' is already an array created using the set() method
	 * the new data will be added to that list rather then the existing list being converted to a single entry
	 * in a new list.  This can lead to unexpected behavior if you're not paying attention.
	 *
	 * @param string $var The name of the value
	 * @param mixed $val The value to store
	 * @return bool
	 */
	public function add($var, $val) {

		if ($this->isStatic()) {
			throw new Bdlm_Exception("Static objects cannot be modified.");
		}

		$var = (string) $var;

		if (
			'singleton' === $this->mode()
			&& !$this->has($var)
		) {
			$this->reset();
		}

		//
		// 'add' this value to the list identified by $key
		//
		if ($this->has($var)) {
			if (!is_array($this->_data[$var])) {
				$this->_data[$var] = array($this->_data[$var]);
			}
		} else {
			$this->_data[$var] = array();
		}
		$this->_data[$var][] = $val;

		return $this;
	}

	/**
	 * Delete a locally stored value
	 *
	 * @param string $var The variable name
	 * @return void
	 */
	public function delete($var) {

		if ($this->isStatic()) {
			throw new Bdlm_Exception("Static objects cannot be modified.");
		}

		$var = (string) $var;
		if ($this->has($var)) {
			unset ($this->_data[$var]);
		}

		return $this;
	}

	/**
	 * Get a locally stored named value.
	 * @param string $var The variable name
	 * @return mixed
	 */
	public function get($var) {
		$ret_val = null;
		$var = (string) $var;
		if ($this->has($var)) {
			$ret_val = $this->_data[$var];
		}
		return $ret_val;
	}

	/**
	 * Get all the data.
	 * Shouldn't be necessary with all the iterator/ArrayAccess/etc. stuff but some functions will only accept an array, so... :(
	 * Should probably use toArray() instead anyway.
	 * @return array
	 */
	public function getData() {
		return $this->_data;
	}

	/**
	 * Check to see if a value has been set.
	 * @param string $var The variable name
	 * @return bool True if set, else false
	 */
	public function has($var) {
		return isset($this->_data[(string) $var]);
	}

	/**
	 * Check to see if a value should be considered "empty"
	 * The empty() call is wrapped to trap false-positives for the string '0' (http://php.net/empty).
	 * If this is called with no arguments it checks to see if any data has been stored yet.
	 *
	 * @param string $var The variable name
	 * @return bool True if empty, else false
	 */
	public function isEmpty($var = null) {
		$ret_val = true;
		if (is_null($var)) {
			$ret_val = (0 === count($this));

		} else {
			$var = (string) $var;

			//
			// empty() returns true for the string '0' so override that, '0' is still data
			//
			if ('0' === $this->get($var)) {
				$ret_val = false;
			} else {
				$ret_val = empty($this->_data[$var]);
			}
		}
		return $ret_val;
	}

	/**
	 * Set/get read-only flag for this object
	 * If true (static) this object becomes read-only
	 * If setting the value it returns $this to chain calls
	 * @param bool $static
	 * @return bool|Bdlm_Object
	 */
	public function isStatic($static = null) {
		$ret_val = false;
		if (is_null($static)) {
			$ret_val = $this->_static;
		} else {
			$this->_static = (bool) $static;
			$ret_val = $this;
		}
		return $ret_val;
	}

	/**
	 * max get/set wrapper
	 * If setting the value it returns $this to chain calls
	 * @param int $max
	 * @return int|Bdlm_Object
	 * @throws Bdlm_Exception If $max is smaller than $min
	 */
	public function max($max = null) {
		$ret_val = null;
		if (is_null($max)) {
			$ret_val = $this->_max;
		} else {
			if (!$this->isValidMax($max)) {
				throw new Bdlm_Exception("Invalid \$max value ($max).  Must be numeric and greater than \$this->min().");
			}
			$this->_max = (int) $max;
			$ret_val = $this;
		}
		return $ret_val;
	}

	/**
	 * min get/set wrapper
	 * If setting the value it returns $this to chain calls
	 * @param int $min
	 * @return int|Bdlm_Object
	 * @throws Bdlm_Exception If $min is greater than $max
	 */
	public function min($min = null) {
		$ret_val = null;
		if (is_null($min)) {
			$ret_val = $this->_min;
		} else {
			if (!$this->isValidMin($min)) {
				throw new Bdlm_Exception("Invalid \$min value ($min).  Must be numeric and smaller than \$this->max().");
			}
			$this->_min = (int) $min;
			$ret_val = $this;
		}
		return $ret_val;
	}

	/**
	 * mode get/set wrapper
	 * If setting the value it returns $this to chain calls
	 * @param string $mode
	 * @return string|Bdlm_Object
	 * @throws Bdlm_Exception If $mode is not a valid value
	 */
	public function mode($mode = null) {
		$ret_val = null;
		if (is_null($mode)) {
			$ret_val = $this->_mode;
		} else {
			if (!$this->isValidMode($mode)) {
				throw new Bdlm_Exception("Invalid mode given '$mode'");
			}
			$this->_mode = $mode;
			$ret_val = $this;
		}
		return $ret_val;
	}

	/**
	 * name get/set wrapper
	 * If setting the value it returns $this to chain calls
	 * @param string $name
	 * @return string|Bdlm_Object
	 * @throws Bdlm_Exception
	 */
	public function name($name = null) {
		$ret_val = null;
		if (is_null($name)) {
			$ret_val = $this->_name;
		} else {
			if (!$this->isValidName($name)) {
				throw new Bdlm_Exception("$name is not a valid name");
			}
			$this->_name = $name;
			$ret_val = $this;
		}
		return $ret_val;
	}

	/**
	 * Delete all locally stored values
	 *
	 * @return Bdlm_Object $this
	 */
	public function reset() {
		if ($this->isStatic()) {
			throw new Bdlm_Exception("Static objects cannot be modified.");
		}
		$this->_data = array();
		return $this;
	}

	/**
	 * Store a named value locally.
	 * @param string $var The name of the value
	 * @param mixed $val The value to store
	 * @return Bdlm_Object $this
	 */
	public function set($var, $val) {

		if ($this->isStatic()) {
			throw new Bdlm_Exception("Static objects cannot be modified.");
		}

		//
		// Check the current mode, act accordingly.
		//
		if ('singleton' === $this->mode()) {
			$this->reset();
		}

		$var = (string) $var;

		$this->_data[$var] = $val;

		return $this;
	}

	/**
	 * Set/replace the entire $_data array
	 * @param array $data
	 * @return bool
	 * @throws Bdlm_Exception In 'singleton' mode if there is more than one item
	 */
	public function setData($data) {
		if (!is_array($data)) {
			$data = (array) $data;
		}

		if (
			'singleton' === $this->mode()
			&& count($data) > 1
		) {
			throw new Bdlm_Exception('Too much data for \'singleton\' mode ('.count($data).' elements given)');
		}
		$this->_data = $data;

		return $this;
	}

	/**
	 * Alias the magic
	 * @return string
	 */
	public function toString() {
		return $this->__toString();
	}

	/**
	 * Recursively convert any Bdlm_Object instances the $_data array to an array and return
	 * @return array
	 */
	public function toArray($array = null) {
		$ret_val = array();
		if ($array instanceof Bdlm_Object) {
			$array = $array->toArray();
		} elseif (!is_array($array)) {
			$array = (array) $this->_data;
		}
		foreach ($array as $k => $v) {
			if (
				is_array($v)
				|| $v instanceof Bdlm_Object
			) {
				$ret_val[$k] = $this->toArray($v);
			} else {
				$ret_val[$k] = $v;
			}
		}
		return $ret_val;
	}

	/**
	 * Convert the $_data array to a JSON string
	 * @return string JSON
	 */
	public function toJson() {
		return json_encode($this->toArray());
	}

	/**
	 * mmmm.... recursion.... tasty
	 * @return string XML
	 */
	public function toXml($array = null) {
		$xml = '';
		if ($array instanceof Bdlm_Object) {
			$array = $array->toArray();
		} elseif (is_object($array)) {
			$array = array(get_class($array));
		} elseif (!is_array($array)) {
			$array = $this->toArray();
		}
		foreach ($array as $k => $v) {
			$xml .= "<$k>";
			if (
				is_array($v)
				|| $v instanceof Bdlm_Object
			) {
				$xml .= $this->toXml($v);
			} else {
				$xml .= $v;
			}
			$xml .= "</$k>";
		}
		return $xml;
	}

	/**
	 * Get/set the $_type value
	 * @param string $type
	 * @return string|Bdlm_Object
	 * @throws Bdlm_Exception
	 */
	public function type($type = null) {
		if (is_null($type)) {
			$ret_val = $this->_type;
		} else {
			if (!$this->isValidType($type)) {
				throw new Bdlm_Exception("Invalid type '$type'");
			}
			$this->_type = $type;
			$ret_val = $this;
		}
		return $ret_val;
	}

#######################################################################################
#	Validation methods
#######################################################################################

	/**
	 * Validate data aginst this field's _type, _max_length and _min_length settings.
	 * @param mixed $data
	 * @return bool True if the data meets the field requirements.
	 * @throws Bdlm_Exception If validation fails
	 */
	public function isValidData($data) {
		switch ($this->_type) {
			case 'string':
				if (!is_string($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'string\'.');}
				if (strlen($data) < $this->_min || strlen($data) > $this->_max) {
					throw new Bdlm_Exception("Data (".strlen($data)." characters) out of range ($this->_min to $this->_max characters)");
				}
			break;

			case 'mbstring':
				throw new Bdlm_Exception("Multi-byte string functionality still needs to be added... :(");
			break;

			case 'int':
			case 'integer':
			case 'long':
			case 'real':
				if (!isNum($data, true)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'int\'.');}
				if ((int) $data < $this->_min || (int) $data > $this->_max) {
					throw new Bdlm_Exception("Data ($data) out of range ($this->_min to $this->_max)");
				}
			break;

			case 'array':
				if (!is_array($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'array\'.');}
				if (count($data) < $this->_min || count($data) > $this->_max) {
					throw new Bdlm_Exception("Data (".count($data)." array elements) out of range ($this->_min to $this->_max array elements)");
				}
			break;

			case 'bool':
				if (!is_bool($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'bool\'.');}
			break;

			case 'object':
				if (!is_object($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'object\'.');}
			break;

			/**
			 * @todo Perfect opportunity to try out the Zend_Date classes.... make sure bcmath is installed.
			 */
			case 'date':
			break;

			case 'resource':
				if (!is_resource($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'resource\'.');}
			break;

			case 'float':
			case 'double':
				if (!isNum($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'float\'.');}
				if (floatval($data) < floatval($this->_min) || floatval($data) > floatval($this->_max)) {
					throw new Bdlm_Exception("Data (".floatval($data).") out of range ($this->_min to $this->_max)");
				}
			break;

			case 'file':
				if (!is_file($data)) {throw new Bdlm_Exception('Invalid file path \''.$data.'\', file not found');}
			break;

			case 'scalar':
				if (!is_scalar($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'scalar\'.');}
			break;

			case 'mixed':
			case null:
				// 'mixed' type data is not validated, exists for flexibility.  Probably shouldn't be used, if $this->_type is null then data isn't validated anyway
			break;



			default:

				//
				// Make sure we should be bothering
				//
				if (!is_null($this->_type)) {

					//
					// Assume $this->_type is a class name.  This does _not_ take inheritance into account
					// @todo Account for inheritance
					//
					if (is_object($data)) {
						$class_name = get_class($data);
						if ($class_name != $this->_type) {
							throw new Bdlm_Exception('Invalid object type ('.$class_name.'), '.$this->_type.' required.');
						}

					//
					// Bad type got in here somehow, revisit isValidType();
					//
					} else {
						throw new Bdlm_Exception('Unknown or invalid data type \''.$this->_type.'\'. Use \'mixed\' if this is intentional.');
					}
				}
			break;
		}

		//
		// Child objects should re-implement the return value however appropriate
		//
		return $this;
	}

	/**
	 * Find out if $max is valid
	 * @param string $max The max value to check
	 * @return bool
	 */
	public function isValidMax($max) {
		$ret_val = true;
		if (
			!isNum($max, true)
			|| (
				!is_null($this->min())
				&& (int) $max < $this->min()
			)
		) {
			$ret_val = false;
		}
		return $ret_val;
	}

	/**
	 * Find out if $min is valid
	 * @param string $min The min value to check
	 * @return bool
	 */
	public function isValidMin($min) {
		$ret_val = true;
		if (
			!isNum($min, true)
			|| (
				!is_null($this->max())
				&& (int) $min > $this->max()
			)
		) {
			$ret_val = false;
		}
		return $ret_val;
	}

	/**
	 * Find out if $mode is valid
	 * @param string $mode The mode value to check
	 * @return bool
	 */
	public function isValidMode($mode) {
		$ret_val = false;
		switch (trim($mode)) {
			case 'list':
			case 'singleton':
				$ret_val = true;
			break;

			default:
				$ret_val = false;
			break;
		}
		return $ret_val;
	}

	/**
	 * Find out if $name is valid
	 * @param string $name The name to check
	 * @return bool
	 */
	public function isValidName($name) {
		return ('' !== (string) $name);
	}

	/**
	 * Find out if $type is valid, in this case $type must be a valid class
	 * Not final so this class can more easily be reapplied while maintaining the API
	 * @param string $type The type name to check
	 * @return bool
	 * @throws Bdlm_Exception If $type can't be a string or is empty
	 */
	public function isValidType($type) {

		$type = (string) $type;
		if ('' === $type) {
			throw new Bdlm_Exception("'type' must be a string and must not be empty");
		}

		$ret_val = false;
		switch ($type) {
			case 'string':
			case 'mbstring':
			case 'int':
			case 'integer':
			case 'long':
			case 'real':
			case 'array':
			case 'bool':
			case 'object':
			case 'date':
			case 'resource':
			case 'float':
			case 'double':
			case 'file':
			case 'scalar':
			case 'mixed':
				$ret_val = true;
			break;

			//
			// Assume it's a class name
			//
			default:
				$ret_val = class_exists($type);
				if (!$ret_val) {
					$ret_val = bdlm_autoload($type);
				}
			break;
		}
		return $ret_val;
	}

#######################################################################################
#	Magic Implementations
#######################################################################################

	/**
	 * @return mixed
	 */
	public function __get($var) {
		return $this->get($var);
	}

	/**
	 * @return bool
	 */
	public function __isset($var) {
		return $this->has($var);
	}

	/**
	 * @return void
	 */
	public function __set($var, $val) {
		$this->set($var, $val);
	}

	/**
	 * @return string
	 * @todo should this return a delimited file or something?
	 */
	public function __toString() {
		$ret_val = 'File: '.__FILE__."\nClass: ".__CLASS__."\nMethod: ".__METHOD__."()\nLine: ".__LINE__."\nMessage: Not Yet Implemented.\n\nCurrent Data:\n";
		foreach ($this as $k => $v) {
			$ret_val .= "\t$k:\t$v\n";
		}
		return $ret_val;
	}

	/**
	 * @return void
	 */
	public function __unset($var) {
		$this->delete($var);
	}

#######################################################################################
#	Iterator Implementations
#######################################################################################

	/**
	 * Iterator implementation for current()
	 * @return bool|mixed See http://php.net/current
	 */
	public function current() {
		return current($this->_data);
	}

	/**
	 * Iterator implementation for each()
	 * @return bool|mixed See http://php.net/each
	 */
	public function each() {
		return each($this->_data);
	}

	/**
	 * Iterator implementation for end()
	 * @return mixed See http://php.net/end
	 */
	public function end() {
		return end($this->_data);
	}

	/**
	 * Iterator implementation for key()
	 * @return mixed See http://php.net/key
	 */
	public function key() {
		return key($this->_data);
	}

	/**
	 * Iterator implementation for next()
	 * @return bool|mixed See http://php.net/next
	 */
	public function next() {
		return next($this->_data);
	}

	/**
	 * Iterator implementation for prev()
	 * @return bool|mixed See http://php.net/prev
	 */
	public function prev() {
		return prev($this->_data);
	}

	/**
	 * Iterator implementation for rewind()
	 * @return bool|mixed See http://php.net/rewind
	 */
	public function rewind() {
		return reset($this->_data);
	}

	/**
	 * Iterator implementation for valid()
	 *
	 * Warning: If an array key is false (by calling Object::set(false, 'someval'))
	 * foreach loops will be endless because $this->key() returns false when past
	 * the end of the array.  This is better than the recommended method from
	 * http://php.net/manual/en/language.oop5.iterations.php which will end the
	 * loop if any _value_ === false.  Just don't use bools for array keys.
	 *
	 * Update, the set method has been changed to only allow strings as keys so this is
	 * a non-issue.
	 *
	 * Update, changed from isset(), $this->has(), and the like because of issues with
	 * false or null keys/values.  This should be pretty solid now.  See
	 * http://php.net/manual/en/class.iterator.php for more info.
	 *
	 * @return bool True if valid else false
	 */
	public function valid() {
		return array_key_exists($this->key(), $this->getData());
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
		return isset($this->_data[$offset]);
	}

	/**
	 * ArrayAccess implementation of offsetGet()
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->_data[$offset] ? $this->_data[$offset] : null;
	}

	/**
	 * ArrayAccess implementation of offset()
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->_data[$offset] = $value;
	}

	/**
	 * ArrayAccess implementation of offsetUnset()
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
	}

#######################################################################################
#	Countable Implementations
#######################################################################################

	/**
	 * Countable implementation of count()
	 */
	public function count() {
		return count($this->_data);
	}

#######################################################################################
#	Serializable Implementations
#######################################################################################

	/**
	 * Serializable implementation of serialize()
	 * @return string The serialized _data array
	 */
	public function serialize() {
		return serialize($this->getData());
	}

	/**
	 * Serializable implementation of serialize()
	 * @param string $data A serialized instance of Bdlm_Object
	 * @return void
	 */
	public function unserialize($data) {
		$this->setData(unserialize($data));
	}

}
