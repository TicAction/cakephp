<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database\Schema;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Schema\Collection as SchemaCollection;
use Cake\Database\Schema\MysqlSchema;
use Cake\Database\Schema\Table;
use Cake\TestSuite\TestCase;


/**
 * Test case for Mysql Schema Dialect.
 */
class MysqlSchemaTest extends TestCase {

/**
 * Helper method for skipping tests that need a real connection.
 *
 * @return void
 */
	protected function _needsConnection() {
		$config = Configure::read('Datasource.test');
		$this->skipIf(strpos($config['datasource'], 'Mysql') === false, 'Not using Mysql for test config');
	}

/**
 * Dataprovider for column testing
 *
 * @return array
 */
	public static function columnProvider() {
		return [
			[
				'DATETIME',
				['type' => 'datetime', 'length' => null]
			],
			[
				'DATE',
				['type' => 'date', 'length' => null]
			],
			[
				'TIME',
				['type' => 'time', 'length' => null]
			],
			[
				'TINYINT(1)',
				['type' => 'boolean', 'length' => null]
			],
			[
				'TINYINT(2)',
				['type' => 'integer', 'length' => 2]
			],
			[
				'INTEGER(11)',
				['type' => 'integer', 'length' => 11]
			],
			[
				'BIGINT',
				['type' => 'biginteger', 'length' => null]
			],
			[
				'VARCHAR(255)',
				['type' => 'string', 'length' => 255]
			],
			[
				'CHAR(25)',
				['type' => 'string', 'length' => 25, 'fixed' => true]
			],
			[
				'TINYTEXT',
				['type' => 'string', 'length' => null]
			],
			[
				'BLOB',
				['type' => 'binary', 'length' => null]
			],
			[
				'MEDIUMBLOB',
				['type' => 'binary', 'length' => null]
			],
			[
				'FLOAT',
				['type' => 'float', 'length' => null, 'precision' => null]
			],
			[
				'DOUBLE',
				['type' => 'float', 'length' => null, 'precision' => null]
			],
			[
				'DECIMAL(11,2)',
				['type' => 'decimal', 'length' => 11, 'precision' => 2]
			],
			[
				'FLOAT(11,2)',
				['type' => 'float', 'length' => 11, 'precision' => 2]
			],
			[
				'DOUBLE(10,4)',
				['type' => 'float', 'length' => 10, 'precision' => 4]
			],
		];
	}

/**
 * Test parsing MySQL column types.
 *
 * @dataProvider columnProvider
 * @return void
 */
	public function testConvertColumnType($input, $expected) {
		$driver = $this->getMock('Cake\Database\Driver\Mysql');
		$dialect = new MysqlSchema($driver);
		$this->assertEquals($expected, $dialect->convertColumn($input));
	}

/**
 * Helper method for testing methods.
 *
 * @return void
 */
	protected function _createTables($connection) {
		$this->_needsConnection();
		$connection->execute('DROP TABLE IF EXISTS articles');
		$connection->execute('DROP TABLE IF EXISTS authors');

		$table = <<<SQL
CREATE TABLE authors (
id INT(11) PRIMARY KEY AUTO_INCREMENT,
name VARCHAR(50),
bio TEXT,
created DATETIME
)
SQL;
		$connection->execute($table);

		$table = <<<SQL
CREATE TABLE articles (
id BIGINT PRIMARY KEY AUTO_INCREMENT,
title VARCHAR(20) COMMENT 'A title',
body TEXT,
author_id INT(11) NOT NULL,
published BOOLEAN DEFAULT 0,
allow_comments TINYINT(1) DEFAULT 0,
created DATETIME,
KEY `author_idx` (`author_id`),
UNIQUE KEY `length_idx` (`title`(4))
) COLLATE=utf8_general_ci
SQL;
		$connection->execute($table);
	}

/**
 * Integration test for SchemaCollection & MysqlDialect.
 *
 * @return void
 */
	public function testListTables() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->listTables();

		$this->assertInternalType('array', $result);
		$this->assertCount(2, $result);
		$this->assertEquals('articles', $result[0]);
		$this->assertEquals('authors', $result[1]);
	}

/**
 * Test describing a table with Mysql
 *
 * @return void
 */
	public function testDescribeTable() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->describe('articles');
		$this->assertInstanceOf('Cake\Database\Schema\Table', $result);
		$expected = [
			'id' => [
				'type' => 'biginteger',
				'null' => false,
				'default' => null,
				'length' => 20,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'title' => [
				'type' => 'string',
				'null' => true,
				'default' => null,
				'length' => 20,
				'precision' => null,
				'comment' => 'A title',
				'fixed' => null,
			],
			'body' => [
				'type' => 'text',
				'null' => true,
				'default' => null,
				'length' => null,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'author_id' => [
				'type' => 'integer',
				'null' => false,
				'default' => null,
				'length' => 11,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'published' => [
				'type' => 'boolean',
				'null' => true,
				'default' => 0,
				'length' => null,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'allow_comments' => [
				'type' => 'boolean',
				'null' => true,
				'default' => 0,
				'length' => null,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
			'created' => [
				'type' => 'datetime',
				'null' => true,
				'default' => null,
				'length' => null,
				'precision' => null,
				'fixed' => null,
				'comment' => null,
			],
		];
		$this->assertEquals(['id'], $result->primaryKey());
		foreach ($expected as $field => $definition) {
			$this->assertEquals(
				$definition,
				$result->column($field),
				'Field definition does not match for ' . $field
			);
		}
	}

/**
 * Test describing a table with indexes in Mysql
 *
 * @return void
 */
	public function testDescribeTableIndexes() {
		$connection = new Connection(Configure::read('Datasource.test'));
		$this->_createTables($connection);

		$schema = new SchemaCollection($connection);
		$result = $schema->describe('articles');
		$this->assertInstanceOf('Cake\Database\Schema\Table', $result);

		$this->assertCount(2, $result->constraints());
		$expected = [
			'primary' => [
				'type' => 'primary',
				'columns' => ['id'],
				'length' => []
			],
			'length_idx' => [
				'type' => 'unique',
				'columns' => ['title'],
				'length' => [
					'title' => 4,
				]
			]
		];
		$this->assertEquals($expected['primary'], $result->constraint('primary'));
		$this->assertEquals($expected['length_idx'], $result->constraint('length_idx'));

		$this->assertCount(1, $result->indexes());
		$expected = [
			'type' => 'index',
			'columns' => ['author_id'],
			'length' => []
		];
		$this->assertEquals($expected, $result->index('author_idx'));
	}

/**
 * Column provider for creating column sql
 *
 * @return array
 */
	public static function columnSqlProvider() {
		return [
			// strings
			[
				'title',
				['type' => 'string', 'length' => 25, 'null' => false],
				'`title` VARCHAR(25) NOT NULL'
			],
			[
				'title',
				['type' => 'string', 'length' => 25, 'null' => true, 'default' => 'ignored'],
				'`title` VARCHAR(25) DEFAULT NULL'
			],
			[
				'id',
				['type' => 'string', 'length' => 32, 'fixed' => true, 'null' => false],
				'`id` CHAR(32) NOT NULL'
			],
			[
				'role',
				['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'admin'],
				'`role` VARCHAR(10) NOT NULL DEFAULT "admin"'
			],
			[
				'title',
				['type' => 'string'],
				'`title` VARCHAR(255)'
			],
			// Text
			[
				'body',
				['type' => 'text', 'null' => false],
				'`body` TEXT NOT NULL'
			],
			// Integers
			[
				'post_id',
				['type' => 'integer', 'length' => 11],
				'`post_id` INTEGER(11)'
			],
			[
				'post_id',
				['type' => 'biginteger', 'length' => 20],
				'`post_id` BIGINT'
			],
			// Decimal
			[
				'value',
				['type' => 'decimal'],
				'`value` DECIMAL'
			],
			[
				'value',
				['type' => 'decimal', 'length' => 11],
				'`value` DECIMAL(11,0)'
			],
			[
				'value',
				['type' => 'decimal', 'length' => 12, 'precision' => 5],
				'`value` DECIMAL(12,5)'
			],
			// Float
			[
				'value',
				['type' => 'float'],
				'`value` FLOAT'
			],
			[
				'value',
				['type' => 'float', 'length' => 11, 'precision' => 3],
				'`value` FLOAT(11,3)'
			],
			// Boolean
			[
				'checked',
				['type' => 'boolean', 'default' => false],
				'`checked` BOOLEAN DEFAULT FALSE'
			],
			[
				'checked',
				['type' => 'boolean', 'default' => true, 'null' => false],
				'`checked` BOOLEAN NOT NULL DEFAULT TRUE'
			],
			// datetimes
			[
				'created',
				['type' => 'datetime', 'comment' => 'Created timestamp'],
				'`created` DATETIME COMMENT "Created timestamp"'
			],
			// Date & Time
			[
				'start_date',
				['type' => 'date'],
				'`start_date` DATE'
			],
			[
				'start_time',
				['type' => 'time'],
				'`start_time` TIME'
			],
			// timestamps
			[
				'created',
				['type' => 'timestamp', 'null' => true],
				'`created` TIMESTAMP NULL'
			],
			[
				'created',
				['type' => 'timestamp', 'null' => false, 'default' => 'current_timestamp'],
				'`created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
			],
		];
	}

/**
 * Test generating column definitions
 *
 * @dataProvider columnSqlProvider
 * @return void
 */
	public function testColumnSql($name, $data, $expected) {
		$driver = $this->_getMockedDriver();
		$schema = new MysqlSchema($driver);

		$table = (new Table('articles'))->addColumn($name, $data);
		$this->assertEquals($expected, $schema->columnSql($table, $name));
	}

/**
 * Provide data for testing constraintSql
 *
 * @return array
 */
	public static function constraintSqlProvider() {
		return [
			[
				'primary',
				['type' => 'primary', 'columns' => ['title']],
				'PRIMARY KEY (`title`)'
			],
			[
				'unique_idx',
				['type' => 'unique', 'columns' => ['title', 'author_id']],
				'UNIQUE KEY `unique_idx` (`title`, `author_id`)'
			],
			[
				'length_idx',
				[
					'type' => 'unique',
					'columns' => ['author_id', 'title'],
					'length' => ['author_id' => 5, 'title' => 4]
				],
				'UNIQUE KEY `length_idx` (`author_id`(5), `title`(4))'
			],
		];
	}

/**
 * Test the constraintSql method.
 *
 * @dataProvider constraintSqlProvider
 */
	public function testConstraintSql($name, $data, $expected) {
		$driver = $this->_getMockedDriver();
		$schema = new MysqlSchema($driver);

		$table = (new Table('articles'))->addColumn('title', [
			'type' => 'string',
			'length' => 255
		])->addColumn('author_id', [
			'type' => 'integer',
		])->addConstraint($name, $data);

		$this->assertEquals($expected, $schema->constraintSql($table, $name));
	}

/**
 * Test provider for indexSql()
 *
 * @return array
 */
	public static function indexSqlProvider() {
		return [
			[
				'key_key',
				['type' => 'index', 'columns' => ['author_id']],
				'KEY `key_key` (`author_id`)'
			],
			[
				'full_text',
				['type' => 'fulltext', 'columns' => ['title']],
				'FULLTEXT KEY `full_text` (`title`)'
			],
		];
	}

/**
 * Test the indexSql method.
 *
 * @dataProvider indexSqlProvider
 */
	public function testIndexSql($name, $data, $expected) {
		$driver = $this->_getMockedDriver();
		$schema = new MysqlSchema($driver);

		$table = (new Table('articles'))->addColumn('title', [
			'type' => 'string',
			'length' => 255
		])->addColumn('author_id', [
			'type' => 'integer',
		])->addIndex($name, $data);

		$this->assertEquals($expected, $schema->indexSql($table, $name));
	}

/**
 * Test generating a column that is a primary key.
 *
 * @return void
 */
	public function testColumnSqlPrimaryKey() {
		$driver = $this->_getMockedDriver();
		$schema = new MysqlSchema($driver);

		$table = new Table('articles');
		$table->addColumn('id', [
				'type' => 'integer',
				'null' => false
			])
			->addConstraint('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);
		$result = $schema->columnSql($table, 'id');
		$this->assertEquals($result, '`id` INTEGER NOT NULL AUTO_INCREMENT');

		$table = new Table('articles');
		$table->addColumn('id', [
				'type' => 'biginteger',
				'null' => false
			])
			->addConstraint('primary', [
				'type' => 'primary',
				'columns' => ['id']
			]);
		$result = $schema->columnSql($table, 'id');
		$this->assertEquals($result, '`id` BIGINT NOT NULL AUTO_INCREMENT');
	}

/**
 * Integration test for converting a Schema\Table into MySQL table creates.
 *
 * @return void
 */
	public function testCreateSql() {
		$driver = $this->_getMockedDriver();
		$connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$connection->expects($this->any())->method('driver')
			->will($this->returnValue($driver));

		$table = (new Table('posts'))->addColumn('id', [
				'type' => 'integer',
				'null' => false
			])
			->addColumn('title', [
				'type' => 'string',
				'null' => false,
				'comment' => 'The title'
			])
			->addColumn('body', ['type' => 'text'])
			->addColumn('created', 'datetime')
			->addConstraint('primary', [
				'type' => 'primary',
				'columns' => ['id']
			])
			->options([
				'engine' => 'InnoDB',
				'charset' => 'utf8',
				'collate' => 'utf8_general_ci',
			]);

		$expected = <<<SQL
CREATE TABLE `posts` (
`id` INTEGER NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL COMMENT "The title",
`body` TEXT,
`created` DATETIME,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
SQL;
		$result = $table->createSql($connection);
		$this->assertCount(1, $result);
		$this->assertEquals($expected, $result[0]);
	}

/**
 * test dropSql
 *
 * @return void
 */
	public function testDropSql() {
		$driver = $this->_getMockedDriver();
		$connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$connection->expects($this->any())->method('driver')
			->will($this->returnValue($driver));

		$table = new Table('articles');
		$result = $table->dropSql($connection);
		$this->assertCount(1, $result);
		$this->assertEquals('DROP TABLE `articles`', $result[0]);
	}

/**
 * Test truncateSql()
 *
 * @return void
 */
	public function testTruncateSql() {
		$driver = $this->_getMockedDriver();
		$connection = $this->getMock('Cake\Database\Connection', [], [], '', false);
		$connection->expects($this->any())->method('driver')
			->will($this->returnValue($driver));

		$table = new Table('articles');
		$result = $table->truncateSql($connection);
		$this->assertCount(1, $result);
		$this->assertEquals('TRUNCATE TABLE `articles`', $result[0]);
	}

/**
 * Get a schema instance with a mocked driver/pdo instances
 *
 * @return MysqlSchema
 */
	protected function _getMockedDriver() {
		$driver = new \Cake\Database\Driver\Mysql();
		$mock = $this->getMock('FakePdo', ['quote', 'quoteIdentifier']);
		$mock->expects($this->any())
			->method('quote')
			->will($this->returnCallback(function ($value) {
				return '"' . $value . '"';
			}));
		$mock->expects($this->any())
			->method('quoteIdentifier')
			->will($this->returnCallback(function ($value) {
				return '`' . $value . '`';
			}));
		$driver->connection($mock);
		return $driver;
	}

}