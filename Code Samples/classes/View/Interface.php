<?php
/**
 * View interface file for Bedlam CORE
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Views
 */

/**
 * View class interface
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 * @version 0.1
 */
interface Bdlm_View_Interface {

	/**
	 * Render a page based on the requested module and output
	 * The module contains the View and Controller logic for specific functionality
	 * @return string The rendered page
	 */
	public function render();

	/**
	 * Get/set the title of a page
	 * @param string $title
	 * @return string
	 */
	public function title($title = null);
}