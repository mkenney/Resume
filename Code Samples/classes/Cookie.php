<?php
/**
 * Cookie management object
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Cookie
 * @version $Id$
 */

if (file_exists(APP_CONF_DIR.'cookie.php')) {
	/**
	 * App-specific cookie settings
	 */
	require_once(APP_CONF_DIR.'cookie.php');
}
/**
 * System cookie settings
 */
require_once(CONF_DIR.'cookie.php');

/**
 * Cookie management object
 * This is nice because if you do all your cookie interaction through here then
 * your cookie data is available immediately without a page refresh.
 *
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Cookie
 * @version 1.0
 */
class Bdlm_Cookie extends Bdlm_Object implements Bdlm_Cookie_Interface {

	/**
	 * The cookie path
	 * @var string
	 */
	public $_path = '/';

	/**
	 * The cookie timeout in seconds, default to 1 day
	 * @var int
	 */
	public $_timeout = 86400;

	/**
	 * The cookie domain
	 * @var string
	 */
	public $_domain = COOKIE_DOMAIN;

	/**
	 * Require SSL encryption
	 * @var bool
	 */
	public $_secure = COOKIE_SECURE;

	/**
	 * HTTPOnly flag
	 * @var bool
	 */
	public $_httponly = COOKIE_HTTPONLY;

	/**
	 * Setup all the defaults and initialize
	 * @param string $name
	 * @param int $timeout
	 * @param string $path
	 * @param string $domain
	 * @return Bdlm_Cookie
	 */
	public function __construct($name = '', $timeout = 86400, $path = '/', $domain = null) {

		//
		// Initialize/store cookie variables
		// @TODO input validation on names and values
		//
		$this->name(trim($name));
		$this->timeout((int) $timeout);
		$this->path(trim($path));
		$this->domain(
			is_null($domain)
				? (defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '')
				: trim($domain)
		);
		$this->secure((bool) defined('COOKIE_SECURE') ? COOKIE_SECURE : false);
		$this->httponly((bool) defined('COOKIE_HTTPONLY') ? COOKIE_HTTPONLY : true);
		parent::__construct(
			'' === $this->name()
				? $_COOKIE
				: $_COOKIE[$this->name()] // Something isn't right about this.  Can you see it?  I can.  It smells funny.
		);
	}

	/**
	 * Delete a cookie value
	 * @param string $var The name of the value to be deleted
	 * @return Bdlm_Object
	 * @throws Bdlm_Exception
	 */
	public function delete($var) {

		//
		// Delete the specified value
		//
		if (
			!setcookie(
				$this->name()."[$var]"
				, false
				, time() - 86400
				, $this->path()
				, $this->domain()
				, $this->secure()
			)
		) {

			//
			// Print error
			//
			throw new Bdlm_Exception("Could not delete cookie ($this->name[$var])");
		}

		//
		// Update local value
		//
		return parent::delete($var);
	}

	/**
	 * Get/set the cookie domain
	 * @param string $domain
	 * @return string
	 */
	final public function domain($domain = null) {
		if (!is_null($domain)) {
			$this->_domain = trim($domain);
		}
		return $this->_domain;
	}

	/**
	 * HTTPOnly get/set wrapper
	 * @param bool $httponly
	 * @return bool
	 */
	final public function httponly($httponly = null) {
		if (is_null($httponly)) {
			$this->_httponly = (bool) $httponly;
		}
		return $this->_httponly;
	}

	/**
	 * Get/set the cookie path
	 * @param string $path
	 * @return string
	 */
	final public function path($path = null) {
		if (!is_null($path)) {
			$this->_path = trim($path);
		}
		return $this->_path;
	}

	/**
	 * Delete all cookies accessable from this cookie object
	 * @return Bdlm_Object $this
	 */
	final public function reset() {

		//
		// Loop through cookie values deleting each one
		// Don't just loop through $this or the each() call gets offset
		// as you delete keys and you can potentially skip some.
		//
		$array = $this->toArray();
		foreach ($this->toArray() as $var) {
			$this->delete($var);
		}
		return parent::reset();
	}

	/**
	 * Secure get/set wrapper
	 * @param bool $secure
	 * @return bool
	 */
	final public function secure($secure = null) {
		if (is_null($secure)) {
			$this->_secure = (bool) $secure;
		}
		return $this->_secure;
	}

	/**
	 * Wrap the Bdlm_Object setter to accomidate the timeout override argument
	 *
	 * @param string $var The key name
	 * @param string $val The value
	 * @param int $timeout The cookie timeout in seconds
	 * @return bool
	 */
	public function set($var, $val, $timeout = null) {

		// Init return value
		$ret_val = false;

		// Set the cookie timeout.  If the timeout value is 0, use a session cookie.
		if (!is_null($timeout)) {
			$timeout = ((int) $timeout > 0
				? time() + (int) $timeout
				: 0
			);
		} else {
			$timeout = ((int) $this->timeout() > 0
				? (int) $this->timeout()
				: 0
			);
		}

		//
		// Set the cookie value
		//
		//echo 'setcookie('.$this->name.'['.$var.']'.', '.$val.', '.$timeout.', '.$this->path().', '.$this->domain().', '.$this->secure().");\n";
		if (
			setcookie(
				$this->name()."[$var]"
				, $val
				, $timeout
				, $this->path()
				, $this->domain()
				, $this->secure()
				, $this->httponly()
			)
		) {

			// Update local values
			$ret_val = parent::set($var, $val);
		}

		return $ret_val;
	}

	/**
	 * Get/set the default cookie timeout
	 * @param int $timeout
	 * @return int
	 */
	final public function timeout($timeout = null) {
		if (!is_null($timeout)) {

			$timeout = (int) $timeout;

			// Format the timestamp, (a value of 0 creates a session cookie)
			if ($timeout > 0) {
				$timeout += time();
			}

			$this->_timeout = $timeout;
		}
		return (int) $this->_timeout;
	}

}