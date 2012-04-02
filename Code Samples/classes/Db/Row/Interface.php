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
interface Bdlm_Db_Row_Interface extends Bdlm_Object_Interface {

	/**
	 * Save this row to the database.
	 * If it's an existing row (already loaded) then saving will overwrite the data.
	 * If it's new, saving will create a new row and update the key field with the
	 * new unique identifier.
	 *
	 * Note that the data will NOT be saved if it has not been changed; that is,
	 * if the dirty flag is still 'false'.
	 *
	 * @param bool $as_new If true, force an insert rather than updating.
	 * @param bool $update_modification_date Default is true
	 * @return string Mysqli::info string
	 */
	public function save($as_new = false, $update_modification_date = true);
}