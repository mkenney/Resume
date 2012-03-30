<?php
/**
 * Class file for the Bdlm_Object class
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 * @version $Id$
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
 * @version 1.74
 * @todo finish testing validation methods and write unit tests
 */
class Bdlm_Object implements Bdlm_Object_Interface {

	/**
	 * Array type error code
	 */
	const INVALID_TYPE_ARRAY     = 1;
	/**
	 * Boolean type error code
	 */
	const INVALID_TYPE_BOOLEAN   = 2;
	/**
	 * Date type error code
	 */
	const INVALID_TYPE_DATE      = 3;
	/**
	 * Double / Float type error code
	 */
	const INVALID_TYPE_DOUBLE    = 4;
	/**
	 * File type error code
	 */
	const INVALID_TYPE_FILE      = 5;
	/**
	 * Int / Integer / Long / Real type error code
	 */
	const INVALID_TYPE_INTEGER   = 7;
	/**
	 * Multi-byte string type error code
	 */
	const INVALID_TYPE_MBSTRING  = 9;
	/**
	 * Object type error code
	 */
	const INVALID_TYPE_OBJECT    = 10;
	/**
	 * Resource type error code
	 */
	const INVALID_TYPE_RESOURCE  = 12;
	/**
	 * Scalar type error code
	 */
	const INVALID_TYPE_SCALAR    = 13;
	/**
	 * String type error code
	 */
	const INVALID_TYPE_STRING    = 14;
	/**
	 * Class type error code
	 */
	const INVALID_TYPE_CLASS     = 15;
	/**
	 * Unknown type error code
	 */
	const INVALID_TYPE_UNKNOWN   = 16;
	/**
	 * Bounded data (max() / min()) error code
	 */
	const INVALID_DATA_SIZE      = 17;

	/**
	 * Local data storage
	 * @var array $_data
	 */
	protected $_data = array();

	/**
	 * The read vs read/write mode.
	 * If true, set/add/save methods should fail
	 */
	protected $_is_static = false;

	/**
	 * Optional, max length/value for this data
	 * @var int $_max
	 */
	protected $_max = null;

	/**
	 * Optional, min length/value for this data
	 * @var int $_min
	 */
	protected $_min = null;

	/**
	 * The object mode.  May be one of:
	 *  - 'list'
	 *	- 'fixed'
	 *  - 'singleton'
	 * Singleton mode should act as an array with one and only one element.
	 * List mode should act as an array with fixed keys.
	 * @var string $_mode
	 */
	protected $_mode = 'list';

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
	 * Initialize and populate data, if any.
	 * If data is an array, it is stored as-is, otherwise it's typed as an array first.
	 * @param mixed $data The initial data to store in the new object
	 * @return Bdlm_Object
	 */
	public function __construct($data = null) {
		if (!is_null($data)) {
			$this->setData((array) $data);
		}
		if (defined('DEBUG') && true === DEBUG) {
			Bdlm_Object::stats($this);
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
	 * @return Bdlm_Object
	 */
	public function add($var, $val) {

		// throws Bdlm_Exception if data is not of type $this->type()
		$this->validateData($val);

		if ($this->isStatic()) {
			throw new Bdlm_Exception("Static objects cannot be modified.");
		}

		$var = (string) $var;

		if (
			'singleton' === $this->mode()
			&& !$this->has($var)
		) {
			$this->reset();
		} elseif (
			'fixed' === $this->mode()
			&& !$this->has($var)
		) {
			throw new Bdlm_Exception("This is a fixed list and the specified key ('{$var}') does not exist.");
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
	 * @return Bdlm_Object
	 */
	public function delete($var) {

		if ($this->isStatic()) {
			throw new Bdlm_Exception("Static objects cannot be modified.");
		}

		$var = (string) $var;
		if (!$this->has($var)) {
			throw new Bdlm_Exception("The specified value '{$var}' does not exist.");
		}

		unset ($this->_data[$var]);
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
		return array_key_exists((string) $var, $this->_data);
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
	 * @param bool $static
	 * @return bool
	 */
	public function isStatic($is_static = null) {
		if (!is_null($is_static)) {
			$this->_is_static = (bool) $is_static;
		}
		return $this->_is_static;
	}

	/**
	 * Bdlm_Object_Interface implementation of the load method
	 * load() should always return the current instance or false on failure.
	 * Exceptions are also allowed
	 * @return false|Bdlm_Object
	 * @throws Bdlm_Exception
	 */
	public function load() {
		return $this;
	}

	/**
	 * max get/set wrapper
	 * If setting the value it returns $this to chain calls
	 * @param int $max
	 * @return int
	 * @throws Bdlm_Exception If $max is smaller than $min
	 */
	public function max($max = null) {
		if (!is_null($max)) {
			if (!$this->isValidMax($max)) {
				throw new Bdlm_Exception("Invalid \$max value ($max).  Must be numeric and greater than \$this->min().");
			}
			$this->_max = (float) $max;
		}
		return $this->_max;
	}

	/**
	 * min get/set wrapper
	 * If setting the value it returns $this to chain calls
	 * @param int $min
	 * @return int
	 * @throws Bdlm_Exception If $min is greater than $max
	 */
	public function min($min = null) {
		if (!is_null($min)) {
			if (!$this->isValidMin($min)) {
				throw new Bdlm_Exception("Invalid \$min value ($min).  Must be numeric and smaller than \$this->max().");
			}
			$this->_min = (float) $min;
		}
		return $this->_min;
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
		if (!is_null($name)) {
			if (!$this->isValidName($name)) {
				throw new Bdlm_Exception("'$name' is not a valid name");
			}
			$this->_name = (string) $name;
		}
		return $this->_name;
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

		// throws Bdlm_Exception if data is not of type $this->type()
		$this->validateData($val);

		if ($this->isStatic()) {
			throw new Bdlm_Exception("Static objects cannot be modified.");
		}

		//
		// Check the current mode, act accordingly.
		//
		if ('singleton' === $this->mode()) {
			$this->reset();

		} elseif (
			'fixed' === $this->mode()
			&& !$this->has($var)
		) {
			throw new Bdlm_Exception("This is a fixed list and the specified key ('{$var}') does not exist.");
		}

		$var = (string) $var;

		$this->_data[$var] = $val;

		return $this;
	}

	/**
	 * Set/replace the entire $_data array
	 * @param array $data
	 * @return bool
	 * @throws Bdlm_Exception In 'static' mode or in 'singleton' mode if there is more than one item
	 */
	public function setData($data) {
		if ($this->isStatic()) {
			throw new Bdlm_Exception("Static objects cannot be modified.");
		}

		if (!is_array($data)) {
			$data = (array) $data;
		}

		if (
			'singleton' === $this->mode()
			&& count($data) > 1
		) {
			throw new Bdlm_Exception('Too much data for \'singleton\' mode ('.count($data).' elements given)');
		} elseif (
			'fixed' === $this->mode()
			&& count(array_diff(array_keys($this->getData(), array_keys($data)))) > 0
		) {
			foreach ($this->getData() as $key => $value) {
				if (!array_key_exists($key, $data)) {
					throw new Bdlm_Exception("This is a fixed list and an existing key ('{$var}') is not present in the new list.");
				}
			}
			foreach ($data as $key => $value) {
				if (!array_key_exists($key, $this->getData())) {
					throw new Bdlm_Exception("This is a fixed list and a specified key ('{$var}') does not exist.");
				}
			}

		}
		foreach ($data as $val) {
			// throws Bdlm_Exception if data is not of type $this->type()
			$this->validateData($val);
		}
		$this->_data = $data;

		return $this;
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
	 * Convert the data array to a tab-delimited text
	 * @return string
	 */
	public function toString() {
		$ret_val = 'File: '.__FILE__."\nClass: ".__CLASS__."\nMethod: ".__METHOD__."()\nLine: ".__LINE__."\nMessage: Not Yet Implemented.\n\nCurrent Data:\n";

		// Use foreach instead of while/list because only foreach() recognizes Iterator implementations
		foreach ($this as $k => $v) {
			var_dump($k);
			var_dump($v);
			echo "\n\n";
			$ret_val .= "\t$k:\t$v\n";
		}
		return $ret_val;
	}

	/**
	 * mmmm.... recursion.... tasty
	 * @return string XML
	 * @todo Add doctype and sanitize $k and $v.
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
		if (!is_null($type)) {
			if ('' === trim($type)) {
				throw new Bdlm_Exception("'type' must be a string and must not be empty");
			} elseif (!$this->isValidType($type)) {
				throw new Bdlm_Exception("Invalid type '$type'");
			}
			$this->_type = $type;
			foreach ($this->getData() as $val) {
				// throws Bdlm_Exception if data is not of type $this->type()
				$this->validateData($val);
			}
		}
		return $this->_type;
	}

#######################################################################################
#	Validation methods
#######################################################################################

	/**
	 * Find out if $max is valid
	 * @param int|float $max The max value to check
	 * @return bool
	 */
	public function isValidMax($max) {
		$ret_val = true;
		if (
			!isNum($max)
			|| (
				!is_null($this->min())
				&& (float) $max < $this->min()
			)
		) {
			$ret_val = false;
		}
		return $ret_val;
	}

	/**
	 * Find out if $min is valid
	 * @param int|float $min The min value to check
	 * @return bool
	 */
	public function isValidMin($min) {
		$ret_val = true;
		if (
			!isNum($min)
			|| (
				!is_null($this->max())
				&& (float) $min > $this->max()
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
			case 'list':      // Arbitrary list of data
			case 'fixed':     // List of data with defined locations (keys)
			case 'singleton': // Single value
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
		$ret_val = false;
		switch ($type) {
			case 'array':
			case 'bool':
			case 'boolean':
			case 'date':
			case 'double':
			case 'file':
			case 'float':
			case 'int':
			case 'integer':
			case 'long':
			case 'mbstring':
			case 'mixed':
			case 'object':
			case 'real':
			case 'resource':
			case 'scalar':
			case 'string':
				$ret_val = true;
			break;

			//
			// Assume it's a class name
			//
			default:
				try {
					$ret_val = class_exists($type, true);
				} catch (Bdlm_Exception $e) {
					if (
						0 !== $e->getCode()
						|| false === strpos(strtolower($e->getMessage()), 'invalid class name')
					) {
						throw new Bdlm_Exception("{$e->getCode()}: {$e->getMessage()}");
					}
				}
			break;
		}
		return $ret_val;
	}

	/**
	 * Validate data aginst this objects's _type, _max and _min values.
	 * @param mixed $data
	 * @return Bdlm_Object $this
	 * @throws Bdlm_Exception If validation fails for any reason
	 * @todo This has full unit-test coverage but it still needs a lot of testing
	 */
	public function validateData($data) {
		$type = $this->type();
		switch ($type) {
			case 'array':
				if (!is_array($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'array\'.', Bdlm_Object::INVALID_TYPE_ARRAY);}
				$size = count($data);
				if (
					(!is_null($this->max()) && $size > (int) $this->max())
					|| (!is_null($this->min()) && $size < (int) $this->min())
				) {
					throw new Bdlm_Exception("Data ($size array elements) out of range ({$this->min()} to {$this->max()} array elements)", Bdlm_Object::INVALID_DATA_SIZE);
				}
			break;

			case 'bool':
			case 'boolean':
				if (!is_bool($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'bool\'.', Bdlm_Object::INVALID_TYPE_BOOLEAN);}
			break;

			case 'date':
				if (isNum($data)) {
					$size = (float) $data;
					if (0 > $size) {
						throw new Bdlm_Exception("Invalid date value, $data seconds", Bdlm_Object::INVALID_TYPE_DATE);
					}
				} elseif (is_string($data)) {
					$size = strtotime($data);
					if (false === $size) {
						throw new Bdlm_Exception("Invalid date string $data", Bdlm_Object::INVALID_TYPE_DATE);
					}
				} else {
					throw new Bdlm_Exception("Invalid date string $data", Bdlm_Object::INVALID_TYPE_DATE);
				}
				if (
					(!is_null($this->max()) && $size > (int) $this->max())
					|| (!is_null($this->min()) && $size < (int) $this->min())
				) {
					throw new Bdlm_Exception("Data ($size) out of range ({$this->min()} to {$this->max()} epoch seconds)", Bdlm_Object::INVALID_DATA_SIZE);
				}
			break;

			case 'double':
			case 'float':
				if (!isNum($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'float\'.', Bdlm_Object::INVALID_TYPE_DOUBLE);}
				$size = (float) $data;
				if (
					(!is_null($this->max()) && $size > (float) $this->max())
					|| (!is_null($this->min()) && $size < (float) $this->min())
				) {
					throw new Bdlm_Exception("Data ({$size}) out of range ({$this->min()} to {$this->max()})", Bdlm_Object::INVALID_DATA_SIZE);
				}
			break;

			case 'file':
				if (!is_string($data)) {
					throw new Bdlm_Exception("Data must be a valid local filesystem path, ".gettype($data)." given.", Bdlm_Object::INVALID_TYPE_FILE);
				}
				if (!is_file($data)) {throw new Bdlm_Exception('Invalid file path \''.$data.'\', file not found', Bdlm_Object::INVALID_TYPE_FILE);}
				$size = filesize($data);
				if (
					(!is_null($this->max()) && $size > (int) $this->max())
					|| (!is_null($this->min()) && $size < (int) $this->min())
				) {
					$size = number_format($size, 0);
					throw new Bdlm_Exception("Data ({$data} is {$size} bytes) out of range ({$this->min()} to {$this->max()})", Bdlm_Object::INVALID_DATA_SIZE);
				}
			break;

			case 'int':
			case 'integer':
			case 'long':
			case 'real':
				if (!isNum($data, true)) {
					throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'int\'.', Bdlm_Object::INVALID_TYPE_INTEGER);
				}
				$size = (int) $data;
				if (
					(!is_null($this->max()) && $size > (int) $this->max())
					|| (!is_null($this->min()) && $size < (int) $this->min())
				) {
					throw new Bdlm_Exception("Data ({$size}) out of range ({$this->min()} to {$this->max()})", Bdlm_Object::INVALID_DATA_SIZE);
				}
			break;

			case 'mbstring':
				throw new Bdlm_Exception("Multi-byte string validation still needs to be added... :(", Bdlm_Object::INVALID_TYPE_MBSTRING);
			break;

			case 'mixed':
			case null:
				// 'mixed' type data is not validated, exists for flexibility.  Probably shouldn't be used, if $this->_type is null then data isn't validated anyway
			break;

			case 'object':
				if (!is_object($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'object\'.', Bdlm_Object::INVALID_TYPE_OBJECT);}
			break;

			case 'resource':
				if (!is_resource($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'resource\'.', Bdlm_Object::INVALID_TYPE_RESOURCE);}
			break;

			case 'scalar':
				if (!is_scalar($data)) {throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'scalar\'.', Bdlm_Object::INVALID_TYPE_SCALAR);}
				if (is_bool($data)) {
					$size = null;
				} elseif (is_integer($data)) {
					$size = $data;
					$max = (int) $this->max();
					$min = (int) $this->min();
				} elseif (is_float($data)) {
					$size = $data;
					$max = (float) $this->max();
					$min = (float) $this->min();
				} elseif (is_string($data)) {
					$size = strlen($data);
					$max = (int) $this->max();
					$min = (int) $this->min();
				}
				if (
					(!is_null($this->max()) && $size > $max)
					|| (!is_null($this->min()) && $size < $min)
				) {
					throw new Bdlm_Exception("Data ({$size}) out of range ({$this->min()} to {$this->max()}), data type: ".gettype($data), Bdlm_Object::INVALID_DATA_SIZE);
				}
			break;

			case 'string':
				if (!is_string($data)) {
					throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', expecting \'string\'.', Bdlm_Object::INVALID_TYPE_STRING);
				}
				$size = strlen($data);
				if (
					(!is_null($this->min()) && $size < $this->min())
					|| (!is_null($this->max()) && $size > $this->max())
				) {
					throw new Bdlm_Exception("Data ({$size} characters) out of range ({$this->min()} to {$this->max()})", Bdlm_Object::INVALID_DATA_SIZE);
				}
			break;

			default:

				//
				// Assume $this->type() is a class name.  This _should_ take inheritance into account
				// @todo Test that inheritance is correctly accounted for
				//
				if (is_object($data)) {
					if (!$data instanceof $type) {
						throw new Bdlm_Exception('Invalid object type \''.get_class($data).'\', '.$type.' required.', Bdlm_Object::INVALID_TYPE_CLASS);
					}

				//
				// Bad type got in here somehow, revisit isValidType();
				//
				} else {
					throw new Bdlm_Exception('Invalid data type \''.gettype($data).'\', must be of type \''.$type.'\'', Bdlm_Object::INVALID_TYPE_UNKNOWN);
				}
			break;
		}

		//
		// Child objects should re-implement the return value however appropriate
		//
		return $this;
	}

#######################################################################################
#	Magic Implementations
#	The data access API (set(), get(), etc.) must be used here to
#	preserve consistent behavior with child classes that re-implement
#	those methods.  These methods are final to enforce api consistency
#######################################################################################

	/**
	 * @return mixed
	 */
	final public function __get($var) {
		return $this->get($var);
	}

	/**
	 * @return bool
	 */
	final public function __isset($var) {
		return $this->has($var);
	}

	/**
	 * @return void
	 */
	final public function __set($var, $val) {
		$this->set($var, $val);
	}

	/**
	 * Alias the standard implementation
	 * @return string
	 * @todo should this return a delimited file or something?
	 */
	final public function __toString() {
		return $this->toString();
	}

	/**
	 * @return void
	 */
	final public function __unset($var) {
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
#	Do not use the data access API (set(), get(), etc.) here as those
#	methods may be overriden in child classes
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
		return (isset($this->_data[$offset]) ? $this->_data[$offset] : null);
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
		return serialize(array(
			'_is_static' => $this->isStatic(),
			'_max' => $this->max(),
			'_min' => $this->min(),
			'_mode' => $this->mode(),
			'_name' => $this->name(),
			'_type' => $this->type(),
			'_data' => $this->getData(),
		));
	}

	/**
	 * Serializable implementation of serialize()
	 * @param string $data A serialized instance of Bdlm_Object
	 * @return void
	 */
	public function unserialize($data) {
		$data = unserialize($data);
		$this->isStatic($data['_is_static']);
		$this->max($data['_max']);
		$this->min($data['_min']);
		$this->mode($data['_mode']);
		$this->name($data['_name']);
		$this->type($data['_type']);
		$this->setData($data['_data']);
	}

#######################################################################################
#	System Statistics
#######################################################################################

	public static function stats($object) {
		$reflection = new ReflectionClass($object);
		if (!is_array($GLOBALS['__bdlm_stats__']['class_stats'][$reflection->getName()])) {
			$GLOBALS['__bdlm_stats__']['class_stats'][$reflection->getName()] = array(
				'init' => 0,
			);
		}
		$GLOBALS['__bdlm_stats__']['class_stats'][$reflection->getName()]['init']++;
	}

}
