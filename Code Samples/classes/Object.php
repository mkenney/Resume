<?php
/**
 * Class file for the Bdlm_Db_Object definition
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version $Id$
 */

/**
 * Representation of a database object (colleciton of tables)
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Db
 * @version 0.57
 */
class Bdlm_Db_Object extends Bdlm_Db_Row implements Bdlm_Db_Object_Interface {

	/**
	 * @var boolean
	 */
	protected $_loaded = false;
	/**
	 * Contains a list of Bdlm_Db_Table objects using the table name as the key
	 * @var Bdlm_Object
	 */
	protected $_child_tables = null;
	/**
	 * Contains a list of Bdlm_Object objects
	 * Each object will contain a list of Bdlm_Db_Row objects with the data for that table
	 * @var Bdlm_Object
	 */
	protected $_child_data = null;
	/**
	 * @var Bdlm_Object
	 */
	protected $_related_data = null;

	/**
	 * Store and optionally load related Bdlm_Db_Table objects in addition to the Bdlm_Db_Row constructor functions
	 * @param Bdlm_Db_Table $table
	 * @param int $key The id of the row to load
	 * @param bool $load
	 * @return void
	 */
	public function __construct(Bdlm_Db_Table $table, $key = null, $load = true) {
		parent::__construct($table, $key, false);

		//
		// Loop through the tables in the database and initialize Table instances
		//
		$_table_names = $this->db()->listTables("{$this->name()}_");
		while (list($k, $table_name) = each($_table_names)) {
			$this->childTables()->set(
				$table_name
				, new Bdlm_Db_Table($table_name, $this->db())
			);
		}
		if (true === $load) {
			$this->load();
		}
	}

	/**
	 * Get/set the local table data container and define it's data type
	 * This contains Bdlm_Object objects containing all rows for each table
	 * @param Bdlm_Object
	 * @return Bdlm_Object
	 */
	public function childData(Bdlm_Object $object = null) {
		if (!is_null($object)) {
			$this->_child_data = $object;
			$this->_child_data->type('Bdlm_Object');
		}
		if (!$this->_child_data instanceof Bdlm_Object) {
			$this->_child_data = new Bdlm_Object();
			$this->_child_data->type('Bdlm_Object');
		}
		return $this->_child_data;
	}

	/**
	 * Get/set the local tables container and define it's data type
	 * @param Bdlm_Object
	 * @return Bdlm_Object
	 */
	public function childTables(Bdlm_Object $object = null) {
		if (!is_null($object)) {
			$this->_child_tables = $object;
			$this->_child_tables->type('Bdlm_Db_Table');
		}
		if (!$this->_child_tables instanceof Bdlm_Object) {
			$this->_child_tables = new Bdlm_Object();
			$this->_child_tables->type('Bdlm_Db_Table');
		}
		return $this->_child_tables;
	}

	/**
	 * Get the Bdlm_Object instance containing the Bdlm_Db_Row data for a specified object table
	 * @param string $table_name The name of the table you want data for
	 * @return Bdlm_Object
	 * @throws Bdlm_Exception
	 */
	public function getchildData($table_name) {
		$table_name = trim($table_name);
		if (!$this->childTables()->has($table_name)) {
			throw new Bdlm_Exception("The specified table '{$table_name}' is not a recognized member of this data model ({$this->name()})");
		}
		if (!$this->childData()->has($table_name)) {
			$this->loadRelated($table_name);
		}
		return $this->childData()->get($table_name);
	}

	/**
	 * Load ALL data for all tables included in this object
	 * @return Bdlm_Db_Object
	 */
	public function load() {
		if (!$this->_loaded) {
			parent::load();
			$this->_loadChildData();
			$this->_loaded = true;
		}
		return $this;
	}

	/**
	 * Load related data into Bdlm_Db_Row objects
	 * @param string $table_name If specified only load the rows for that table, else load all tables
	 * @return void
	 * @throws Bdlm_Exception
	 */
	protected function _loadChildData($table_name = null) {
		if (!is_null($table_name) && !array_key_exists($table_name, $this->childTables()->toArray())) {
			throw new Bdlm_Exception("The specified table '{$table_name}' is not a recognized member of this data model ({$this->name()})");
		}

		$_child_tables = $this->childTables();
		while (list($k, $table) = each($_child_tables)) {
			if (
				is_null($table_name)
				|| $table->name() === $table_name
			) {

				//
				// Init objects for each table to store it's data rows
				//
				if (
					!$this->childData()->get($table->name()) instanceof Bdlm_Object
					|| 'Bdlm_Db_Row' !== $this->childData()->get($table->name())->type()
				) {
					$this->childData()->set($table->name(), new Bdlm_Object());
					$this->childData()->get($table->name())->type('Bdlm_Db_Row');
				}

				//
				// Load related data from this table as Bdlm_Db_Row objects
				//
				logevent('debug', __FILE__.':'.__LINE__." Loading child data for '{$table->name()}'");
				if (count($this->childData()->get($table->name())) < 1) {
					$rows = $GLOBALS['db']->query(new Bdlm_Db_Statement("
						SELECT id
						FROM :table_name
						WHERE :id_field = :id_field_value
						ORDER BY :id_field
					", array(
						'table_name' => $table->name(),
						'id_field' => "id_{$this->name()}",
						'id_field_value' => $this->id()
					)));
					while (list($k, $row) = each($rows)) {
						$this->childData()->get($table->name())->set(
							$row['id']
							, new Bdlm_Db_Object($table, $row['id'], true)
						);
					}
				}
			}
		}
	}

	protected function _loadRelatedData() {
		$_field_names = $this->table()->getData();
		while (list($field_name, $v) = each($_field_names)) {
			if (
				substr($field_name, 0, 3) == 'id_'
				&& "id_{$this->name()}" !== $field_name
			) {
				$object_name = substr($field_name, 3);
				$this->relatedData()->set($object_name, new Bdlm_Db_Object());
			}
		}
	}

	/**
	 * Create a new record in the related table $table_name
	 * @param string $table_name The name of the related toable to create a new record for
	 * @return Bdlm_Db_Row
	 */
	public function newChildRecord($table_name) {
		$record = new Bdlm_Db_Row($this->childTables()->get($table_name), 0, false);
		$record->set("id_{$this->name()}", $this->id());

		//
		// A valid primary key ('id') and parent Id ("id_{$this->name()}") are the minimum
		// requirements for a child record.  Other data must be added by your code/child class
		//
		$record->save();
		$this->childData()->get($table_name)->set($record->id(), $record);
		return $record;
	}

	/**
	 * Get/set the related objects container and define it's data type
	 * @param Bdlm_Object
	 * @return Bdlm_Object
	 */
	public function relatedData(Bdlm_Object $object = null) {
		if (!is_null($object)) {
			$this->_related_data = $object;
			$this->_related_data->type('Bdlm_Db_Object');
		}
		if (!$this->_related_data instanceof Bdlm_Object) {
			$this->_related_data = new Bdlm_Object();
			$this->_related_data->type('Bdlm_Db_Object');
		}
		return $this->_related_data;
	}

	/**
	 * Save this row and all child rows to the database.
	 * If it's an existing row (already loaded) then saving will overwrite the data.
	 * If it's new, saving will create a new row and update the key field with the
	 * new unique identifier and all child row data will be created as new and associated
	 * with the parent record ($this)
	 *
	 * Note that the data will NOT be saved if it has not been changed; that is,
	 * if the dirty flag is still 'false'.
	 *
	 * If the $as_new flag is true, the main row and all child rows will be copied in full
	 * to a new object Id.
	 *
	 * @param bool $as_new If true, force an insert of new records rather than updating.
	 * @param bool $update_modification_date Default is true
	 * @return string Mysqli::info string.
	 */
	public function save($as_new = false, $update_modification_date = true) {
		$ret_val = array();
		$ret_val[] = parent::save($as_new, $update_lastmod);

		$_child_data = $this->childData();
		while (list($k, $child_row) = each($_child_data)) {
			$child_row->set("id_{$this->name()}", $this->id());
			$ret_val[] = $child_row->save($as_new, $update_lastmod);
		}

		return implode("\n", $ret_val);
	}
}
