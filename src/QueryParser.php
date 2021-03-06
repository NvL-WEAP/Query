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

use Query\Drivers\DriverInterface;

/**
 * Utility Class to parse sql clauses for properly escaping identifiers
 */
class QueryParser {

	/**
	 * DB Driver
	 *
	 * @var DriverInterface
	 */
	private $db;

	/**
	 * Regex patterns for various syntax components
	 *
	 * @var array
	 */
	private $matchPatterns = [
		'function' => '([a-zA-Z0-9_]+\((.*?)\))',
		'identifier' => '([a-zA-Z0-9_-]+\.?)+',
		'operator' => '=|AND|&&?|~|\|\|?|\^|/|>=?|<=?|-|%|OR|\+|NOT|\!=?|<>|XOR'
	];

	/**
	 * Regex matches
	 *
	 * @var array
	 */
	public $matches = [
		'functions' => [],
		'identifiers' => [],
		'operators' => [],
		'combined' => [],
	];

	/**
	 * Constructor/entry point into parser
	 *
	 * @param DriverInterface $db
	 */
	public function __construct(DriverInterface $db)
	{
		$this->db = $db;
	}

	/**
	 * Parser method for setting the parse string
	 *
	 * @param string $sql
	 * @return array
	 */
	public function parseJoin(string $sql): array
	{
		// Get sql clause components
		preg_match_all('`'.$this->matchPatterns['function'].'`', $sql, $this->matches['functions'], PREG_SET_ORDER);
		preg_match_all('`'.$this->matchPatterns['identifier'].'`', $sql, $this->matches['identifiers'], PREG_SET_ORDER);
		preg_match_all('`'.$this->matchPatterns['operator'].'`', $sql, $this->matches['operators'], PREG_SET_ORDER);

		// Get everything at once for ordering
		$fullPattern = '`'.$this->matchPatterns['function'].'+|'.$this->matchPatterns['identifier'].'|('.$this->matchPatterns['operator'].')+`i';
		preg_match_all($fullPattern, $sql, $this->matches['combined'], PREG_SET_ORDER);

		// Go through the matches, and get the most relevant matches
		$this->matches = array_map([$this, 'filterArray'], $this->matches);

		return $this->matches;
	}

	/**
	 * Compiles a join condition after parsing
	 *
	 * @param string $condition
	 * @return string
	 */
	public function compileJoin(string $condition): string
	{
		$parts = $this->parseJoin($condition);
		$count = count($parts['identifiers']);

		// Go through and quote the identifiers
		for($i=0; $i <= $count; $i++)
		{
			if (in_array($parts['combined'][$i], $parts['identifiers']) && ! is_numeric($parts['combined'][$i]))
			{
				$parts['combined'][$i] = $this->db->quoteIdent($parts['combined'][$i]);
			}
		}

		return implode('', $parts['combined']);
	}

	/**
	 * Returns a more useful match array
	 *
	 * @param array $array
	 * @return array
	 */
	protected function filterArray(array $array): array
	{
		$newArray = [];

		foreach($array as $row)
		{
			$newArray[] =  (is_array($row)) ? $row[0] : $row;
		}

		return $newArray;
	}
}