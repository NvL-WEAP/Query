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

namespace Query\Tests\Drivers\PgSQL;

use PDO;
use Query\Drivers\Pgsql\Driver;
use Query\Tests\BaseDriverTest;
use TypeError;

/**
 * PgTest class.
 *
 * @extends DBTest
 * @requires extension pdo_pgsql
 */
class PgSQLDriverTest extends BaseDriverTest {

	public function setUp()
    {
		// If the database isn't installed, skip the tests
		if ( ! class_exists(Driver::class))
		{
			$this->markTestSkipped('Postgres extension for PDO not loaded');
		}
	}

	public static function setUpBeforeClass()
	{

		$params = get_json_config();
		if ($var = getenv('TRAVIS'))
		{
			self::$db = new Driver('host=127.0.0.1;port=5432;dbname=test', 'postgres');
		}
		// Attempt to connect, if there is a test config file
		else if ($params !== FALSE)
		{
			$params = $params->pgsql;
			self::$db = new Driver("pgsql:host={$params->host};dbname={$params->database};port=5432", $params->user, $params->pass);
		}

		self::$db->setTablePrefix('create_');
	}

	public function testExists()
	{
		$drivers = PDO::getAvailableDrivers();
		$this->assertTrue(in_array('pgsql', $drivers, TRUE));
	}

	public function testConnection()
	{
		if (empty(self::$db))  return;

		$this->assertIsA(self::$db, Driver::class);
	}

	public function testCreateTable()
	{
		self::$db->exec(file_get_contents(QTEST_DIR.'/db_files/pgsql.sql'));

		// Drop the table(s) if they exist
		$sql = 'DROP TABLE IF EXISTS "create_test"';
		self::$db->query($sql);
		$sql = 'DROP TABLE IF EXISTS "create_join"';
		self::$db->query($sql);


		//Attempt to create the table
		$sql = self::$db->getUtil()->createTable('create_test',
			array(
				'id' => 'integer',
				'key' => 'TEXT',
				'val' => 'TEXT',
			),
			array(
				'id' => 'PRIMARY KEY'
			)
		);

		self::$db->query($sql);

		//Attempt to create the table
		$sql = self::$db->getUtil()->createTable('create_join',
			array(
				'id' => 'integer',
				'key' => 'TEXT',
				'val' => 'TEXT',
			),
			array(
				'id' => 'PRIMARY KEY'
			)
		);
		self::$db->query($sql);

		//echo $sql.'<br />';

		//Reset
		//unset(self::$db);
		//$this->setUp();

		//Check
		$dbs = self::$db->getTables();
		$this->assertTrue(in_array('create_test', $dbs, TRUE));

	}

	public function testTruncate()
	{
		self::$db->truncate('test');
		$this->assertEquals(0, self::$db->countAll('test'));

		self::$db->truncate('join');
		$this->assertEquals(0, self::$db->countAll('join'));
	}

	public function testPreparedStatements()
	{
		$sql = <<<SQL
			INSERT INTO "create_test" ("id", "key", "val")
			VALUES (?,?,?)
SQL;
		$statement = self::$db->prepareQuery($sql, array(1,'boogers', 'Gross'));

		$statement->execute();

		$res = self::$db->query('SELECT * FROM "create_test" WHERE "id"=1')
			->fetch(PDO::FETCH_ASSOC);

		$this->assertEquals([
			'id' => 1,
			'key' => 'boogers',
			'val' => 'Gross'
		], $res);
	}

	public function testBadPreparedStatement()
	{
		$this->expectException(TypeError::class);

		$sql = <<<SQL
			INSERT INTO "create_test" ("id", "key", "val")
			VALUES (?,?,?)
SQL;

		self::$db->prepareQuery($sql, 'foo');
	}

	public function testPrepareExecute()
	{
		if (empty(self::$db))  return;

		$sql = <<<SQL
			INSERT INTO "create_test" ("id", "key", "val")
			VALUES (?,?,?)
SQL;
		self::$db->prepareExecute($sql, array(
			2, 'works', 'also?'
		));

		$res = self::$db->query('SELECT * FROM "create_test" WHERE "id"=2')
			->fetch(PDO::FETCH_ASSOC);

		$this->assertEquals([
			'id' => 2,
			'key' => 'works',
			'val' => 'also?'
		], $res);
	}

	public function testCommitTransaction()
	{
		if (empty(self::$db))  return;

		self::$db->beginTransaction();

		$sql = 'INSERT INTO "create_test" ("id", "key", "val") VALUES (10, 12, 14)';
		self::$db->query($sql);

		$res = self::$db->commit();
		$this->assertTrue($res);
	}

	public function testRollbackTransaction()
	{
		if (empty(self::$db))  return;

		self::$db->beginTransaction();

		$sql = 'INSERT INTO "create_test" ("id", "key", "val") VALUES (182, 96, 43)';
		self::$db->query($sql);

		$res = self::$db->rollback();
		$this->assertTrue($res);
	}

	public function testGetSchemas()
	{
		$this->assertTrue(is_array(self::$db->getSchemas()));
	}

	public function testGetDBs()
	{
		$this->assertTrue(is_array(self::$db->getDbs()));
	}

	public function testGetFunctions()
	{
		$this->assertNull(self::$db->getFunctions());
	}
}
