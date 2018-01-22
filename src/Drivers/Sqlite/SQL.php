<?php declare(strict_types=1);
/**
 * Query
 *
 * SQL Query Builder / Database Abstraction Layer
 *
 * PHP version 7.1
 *
 * @package     Query
 * @author      Timothy J. Warren <tim@timshomepage.net>
 * @copyright   2012 - 2018 Timothy J. Warren
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link        https://git.timshomepage.net/aviat4ion/Query
 */
namespace Query\Drivers\Sqlite;

use Query\Drivers\AbstractSQL;

/**
 * SQLite Specific SQL
 */
class SQL extends AbstractSQL {

	/**
	 * Get the query plan for the sql query
	 *
	 * @param string $sql
	 * @return string
	 */
	public function explain($sql)
	{
		return "EXPLAIN QUERY PLAN {$sql}";
	}

	/**
	 * Random ordering keyword
	 *
	 * @return string
	 */
	public function random()
	{
		return ' RANDOM()';
	}

	/**
	 * Returns sql to list other databases
	 *
	 * @return string
	 */
	public function dbList()
	{
		return 'PRAGMA database_list';
	}

	/**
	 * Returns sql to list tables
	 *
	 * @return string
	 */
	public function tableList()
	{
		return <<<SQL
			SELECT DISTINCT "name"
			FROM "sqlite_master"
			WHERE "type"='table'
			AND "name" NOT LIKE 'sqlite_%'
			ORDER BY "name" DESC
SQL;
	}

	/**
	 * List the system tables
	 *
	 * @return string[]
	 */
	public function systemTableList()
	{
		return ['sqlite_master', 'sqlite_temp_master', 'sqlite_sequence'];
	}

	/**
	 * Returns sql to list views
	 *
	 * @return string
	 */
	public function viewList()
	{
		return <<<SQL
			SELECT "name" FROM "sqlite_master" WHERE "type" = 'view'
SQL;
	}

	/**
	 * Returns sql to list triggers
	 *
	 * @return string
	 */
	public function triggerList()
	{
		return 'SELECT "name" FROM "sqlite_master" WHERE "type"=\'trigger\'';
	}

	/**
	 * Return sql to list functions
	 *
	 * @return NULL
	 */
	public function functionList()
	{
		return NULL;
	}

	/**
	 * Return sql to list stored procedures
	 *
	 * @return NULL
	 */
	public function procedureList()
	{
		return NULL;
	}

	/**
	 * Return sql to list sequences
	 *
	 * @return NULL
	 */
	public function sequenceList()
	{
		return NULL;
	}

	/**
	 * SQL to show list of field types
	 *
	 * @return string[]
	 */
	public function typeList()
	{
		return ['INTEGER', 'REAL', 'TEXT', 'BLOB'];
	}

	/**
	 * SQL to show infromation about columns in a table
	 *
	 * @param string $table
	 * @return string
	 */
	public function columnList($table)
	{
		return 'PRAGMA table_info("' . $table . '")';
	}

	/**
	 * Get the list of foreign keys for the current
	 * table
	 *
	 * @param string $table
	 * @return string
	 */
	public function fkList($table)
	{
		return 'PRAGMA foreign_key_list("' . $table . '")';
	}


	/**
	 * Get the list of indexes for the current table
	 *
	 * @param string $table
	 * @return string
	 */
	public function indexList($table)
	{
		return 'PRAGMA index_list("' . $table . '")';
	}
}