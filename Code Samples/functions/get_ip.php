<?php
/**
 * Common system functions
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Functions
 */

/**
 * Get a users IP address bypassing proxies if possible
 *
 * @return string|false The users IP address or false on failure
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Functions
 * @version 0.31
 */
function get_ip() {

	//
	// Defalut false in case of failure
	//
	$ip_addr = false;

	//
	// Check the $_SERVER global first
	//
	if (count($_SERVER) > 1) {

		//
		// Check originating address if forwarded by a proxy
		//
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$ip_addr = $_SERVER["HTTP_X_FORWARDED_FOR"];

		//
		// Check for the client ip
		//
		} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
			$ip_addr = $_SERVER["HTTP_CLIENT_IP"];

		//
		// Default to the remote address
		//
		} elseif (isset($_SERVER["REMOTE_ADDR"])) {
			$ip_addr = $_SERVER["REMOTE_ADDR"];
		}

	//
	// Check environment variables if the $_SERVER global isn't available for some reason
	//
	} else {

		//
		// Check originating address if forwarded by a proxy
		//
		if (getenv('HTTP_X_FORWARDED_FOR')) {
			$ip_addr = getenv('HTTP_X_FORWARDED_FOR');

		//
		// Check for the client ip
		//
		} elseif (getenv('HTTP_CLIENT_IP')) {
			$ip_addr = getenv('HTTP_CLIENT_IP');

		//
		// Default to the remote address
		//
		} elseif (getenv('REMOTE_ADDR')) {
			$ip_addr = getenv('REMOTE_ADDR');
		}
	}

	//
	// IP address on success, false on failure
	//
	return $ip_addr;
}
