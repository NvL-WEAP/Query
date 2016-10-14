<?php declare(strict_types=1);
/**
 * Query
 *
 * SQL Query Builder / Database Abstraction Layer
 *
 * PHP version 7
 *
 * @package     Query
 * @author      Timothy J. Warren <tim@timshomepage.net>
 * @copyright   2012 - 2016 Timothy J. Warren
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link        https://git.timshomepage.net/aviat4ion/Query
 */
namespace Query\Drivers\Pgsql;

use Query\Drivers\AbstractSQL;

/**
 * PostgreSQL specific SQL
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
		return "EXPLAIN VERBOSE {$sql}";
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
		return <<<SQL
			SELECT "datname" FROM "pg_database"
			WHERE "datname" NOT IN ('template0','template1')
			ORDER BY "datname" ASC
SQL;
	}

	/**
	 * Returns sql to list tables
	 *
	 * @return string
	 */
	public function tableList()
	{
		return <<<SQL
			SELECT "table_name"
			FROM "information_schema"."tables"
			WHERE "table_type" = 'BASE TABLE'
			AND "table_schema" NOT IN
				('pg_catalog', 'information_schema');
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
			SELECT "table_name"
			FROM "information_schema"."tables"
			WHERE "table_type" = 'BASE TABLE'
			AND "table_schema" IN
				('pg_catalog', 'information_schema');
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
		 	SELECT "viewname" FROM "pg_views"
			WHERE "schemaname" NOT IN
				('pg_catalog', 'information_schema')
			AND "viewname" !~ '^pg_'
			ORDER BY "viewname" ASC
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
			SELECT *
			FROM "information_schema"."triggers"
			WHERE "trigger_schema" NOT IN
				('pg_catalog', 'information_schema')
SQL;
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
	 * @return string
	 */
	public function procedureList()
	{
		return <<<SQL
			SELECT "routine_name"
			FROM "information_schema"."routines"
			WHERE "specific_schema" NOT IN
				('pg_catalog', 'information_schema')
			AND "type_udt_name" != 'trigger';
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
			SELECT "c"."relname"
			FROM "pg_class" "c"
			WHERE "c"."relkind" = 'S'
			ORDER BY "relname" ASC
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
			SELECT ordinal_position,
				column_name,
				data_type,
				column_default,
				is_nullable,
				character_maximum_length,
				numeric_precision
			FROM information_schema.columns
			WHERE table_name = '{$table}'
			ORDER BY ordinal_position;
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
			SELECT "typname" FROM "pg_catalog"."pg_type"
			WHERE "typname" !~ '^pg_|_'
			AND "typtype" = 'b'
			ORDER BY "typname"
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
			SELECT
				"att2"."attname" AS "child_column",
				"cl"."relname" AS "parent_table",
				"att"."attname" AS "parent_column",
				"con"."update" AS "update",
				"con"."update" AS "delete"
			FROM
				(SELECT
					unnest(con1.conkey) AS "parent",
					unnest(con1.confkey) AS "child",
					"con1"."confrelid",
					"con1"."conrelid",
					"con1"."confupdtype" as "update",
					"con1"."confdeltype" as "delete"
				FROM "pg_class" "cl"
				JOIN "pg_namespace" "ns" ON "cl"."relnamespace" = "ns"."oid"
				JOIN "pg_constraint" "con1" ON "con1"."conrelid" = "cl"."oid"
				WHERE "cl"."relname" = '{$table}'
					AND "ns"."nspname" = 'public'
					AND "con1"."contype" = 'f'
				)
				"con"
				JOIN "pg_attribute" "att" ON
					"att"."attrelid" = "con"."confrelid"
					AND "att"."attnum" = "con"."child"
				JOIN "pg_class" "cl" ON
					"cl"."oid" = "con"."confrelid"
				JOIN "pg_attribute" "att2" ON
					"att2"."attrelid" = "con"."conrelid"
					AND "att2"."attnum" = "con"."parent"
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
			SELECT
				t.relname AS table_name,
				i.relname AS index_name,
				array_to_string(array_agg(a.attname), ', ') AS column_names
			FROM
				pg_class t,
				pg_class i,
				pg_index ix,
				pg_attribute a
			WHERE
				t.oid = ix.indrelid
				AND i.oid = ix.indexrelid
				AND a.attrelid = t.oid
				AND a.attnum = ANY(ix.indkey)
				AND t.relkind = 'r'
				AND t.relname = '{$table}'
			GROUP BY
				t.relname,
				i.relname
			ORDER BY
				t.relname,
				i.relname;
SQL;
	}
}