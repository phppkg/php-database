<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-23
 * Time: 17:07
 */

namespace Inhere\Database\Drivers\MySQL;

use Inhere\Database\Builders\QueryCompiler;
use Inhere\Database\Base\PDOConnection;
use PDO;

/**
 * Class MysqlConnection
 * @package Inhere\Database\Drivers\MySQL
 */
class MySQLConnection extends PDOConnection
{
    /**
     * The default PDO connection options.
     * @var array
     */
    protected static $pdoOptions = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * Get the default query grammar instance.
     * @return QueryCompiler
     */
    protected function getDefaultQueryCompiler()
    {
        return $this->withTablePrefix(new MySQLCompiler());
    }

    /**
     * Bind values to their parameters in the given statement.
     * @param  \PDOStatement $statement
     * @param  array $bindings
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                \is_string($key) ? $key : $key + 1, $value,
                \is_int($value) || \is_float($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = 'SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = ? AND `table_name` = ?';

        return (bool)$this->query($query, [$this->getOptions('database'), $name])->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function tableNames(): array
    {
        $result = [];

        foreach ($this->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $row) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * Is this driver supported.
     * @return  boolean
     */
    public static function isSupported()
    {
        return \in_array('mysql', \PDO::getAvailableDrivers(), true);
    }
}
