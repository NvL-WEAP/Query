<?php
/**
 * Query
 *
 * Free Query Builder / Database Abstraction Layer
 *
 * @package		Query
 * @author		Timothy J. Warren
 * @copyright	Copyright (c) 2012
 * @link 		https://github.com/aviat4ion/Query
 * @license		http://philsturgeon.co.uk/code/dbad-license
 */

// --------------------------------------------------------------------------

/**
 * Utility Class to parse sql clauses for properly escaping identifiers
 *
 * @package Query
 * @subpackage Query
 */
class Query_Parser {

	/**
	 * Regex patterns for various syntax components
	 *
	 * @var array
	 */
	private $match_patterns = array(
		'function' => '([a-zA-Z0-9_]+\((.*?)\))',
		'identifier' => '([a-zA-Z0-9_-]+\.?)+',
		'operator' => '=|AND|&&?|~|\|\|?|\^|/|>=?|<=?|-|%|OR|\+|NOT|\!=?|<>|XOR'
	);

	/**
	 * Regex matches
	 *
	 * @var array
	 */
	public $matches = array(
		'functions' => array(),
		'identifiers' => array(),
		'operators' => array(),
		'combined' => array(),
	);

	/**
	 * Constructor/entry point into parser
	 *
	 * @param string
	 */
	public function __construct($sql = '')
	{
		// Get sql clause components
		preg_match_all('`'.$this->match_patterns['function'].'`', $sql, $this->matches['functions'], PREG_SET_ORDER);
		preg_match_all('`'.$this->match_patterns['identifier'].'`', $sql, $this->matches['identifiers'], PREG_SET_ORDER);
		preg_match_all('`'.$this->match_patterns['operator'].'`', $sql, $this->matches['operators'], PREG_SET_ORDER);

		// Get everything at once for ordering
		$full_pattern = '`'.$this->match_patterns['function'].'+|'.$this->match_patterns['identifier'].'|('.$this->match_patterns['operator'].')+`i';
		preg_match_all($full_pattern, $sql, $this->matches['combined'], PREG_SET_ORDER);

		// Go through the matches, and get the most relevant matches
		$this->matches = array_map(array($this, 'filter_array'), $this->matches);
	}

	// --------------------------------------------------------------------------

	/**
	 * Public parser method for seting the parse string
	 *
	 * @param string
	 */
	public function parse_join($sql)
	{
		$this->__construct($sql);
		return $this->matches;
	}

	// --------------------------------------------------------------------------

	/**
	 * Returns a more useful match array
	 *
	 * @param array
	 * @return array
	 */
	private function filter_array($array)
	{
		$new_array = array();

		foreach($array as $row)
		{
			if (is_array($row))
			{
				$new_array[] = $row[0];
			}
			else
			{
				$new_array[] = $row;
			}
		}

		return $new_array;
	}

}

// End of query_parser.php