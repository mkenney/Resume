<?php
/**
 * Database object interface file for Bedlam CORE
 *
 * @copyright 2005 - Present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version $Id$
 */

/**
 * Database object interface for Bedlam CORE
 *
 * @copyright 2005 - Present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version 0.01
 */
interface Bdlm_Db_Object_Interface extends Bdlm_Db_Row_Interface {
	/**
	 * Create a new record in the related table $table_name
	 * @param string $table_name The name of the child table to create a new row in
	 * @return Bdlm_Db_Row The new child record
	 */
	public function newChildRecord($table_name);
}