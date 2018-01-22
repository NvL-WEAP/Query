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
namespace Query\Drivers\Firebird;

use Query\Drivers\AbstractSQL;

/**
 * Firebird Specific SQL
 */
class SQL extends AbstractSQL {

	/**
	 * Limit clause
	 *
	 * @param string $sql
	 * @param int $limit
	 * @param int|bool $offset
	 * @return string
	 */
	public function limit($sql, $limit, $offset=FALSE)
	{
		// Keep the current sql string safe for a moment
		$origSql = $sql;

		$sql = 'FIRST '. (int) $limit;

		if ($offset > 0)
		{
			$sql .= ' SKIP '. (int) $offset;
		}

		$sql = preg_replace("`SELECT`i", "SELECT {$sql}", $origSql);

		return $sql;
	}

	/**
	 * Get the query plan for the sql query
	 *
	 * @param string $sql
	 * @return string
	 */
	public function explain($sql)
	{
		return $sql;
	}

	/**
	 * Random ordering keyword
	 *
	 * @return string
	 */
	public function random()
	{
		return NULL;
	}

	/**
	 * Returns sql to list other databases
	 *
	 * @return NULL
	 */
	public function dbList()
	{
		return NULL;
	}

	/**
	 * Returns sql to list tables
	 *
	 * @return string
	 */
	public function tableList()
	{
		return <<<SQL
			SELECT TRIM("RDB\$RELATION_NAME")
			FROM "RDB\$RELATIONS"
			WHERE "RDB\$SYSTEM_FLAG"=0
			AND "RDB\$VIEW_BLR" IS NULL
			ORDER BY "RDB\$RELATION_NAME" ASC
SQL;
	}

	/**
	 * Returns sql to list system tables
	 *
	 * @return string
	 */
	public function systemTableList()
	{
		return <<<SQL
			SELECT TRIM("RDB\$RELATION_NAME")
			FROM "RDB\$RELATIONS"
			WHERE "RDB\$SYSTEM_FLAG"=1
			ORDER BY "RDB\$RELATION_NAME" ASC
SQL;
	}

	/**
	 * Returns sql to list views
	 *
	 * @return string
	 */
	public function viewList()
	{
		return <<<SQL
			SELECT DISTINCT TRIM("RDB\$VIEW_NAME")
			FROM "RDB\$VIEW_RELATIONS"
SQL;
	}

	/**
	 * Returns sql to list triggers
	 *
	 * @return string
	 */
	public function triggerList()
	{
		return <<<SQL
			SELECT * FROM "RDB\$FUNCTIONS"
			WHERE "RDB\$SYSTEM_FLAG" = 0
SQL;
	}

	/**
	 * Return sql to list functions
	 *
	 * @return string
	 */
	public function functionList()
	{
		return 'SELECT * FROM "RDB$FUNCTIONS"';
	}

	/**
	 * Return sql to list stored procedures
	 *
	 * @return string
	 */
	public function procedureList()
	{
		return <<<SQL
			SELECT "RDB\$PROCEDURE_NAME",
				"RDB\$PROCEDURE_ID",
				"RDB\$PROCEDURE_INPUTS",
				"RDB\$PROCEDURE_OUTPUTS",
				"RDB\$DESCRIPTION",
				"RDB\$PROCEDURE_SOURCE",
				"RDB\$SECURITY_CLASS",
				"RDB\$OWNER_NAME",
				"RDB\$RUNTIME",
				"RDB\$SYSTEM_FLAG",
				"RDB\$PROCEDURE_TYPE",
				"RDB\$VALID_BLR"
			FROM "RDB\$PROCEDURES"
			ORDER BY "RDB\$PROCEDURE_NAME" ASC
SQL;

	}

	/**
	 * Return sql to list sequences
	 *
	 * @return string
	 */
	public function sequenceList()
	{
		return <<<SQL
			SELECT TRIM("RDB\$GENERATOR_NAME")
			FROM "RDB\$GENERATORS"
			WHERE "RDB\$SYSTEM_FLAG" = 0
SQL;
	}

	/**
	 * Return sql to list columns of the specified table
	 *
	 * @param string $table
	 * @return string
	 */
	public function columnList($table)
	{
		return <<<SQL
			SELECT r.RDB\$FIELD_NAME AS field_name,
				r.RDB\$DESCRIPTION AS field_description,
				r.RDB\$DEFAULT_VALUE AS field_default_value,
				r.RDB\$NULL_FLAG AS field_not_null_constraint,
				f.RDB\$FIELD_LENGTH AS field_length,
				f.RDB\$FIELD_PRECISION AS field_precision,
				f.RDB\$FIELD_SCALE AS field_scale,
				CASE f.RDB\$FIELD_TYPE
					WHEN 261 THEN 'BLOB'
					WHEN 14 THEN 'CHAR'
					WHEN 40 THEN 'CSTRING'
					WHEN 11 THEN 'D_FLOAT'
					WHEN 27 THEN 'DOUBLE'
					WHEN 10 THEN 'FLOAT'
					WHEN 16 THEN 'INT64'
					WHEN 8 THEN 'INTEGER'
					WHEN 9 THEN 'QUAD'
					WHEN 7 THEN 'SMALLINT'
					WHEN 12 THEN 'DATE'
					WHEN 13 THEN 'TIME'
					WHEN 35 THEN 'TIMESTAMP'
					WHEN 37 THEN 'VARCHAR'
				ELSE 'UNKNOWN'
				END AS field_type,
				f.RDB\$FIELD_SUB_TYPE AS field_subtype,
				coll.RDB\$COLLATION_NAME AS field_collation,
				cset.RDB\$CHARACTER_SET_NAME AS field_charset
			FROM RDB\$RELATION_FIELDS r
			LEFT JOIN RDB\$FIELDS f ON r.RDB\$FIELD_SOURCE = f.RDB\$FIELD_NAME
			LEFT JOIN RDB\$COLLATIONS coll ON f.RDB\$COLLATION_ID = coll.RDB\$COLLATION_ID
			LEFT JOIN RDB\$CHARACTER_SETS cset ON f.RDB\$CHARACTER_SET_ID = cset.RDB\$CHARACTER_SET_ID
			WHERE r.RDB\$RELATION_NAME='{$table}'
			ORDER BY r.RDB\$FIELD_POSITION
SQL;
	}

	/**
	 * SQL to show list of field types
	 *
	 * @return string
	 */
	public function typeList()
	{
		return <<<SQL
			SELECT "RDB\$TYPE_NAME", "RDB\$FIELD_NAME" FROM "RDB\$TYPES"
			WHERE "RDB\$FIELD_NAME" IN ('RDB\$FIELD_TYPE', 'RDB\$FIELD_SUB_TYPE')
			ORDER BY "RDB\$FIELD_NAME" DESC, "RDB\$TYPE_NAME" ASC
SQL;
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
		return <<<SQL
		SELECT DISTINCT
			TRIM(d1.RDB\$FIELD_NAME) AS "child_column",
			TRIM(d2.RDB\$DEPENDED_ON_NAME) AS "parent_table",
			TRIM(d2.RDB\$FIELD_NAME) AS "parent_column",
			TRIM(refc.RDB\$UPDATE_RULE) AS "update",
			TRIM(refc.RDB\$DELETE_RULE) AS "delete"
		FROM RDB\$RELATION_CONSTRAINTS AS rc
		LEFT JOIN RDB\$REF_CONSTRAINTS refc ON rc.RDB\$CONSTRAINT_NAME = refc.RDB\$CONSTRAINT_NAME
		LEFT JOIN RDB\$DEPENDENCIES d1 ON d1.RDB\$DEPENDED_ON_NAME = rc.RDB\$RELATION_NAME
		LEFT JOIN RDB\$DEPENDENCIES d2 ON d1.RDB\$DEPENDENT_NAME = d2.RDB\$DEPENDENT_NAME
		WHERE rc.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
			AND d1.RDB\$DEPENDED_ON_NAME <> d2.RDB\$DEPENDED_ON_NAME
			AND d1.RDB\$FIELD_NAME <> d2.RDB\$FIELD_NAME
			AND rc.RDB\$RELATION_NAME = '{$table}'  -- table name
SQL;
	}

	/**
	 * Get the list of indexes for the current table
	 *
	 * @param string $table
	 * @return array
	 */
	public function indexList($table)
	{
		return <<<SQL
			SELECT "RDB\$INDEX_NAME", "RDB\$UNIQUE_FLAG", "RDB\$FOREIGN_KEY"
			FROM "RDB\$INDICES"
			WHERE "RDB\$RELATION_NAME"='{$table}'
SQL;
	}
}