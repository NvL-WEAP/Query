<?php
/**
 * Query
 *
 * Free Query Builder / Database Abstraction Layer
 *
 * @author 		Timothy J. Warren
 * @copyright	Copyright (c) 2012
 * @link 		https://github.com/aviat4ion/Query
 * @license 	http://philsturgeon.co.uk/code/dbad-license 
 */

// --------------------------------------------------------------------------

/**
 * Unit test bootstrap - Using php simpletest
 */
define('TEST_DIR', dirname(__FILE__));
define('BASE_DIR', str_replace(basename(TEST_DIR), '', TEST_DIR));
define('DS', DIRECTORY_SEPARATOR);

// Include simpletest
// it has to be set in your php path, or put in the tests folder
require_once('simpletest/autorun.php');

// Require base testing classes
require_once(TEST_DIR.'/parent.php');

// Include db classes
require_once(BASE_DIR.'autoload.php');

// Include db tests
// Load db classes based on capability
$src_path = BASE_DIR.'drivers/';
$test_path = TEST_DIR.'/databases/';

foreach(pdo_drivers() as $d)
{
	// PDO firebird isn't stable enough to 
	// bother, so skip it.
	if ($d === 'firebird')
	{
		continue;
	}

	$src_dir = "{$src_path}{$d}";
	
	if(is_dir($src_dir))
	{
		require_once("{$test_path}{$d}.php");
		require_once("{$test_path}{$d}-qb.php");
	}
}

// Load Firebird if there is support
if(function_exists('fbird_connect'))
{
	require_once("{$test_path}firebird.php");
	require_once("{$test_path}firebird-qb.php");
}