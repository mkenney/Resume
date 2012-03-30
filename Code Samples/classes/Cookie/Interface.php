<?php
/**
 * Object class interface for Bedlam CORE<br />
 * path: core/libs/classes/interfaces/ObjectInterface.interface.php
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 */

/**
 * Object class interface
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 * @version 0.17
 */
interface Bdlm_Cookie_Interface extends Bdlm_Object_Interface {

	/**
	 * Path get/set wrapper
	 * @param string $var The name of the data object to return
	 */
	public function path($path = null);

	/**
	 * timeout get/set wrapper
	 */
	public function timeout($timeout = null);

	/**
	 * domain get/set wrapper
	 */
	public function domain($domain = null);

	/**
	 * secure get/set wrapper
	 */
	public function secure($secure = null);
}