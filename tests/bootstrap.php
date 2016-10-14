<?php
/**
 * Query
 *
 * Free Query Builder / Database Abstraction Layer
 *
 * @package		Query
 * @author		Timothy J. Warren
 * @copyright	Copyright (c) 2012 - 2014
 * @link 		https://github.com/aviat4ion/Query
 * @license		http://philsturgeon.co.uk/code/dbad-license
 */

use PHPUnit\Framework\TestCase;

/**
 * Unit test bootstrap - Using phpunit
 */
define('QTEST_DIR', realpath(__DIR__));
define('QBASE_DIR', realpath(QTEST_DIR.'/../') . '/');
define('QDS', DIRECTORY_SEPARATOR);

// Set up autoloader
require_once(QBASE_DIR . 'vendor/autoload.php');

// --------------------------------------------------------------------------

/**
 * Quercus detection for workarounds
 */
if ( ! defined('IS_QUERCUS'))
{
	if ( ! isset($_sERVERSOFTWARE))
	{
		define('IS_QUERCUS', FALSE);
	}
	else
	{
		$test = strpos($_sERVER["SERVER_SOFTWARE"],'Quercus') !== FALSE;
		define('IS_QUERCUS', $test);
		unset($test);
	}
}

function get_json_config()
{
	$files = array(
		__DIR__ . '/settings.json',
		__DIR__ . '/settings.json.dist'
	);

	foreach($files as $file)
	{
		if (is_file($file))
		{
			return json_decode(file_get_contents($file));
		}
	}

	return FALSE;
}

/**
 * Base class for TestCases
 */
class Query_TestCase extends TestCase {

	/**
	 * Wrapper for Simpletest's assertEqual
	 *
	 * @param mixed $expected
	 * @param mixed $actual
	 * @param string $message
	 */
	public function assertEqual($expected, $actual, $message='')
	{
		$this->assertEquals($expected, $actual, $message);
	}

	/**
	 * Wrapper for SimpleTest's assertIsA
	 *
	 * @param object $object
	 * @param string $type
	 * @param string $message
	 */
	public function assertIsA($object, $type, $message='')
	{
		$this->assertTrue(is_a($object, $type), $message);
	}

	/**
	 * Implementation of SimpleTest's assertReference
	 *
	 * @param mixed $first
	 * @param mixed $second
	 * @param string $message
	 */
	public function assertReference($first, $second, $message='')
	{
		if (is_object($first))
		{
			$res = ($first === $second);
		}
		else
		{
			$temp = $first;
			$first = uniqid("test");
			$isRef = ($first === $second);
			$first = $temp;
			$res = $isRef;
		}
		$this->assertTrue($res, $message);
	}
}

// --------------------------------------------------------------------------
$path = QTEST_DIR.QDS.'db_files'.QDS.'test_sqlite.db';
@unlink($path);

// Require base testing classes
require_once(QTEST_DIR . '/core/base_db_test.php');
require_once(QTEST_DIR . '/core/base_query_builder_test.php');


// End of bootstrap.php