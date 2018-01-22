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
namespace Query;

use BadMethodCallException;
use PDOStatement;
use Query\Drivers\DriverInterface;

/**
 * Convenience class for creating sql queries
 */
class QueryBuilder extends AbstractQueryBuilder implements QueryBuilderInterface {

	/**
	 * String class values to be reset
	 *
	 * @var array
	 */
	private $stringVars = [
		'selectString',
		'fromString',
		'setString',
		'orderString',
		'groupString',
		'limit',
		'offset',
		'explain',
	];

	/**
	 * Array class variables to be reset
	 *
	 * @var array
	 */
	private $arrayVars = [
		'setArrayKeys',
		'orderArray',
		'groupArray',
		'values',
		'whereValues',
		'queryMap',
		'havingMap'
	];

	// --------------------------------------------------------------------------
	// ! Methods
	// --------------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param DriverInterface $db
	 * @param QueryParser $parser
	 */
	public function __construct(DriverInterface $db, QueryParser $parser)
	{
		// Inject driver and parser
		$this->db = $db;
		$this->parser = $parser;

		$this->queries['total_time'] = 0;

		// Alias driver sql and util classes
		$this->sql = $this->db->getSql();
		$this->util = $this->db->getUtil();
	}

	/**
	 * Destructor
	 * @codeCoverageIgnore
	 */
	public function __destruct()
	{
		$this->db = NULL;
	}

	/**
	 * Calls a function further down the inheritance chain
	 *
	 * @param string $name
	 * @param array $params
	 * @return mixed
	 * @throws BadMethodCallException
	 */
	public function __call(string $name, array $params)
	{
		// Alias snake_case method calls
		$camelName = \to_camel_case($name);

		foreach([$this, $this->db] as $object)
		{
			foreach([$name, $camelName] as $methodName)
			{
				if (method_exists($object, $methodName))
				{
					return \call_user_func_array([$object, $methodName], $params);
				}
			}

		}

		throw new BadMethodCallException('Method does not exist');
	}

	// --------------------------------------------------------------------------
	// ! Select Queries
	// --------------------------------------------------------------------------

	/**
	 * Specifies rows to select in a query
	 *
	 * @param string $fields
	 * @return QueryBuilderInterface
	 */
	public function select(string $fields): QueryBuilderInterface
	{
		// Split fields by comma
		$fieldsArray = explode(',', $fields);
		$fieldsArray = array_map('mb_trim', $fieldsArray);

		// Split on 'As'
		foreach ($fieldsArray as $key => $field)
		{
			if (stripos($field, 'as') !== FALSE)
			{
				$fieldsArray[$key] = preg_split('` as `i', $field);
				$fieldsArray[$key] = array_map('mb_trim', $fieldsArray[$key]);
			}
		}

		// Quote the identifiers
		$safeArray = $this->db->quoteIdent($fieldsArray);

		unset($fieldsArray);

		// Join the strings back together
		for($i = 0, $c = count($safeArray); $i < $c; $i++)
		{
			if (is_array($safeArray[$i]))
			{
				$safeArray[$i] = implode(' AS ', $safeArray[$i]);
			}
		}

		$this->selectString .= implode(', ', $safeArray);

		unset($safeArray);

		return $this;
	}

	/**
	 * Selects the maximum value of a field from a query
	 *
	 * @param string $field
	 * @param string|bool $as
	 * @return QueryBuilderInterface
	 */
	public function selectMax(string $field, $as=FALSE): QueryBuilderInterface
	{
		// Create the select string
		$this->selectString .= ' MAX'.$this->_select($field, $as);
		return $this;
	}

	/**
	 * Selects the minimum value of a field from a query
	 *
	 * @param string $field
	 * @param string|bool $as
	 * @return QueryBuilderInterface
	 */
	public function selectMin(string $field, $as=FALSE): QueryBuilderInterface
	{
		// Create the select string
		$this->selectString .= ' MIN'.$this->_select($field, $as);
		return $this;
	}

	/**
	 * Selects the average value of a field from a query
	 *
	 * @param string $field
	 * @param string|bool $as
	 * @return QueryBuilderInterface
	 */
	public function selectAvg(string $field, $as=FALSE): QueryBuilderInterface
	{
		// Create the select string
		$this->selectString .= ' AVG'.$this->_select($field, $as);
		return $this;
	}

	/**
	 * Selects the sum of a field from a query
	 *
	 * @param string $field
	 * @param string|bool $as
	 * @return QueryBuilderInterface
	 */
	public function selectSum(string $field, $as=FALSE): QueryBuilderInterface
	{
		// Create the select string
		$this->selectString .= ' SUM'.$this->_select($field, $as);
		return $this;
	}

	/**
	 * Adds the 'distinct' keyword to a query
	 *
	 * @return QueryBuilderInterface
	 */
	public function distinct(): QueryBuilderInterface
	{
		// Prepend the keyword to the select string
		$this->selectString = ' DISTINCT '.$this->selectString;
		return $this;
	}

	/**
	 * Tell the database to give you the query plan instead of result set
	 *
	 * @return QueryBuilderInterface
	 */
	public function explain(): QueryBuilderInterface
	{
		$this->explain = TRUE;
		return $this;
	}

	/**
	 * Specify the database table to select from
	 *
	 * @param string $tblname
	 * @return QueryBuilderInterface
	 */
	public function from($tblname): QueryBuilderInterface
	{
		// Split identifiers on spaces
		$identArray = explode(' ', \mb_trim($tblname));
		$identArray = array_map('\\mb_trim', $identArray);

		// Quote the identifiers
		$identArray[0] = $this->db->quoteTable($identArray[0]);
		$identArray = $this->db->quoteIdent($identArray);

		// Paste it back together
		$this->fromString = implode(' ', $identArray);

		return $this;
	}

	// --------------------------------------------------------------------------
	// ! 'Like' methods
	// --------------------------------------------------------------------------

	/**
	 * Creates a Like clause in the sql statement
	 *
	 * @param string $field
	 * @param mixed $val
	 * @param string $pos
	 * @return QueryBuilderInterface
	 */
	public function like($field, $val, $pos='both'): QueryBuilderInterface
	{
		return $this->_like($field, $val, $pos, 'LIKE', 'AND');
	}

	/**
	 * Generates an OR Like clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @param string $pos
	 * @return QueryBuilderInterface
	 */
	public function orLike($field, $val, $pos='both'): QueryBuilderInterface
	{
		return $this->_like($field, $val, $pos, 'LIKE', 'OR');
	}

	/**
	 * Generates a NOT LIKE clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @param string $pos
	 * @return QueryBuilderInterface
	 */
	public function notLike($field, $val, $pos='both'): QueryBuilderInterface
	{
		return $this->_like($field, $val, $pos, 'NOT LIKE', 'AND');
	}

	/**
	 * Generates a OR NOT LIKE clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @param string $pos
	 * @return QueryBuilderInterface
	 */
	public function orNotLike($field, $val, $pos='both'): QueryBuilderInterface
	{
		return $this->_like($field, $val, $pos, 'NOT LIKE', 'OR');
	}

	// --------------------------------------------------------------------------
	// ! Having methods
	// --------------------------------------------------------------------------

	/**
	 * Generates a 'Having' clause
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @return QueryBuilderInterface
	 */
	public function having($key, $val=[]): QueryBuilderInterface
	{
		return $this->_having($key, $val, 'AND');
	}

	/**
	 * Generates a 'Having' clause prefixed with 'OR'
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @return QueryBuilderInterface
	 */
	public function orHaving($key, $val=[]): QueryBuilderInterface
	{
		return $this->_having($key, $val, 'OR');
	}

	// --------------------------------------------------------------------------
	// ! 'Where' methods
	// --------------------------------------------------------------------------

	/**
	 * Specify condition(s) in the where clause of a query
	 * Note: this function works with key / value, or a
	 * passed array with key / value pairs
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @param mixed $escape
	 * @return QueryBuilderInterface
	 */
	public function where($key, $val=[], $escape=NULL): QueryBuilderInterface
	{
		return $this->_whereString($key, $val, 'AND');
	}

	/**
	 * Where clause prefixed with "OR"
	 *
	 * @param string $key
	 * @param mixed $val
	 * @return QueryBuilderInterface
	 */
	public function orWhere($key, $val=[]): QueryBuilderInterface
	{
		return $this->_whereString($key, $val, 'OR');
	}

	/**
	 * Where clause with 'IN' statement
	 *
	 * @param mixed $field
	 * @param mixed $val
	 * @return QueryBuilderInterface
	 */
	public function whereIn($field, $val=[]): QueryBuilderInterface
	{
		return $this->_whereIn($field, $val);
	}

	/**
	 * Where in statement prefixed with "or"
	 *
	 * @param string $field
	 * @param mixed $val
	 * @return QueryBuilderInterface
	 */
	public function orWhereIn($field, $val=[]): QueryBuilderInterface
	{
		return $this->_whereIn($field, $val, 'IN', 'OR');
	}

	/**
	 * WHERE NOT IN (FOO) clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @return QueryBuilderInterface
	 */
	public function whereNotIn($field, $val=[]): QueryBuilderInterface
	{
		return $this->_whereIn($field, $val, 'NOT IN', 'AND');
	}

	/**
	 * OR WHERE NOT IN (FOO) clause
	 *
	 * @param string $field
	 * @param mixed $val
	 * @return QueryBuilderInterface
	 */
	public function orWhereNotIn($field, $val=[]): QueryBuilderInterface
	{
		return $this->_whereIn($field, $val, 'NOT IN', 'OR');
	}

	// --------------------------------------------------------------------------
	// ! Other Query Modifier methods
	// --------------------------------------------------------------------------

	/**
	 * Sets values for inserts / updates / deletes
	 *
	 * @param mixed $key
	 * @param mixed $val
	 * @return QueryBuilderInterface
	 */
	public function set($key, $val = NULL): QueryBuilderInterface
	{
		$this->_mixedSet($this->setArrayKeys, $key, $val, self::KEY);
		$this->_mixedSet($this->values, $key, $val, self::VALUE);

		// Use the keys of the array to make the insert/update string
		// Escape the field names
		$this->setArrayKeys = array_map([$this->db, '_quote'], $this->setArrayKeys);

		// Generate the "set" string
		$this->setString = implode('=?,', $this->setArrayKeys);
		$this->setString .= '=?';

		return $this;
	}

	/**
	 * Creates a join phrase in a compiled query
	 *
	 * @param string $table
	 * @param string $condition
	 * @param string $type
	 * @return QueryBuilderInterface
	 */
	public function join($table, $condition, $type=''): QueryBuilderInterface
	{
		// Prefix and quote table name
		$table = explode(' ', mb_trim($table));
		$table[0] = $this->db->quoteTable($table[0]);
		$table = $this->db->quoteIdent($table);
		$table = implode(' ', $table);

		// Parse out the join condition
		$parsedCondition = $this->parser->compileJoin($condition);
		$condition = $table . ' ON ' . $parsedCondition;

		$this->_appendMap("\n" . strtoupper($type) . ' JOIN ', $condition, 'join');

		return $this;
	}

	/**
	 * Group the results by the selected field(s)
	 *
	 * @param mixed $field
	 * @return QueryBuilderInterface
	 */
	public function groupBy($field): QueryBuilderInterface
	{
		if ( ! is_scalar($field))
		{
			$newGroupArray = array_map([$this->db, 'quoteIdent'], $field);
			$this->groupArray = array_merge($this->groupArray, $newGroupArray);
		}
		else
		{
			$this->groupArray[] = $this->db->quoteIdent($field);
		}

		$this->groupString = ' GROUP BY ' . implode(',', $this->groupArray);

		return $this;
	}

	/**
	 * Order the results by the selected field(s)
	 *
	 * @param string $field
	 * @param string $type
	 * @return QueryBuilderInterface
	 */
	public function orderBy($field, $type=""): QueryBuilderInterface
	{
		// When ordering by random, do an ascending order if the driver
		// doesn't support random ordering
		if (stripos($type, 'rand') !== FALSE)
		{
			$rand = $this->sql->random();
			$type = $rand ?? 'ASC';
		}

		// Set fields for later manipulation
		$field = $this->db->quoteIdent($field);
		$this->orderArray[$field] = $type;

		$orderClauses = [];

		// Flatten key/val pairs into an array of space-separated pairs
		foreach($this->orderArray as $k => $v)
		{
			$orderClauses[] = $k . ' ' . strtoupper($v);
		}

		// Set the final string
		$this->orderString = ( ! isset($rand))
			? "\nORDER BY ".implode(', ', $orderClauses)
			: "\nORDER BY".$rand;

		return $this;
	}

	/**
	 * Set a limit on the current sql statement
	 *
	 * @param int $limit
	 * @param int|bool $offset
	 * @return QueryBuilderInterface
	 */
	public function limit($limit, $offset=FALSE): QueryBuilderInterface
	{
		$this->limit = (int) $limit;
		$this->offset = $offset;

		return $this;
	}

	// --------------------------------------------------------------------------
	// ! Query Grouping Methods
	// --------------------------------------------------------------------------

	/**
	 * Adds a paren to the current query for query grouping
	 *
	 * @return QueryBuilderInterface
	 */
	public function groupStart(): QueryBuilderInterface
	{
		$conj = (empty($this->queryMap)) ? ' WHERE ' : ' ';

		$this->_appendMap($conj, '(', 'group_start');

		return $this;
	}

	/**
	 * Adds a paren to the current query for query grouping,
	 * prefixed with 'NOT'
	 *
	 * @return QueryBuilderInterface
	 */
	public function notGroupStart(): QueryBuilderInterface
	{
		$conj = (empty($this->queryMap)) ? ' WHERE ' : ' AND ';

		$this->_appendMap($conj, ' NOT (', 'group_start');

		return $this;
	}

	/**
	 * Adds a paren to the current query for query grouping,
	 * prefixed with 'OR'
	 *
	 * @return QueryBuilderInterface
	 */
	public function orGroupStart(): QueryBuilderInterface
	{
		$this->_appendMap('', ' OR (', 'group_start');

		return $this;
	}

	/**
	 * Adds a paren to the current query for query grouping,
	 * prefixed with 'OR NOT'
	 *
	 * @return QueryBuilderInterface
	 */
	public function orNotGroupStart(): QueryBuilderInterface
	{
		$this->_appendMap('', ' OR NOT (', 'group_start');

		return $this;
	}

	/**
	 * Ends a query group
	 *
	 * @return QueryBuilderInterface
	 */
	public function groupEnd(): QueryBuilderInterface
	{
		$this->_appendMap('', ')', 'group_end');

		return $this;
	}

	// --------------------------------------------------------------------------
	// ! Query execution methods
	// --------------------------------------------------------------------------

	/**
	 * Select and retrieve all records from the current table, and/or
	 * execute current compiled query
	 *
	 * @param string $table
	 * @param int|bool $limit
	 * @param int|bool $offset
	 * @return PDOStatement
	 */
	public function get($table='', $limit=FALSE, $offset=FALSE): PDOStatement
	{
		// Set the table
		if ( ! empty($table))
		{
			$this->from($table);
		}

		// Set the limit, if it exists
		if (\is_int($limit))
		{
			$this->limit($limit, $offset);
		}

		return $this->_run('get', $table);
	}

	/**
	 * Convenience method for get() with a where clause
	 *
	 * @param string $table
	 * @param array $where
	 * @param int|bool $limit
	 * @param int|bool $offset
	 * @return PDOStatement
	 */
	public function getWhere($table, $where=[], $limit=FALSE, $offset=FALSE): PDOStatement
	{
		// Create the where clause
		$this->where($where);

		// Return the result
		return $this->get($table, $limit, $offset);
	}

	/**
	 * Retrieve the number of rows in the selected table
	 *
	 * @param string $table
	 * @return int
	 */
	public function countAll($table): int
	{
		$sql = 'SELECT * FROM '.$this->db->quoteTable($table);
		$res = $this->db->query($sql);
		return (int) count($res->fetchAll());
	}

	/**
	 * Retrieve the number of results for the generated query - used
	 * in place of the get() method
	 *
	 * @param string $table
	 * @param boolean $reset
	 * @return int
	 */
	public function countAllResults(string $table='', bool $reset = TRUE): int
	{
		// Set the table
		if ( ! empty($table))
		{
			$this->from($table);
		}

		$result = $this->_run('get', $table, NULL, NULL, $reset);
		$rows = $result->fetchAll();

		return (int) count($rows);
	}

	/**
	 * Creates an insert clause, and executes it
	 *
	 * @param string $table
	 * @param mixed $data
	 * @return PDOStatement
	 */
	public function insert($table, $data=[]): PDOStatement
	{
		if ( ! empty($data))
		{
			$this->set($data);
		}

		return $this->_run("insert", $table);
	}

	/**
	 * Creates and executes a batch insertion query
	 *
	 * @param string $table
	 * @param array $data
	 * @return PDOStatement
	 */
	public function insertBatch($table, $data=[]): PDOStatement
	{
		// Get the generated values and sql string
		list($sql, $data) = $this->db->insertBatch($table, $data);

		return ( ! is_null($sql))
			? $this->_run('', $table, $sql, $data)
			: NULL;
	}

	/**
	 * Creates an update clause, and executes it
	 *
	 * @param string $table
	 * @param mixed $data
	 * @return PDOStatement
	 */
	public function update($table, $data=[]): PDOStatement
	{
		if ( ! empty($data))
		{
			$this->set($data);
		}

		return $this->_run("update", $table);
	}

	/**
	 * Creates a batch update, and executes it.
	 * Returns the number of affected rows
	 *
	 * @param string $table
	 * @param array|object $data
	 * @param string $where
	 * @return int|null
	 */
	public function updateBatch($table, $data, $where)
	{
		// Get the generated values and sql string
		list($sql, $data) = $this->db->updateBatch($table, $data, $where);

		return ( ! is_null($sql))
			? $this->_run('', $table, $sql, $data)
			: NULL;
	}

	/**
	 * Insertion with automatic overwrite, rather than attempted duplication
	 *
	 * @param string $table
	 * @param array $data
	 * @return \PDOStatement|null
	 */
	public function replace($table, $data=[])
	{
		if ( ! empty($data))
		{
			$this->set($data);
		}

		return $this->_run("replace", $table);
	}

	/**
	 * Deletes data from a table
	 *
	 * @param string $table
	 * @param mixed $where
	 * @return PDOStatement
	 */
	public function delete($table, $where=''): PDOStatement
	{
		// Set the where clause
		if ( ! empty($where))
		{
			$this->where($where);
		}

		return $this->_run("delete", $table);
	}

	// --------------------------------------------------------------------------
	// ! SQL Returning Methods
	// --------------------------------------------------------------------------

	/**
	 * Returns the generated 'select' sql query
	 *
	 * @param string $table
	 * @param bool $reset
	 * @return string
	 */
	public function getCompiledSelect(string $table='', bool $reset=TRUE): string
	{
		// Set the table
		if ( ! empty($table))
		{
			$this->from($table);
		}

		return $this->_getCompile('select', $table, $reset);
	}

	/**
	 * Returns the generated 'insert' sql query
	 *
	 * @param string $table
	 * @param bool $reset
	 * @return string
	 */
	public function getCompiledInsert(string $table, bool $reset=TRUE): string
	{
		return $this->_getCompile('insert', $table, $reset);
	}

	/**
	 * Returns the generated 'update' sql query
	 *
	 * @param string $table
	 * @param bool $reset
	 * @return string
	 */
	public function getCompiledUpdate(string $table='', bool $reset=TRUE): string
	{
		return $this->_getCompile('update', $table, $reset);
	}

	/**
	 * Returns the generated 'delete' sql query
	 *
	 * @param string $table
	 * @param bool $reset
	 * @return string
	 */
	public function getCompiledDelete(string $table='', bool $reset=TRUE): string
	{
		return $this->_getCompile('delete', $table, $reset);
	}

	// --------------------------------------------------------------------------
	// ! Miscellaneous Methods
	// --------------------------------------------------------------------------

	/**
	 * Clear out the class variables, so the next query can be run
	 *
	 * @return void
	 */
	public function resetQuery()
	{
		// Reset strings and booleans
		foreach($this->stringVars as $var)
		{
			$this->$var = NULL;
		}

		// Reset arrays
		foreach($this->arrayVars as $var)
		{
			$this->$var = [];
		}
	}
}
// End of query_builder.php