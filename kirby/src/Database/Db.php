<?php

namespace Kirby\Database;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\Config;

/**
 * Database shortcuts
 *
 * @package   Kirby Database
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier
 * @license   https://opensource.org/licenses/MIT
 */
class Db
{
	/**
	 * Query shortcuts
	 */
	public static array $queries = [];

	/**
	 * The singleton Database object
	 */
	public static Database|null $connection = null;

	/**
	 * (Re)connect the database
	 *
	 * @param array|null $params Pass `[]` to use the default params from the config,
	 *                           don't pass any argument to get the current connection
	 */
	public static function connect(array|null $params = null): Database
	{
		if ($params === null && static::$connection !== null) {
			return static::$connection;
		}

		// try to connect with the default
		// connection settings if no params are set
		$params ??= [
			'type'     => Config::get('db.type', 'mysql'),
			'host'     => Config::get('db.host', 'localhost'),
			'user'     => Config::get('db.user', 'root'),
			'password' => Config::get('db.password', ''),
			'database' => Config::get('db.database', ''),
			'prefix'   => Config::get('db.prefix', ''),
			'port'     => Config::get('db.port', ''),
			'charset'  => Config::get('db.charset')
		];

		return static::$connection = new Database($params);
	}

	/**
	 * Returns the current database connection
	 */
	public static function connection(): Database|null
	{
		return static::$connection;
	}

	/**
	 * Sets the current table which should be queried. Returns a
	 * Query object, which can be used to build a full query for
	 * that table.
	 */
	public static function table(string $table): Query
	{
		$db = static::connect();
		return $db->table($table);
	}

	/**
	 * Executes a raw SQL query which expects a set of results
	 */
	public static function query(string $query, array $bindings = [], array $params = [])
	{
		$db = static::connect();
		return $db->query($query, $bindings, $params);
	}

	/**
	 * Executes a raw SQL query which expects
	 * no set of results (i.e. update, insert, delete)
	 */
	public static function execute(string $query, array $bindings = []): bool
	{
		$db = static::connect();
		return $db->execute($query, $bindings);
	}

	/**
	 * Magic calls for other static Db methods are
	 * redirected to either a predefined query or
	 * the respective method of the Database object
	 *
	 * @throws \Kirby\Exception\InvalidArgumentException
	 */
	public static function __callStatic(string $method, $arguments)
	{
		if (isset(static::$queries[$method])) {
			return (static::$queries[$method])(...$arguments);
		}

		if (
			static::$connection !== null &&
			method_exists(static::$connection, $method) === true
		) {
			return call_user_func_array([static::$connection, $method], $arguments);
		}

		throw new InvalidArgumentException(
			message: 'Invalid static Db method: ' . $method
		);
	}
}

// @codeCoverageIgnoreStart

/**
 * Shortcut for SELECT clauses
 *
 * @param string $table The name of the table which should be queried
 * @param mixed $columns Either a string with columns or an array of column names
 * @param mixed $where The WHERE clause; can be a string or an array
 */
Db::$queries['select'] = function (
	string $table,
	$columns = '*',
	$where = null,
	string|null $order = null,
	int $offset = 0,
	int|null $limit = null
) {
	return Db::table($table)
		->select($columns)
		->where($where)
		->order($order)
		->offset($offset)
		->limit($limit)
		->all();
};

/**
 * Shortcut for selecting a single row in a table
 *
 * @param string $table The name of the table which should be queried
 * @param mixed $columns Either a string with columns or an array of column names
 * @param mixed $where The WHERE clause; can be a string or an array
 */
Db::$queries['first'] = Db::$queries['row'] = Db::$queries['one'] = function (
	string $table,
	$columns = '*',
	$where = null,
	string|null $order = null
) {
	return Db::table($table)
		->select($columns)
		->where($where)
		->order($order)
		->first();
};

/**
 * Returns only values from a single column
 *
 * @param string $table The name of the table which should be queried
 * @param string $column The name of the column to select from
 * @param mixed $where The WHERE clause; can be a string or an array
 */
Db::$queries['column'] = function (
	string $table,
	string $column,
	$where = null,
	string|null $order = null,
	int $offset = 0,
	int|null $limit = null
) {
	return Db::table($table)
		->where($where)
		->order($order)
		->offset($offset)
		->limit($limit)
		->column($column);
};

/**
 * Shortcut for inserting a new row into a table
 *
 * @param string $table The name of the table which should be queried
 * @param array $values An array of values which should be inserted
 * @return mixed Returns the last inserted id on success or false
 */
Db::$queries['insert'] = function (string $table, array $values): mixed {
	return Db::table($table)->insert($values);
};

/**
 * Shortcut for updating a row in a table
 *
 * @param string $table The name of the table which should be queried
 * @param array $values An array of values which should be inserted
 * @param mixed $where An optional WHERE clause
 */
Db::$queries['update'] = function (
	string $table,
	array $values,
	$where = null
): bool {
	return Db::table($table)->where($where)->update($values);
};

/**
 * Shortcut for deleting rows in a table
 *
 * @param string $table The name of the table which should be queried
 * @param mixed $where An optional WHERE clause
 */
Db::$queries['delete'] = function (string $table, $where = null): bool {
	return Db::table($table)->where($where)->delete();
};

/**
 * Shortcut for counting rows in a table
 *
 * @param string $table The name of the table which should be queried
 * @param mixed $where An optional WHERE clause
 */
Db::$queries['count'] = function (string $table, mixed $where = null): int {
	return Db::table($table)->where($where)->count();
};

/**
 * Shortcut for calculating the minimum value in a column
 *
 * @param string $table The name of the table which should be queried
 * @param string $column The name of the column of which the minimum should be calculated
 * @param mixed $where An optional WHERE clause
 */
Db::$queries['min'] = function (
	string $table,
	string $column,
	$where = null
): float {
	return Db::table($table)->where($where)->min($column);
};

/**
 * Shortcut for calculating the maximum value in a column
 *
 * @param string $table The name of the table which should be queried
 * @param string $column The name of the column of which the maximum should be calculated
 * @param mixed $where An optional WHERE clause
 */
Db::$queries['max'] = function (
	string $table,
	string $column,
	$where = null
): float {
	return Db::table($table)->where($where)->max($column);
};

/**
 * Shortcut for calculating the average value in a column
 *
 * @param string $table The name of the table which should be queried
 * @param string $column The name of the column of which the average should be calculated
 * @param mixed $where An optional WHERE clause
 */
Db::$queries['avg'] = function (
	string $table,
	string $column,
	$where = null
): float {
	return Db::table($table)->where($where)->avg($column);
};

/**
 * Shortcut for calculating the sum of all values in a column
 *
 * @param string $table The name of the table which should be queried
 * @param string $column The name of the column of which the sum should be calculated
 * @param mixed $where An optional WHERE clause
 */
Db::$queries['sum'] = function (
	string $table,
	string $column,
	$where = null
): float {
	return Db::table($table)->where($where)->sum($column);
};

// @codeCoverageIgnoreEnd
