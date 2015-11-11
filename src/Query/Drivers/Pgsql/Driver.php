<?php
/**
 * Query
 *
 * Free Query Builder / Database Abstraction Layer
 *
 * @package		Query
 * @author		Timothy J. Warren
 * @copyright	Copyright (c) 2012 - 2015
 * @link 		https://github.com/aviat4ion/Query
 * @license		http://philsturgeon.co.uk/code/dbad-license
 */

// --------------------------------------------------------------------------

namespace Query\Drivers\Pgsql;

/**
 * PostgreSQL specifc class
 *
 * @package Query
 * @subpackage Drivers
 */
class Driver extends \Query\AbstractDriver {

	/**
	 * Connect to a PosgreSQL database
	 *
	 * @codeCoverageIgnore
	 * @param string $dsn
	 * @param string $username
	 * @param string $password
	 * @param array  $options
	 */
	public function __construct($dsn, $username=null, $password=null, array $options=array())
	{
		if (strpos($dsn, 'pgsql') === FALSE) $dsn = 'pgsql:'.$dsn;

		parent::__construct($dsn, $username, $password, $options);
	}

	// --------------------------------------------------------------------------

	/**
	 * Get a list of schemas for the current connection
	 *
	 * @return array
	 */
	public function get_schemas()
	{
		$sql = <<<SQL
			SELECT DISTINCT "schemaname" FROM "pg_tables"
			WHERE "schemaname" NOT LIKE 'pg\_%'
			AND "schemaname" != 'information_schema'
SQL;

		return $this->driver_query($sql);
	}

	// --------------------------------------------------------------------------

	/**
	 * Retrieve foreign keys for the table
	 *
	 * @param string $table
	 * @return array
	 */
	public function get_fks($table)
	{
		$value_map = array(
			'c' => 'CASCADE',
			'r' => 'RESTRICT',
		);

		$keys = parent::get_fks($table);

		foreach($keys as &$key)
		{
			foreach(array('update', 'delete') AS $type)
			{
				if ( ! isset($value_map[$key[$type]])) continue;

				$key[$type] = $value_map[$key[$type]];
			}
		}

		return $keys;
	}
}
//End of pgsql_driver.php