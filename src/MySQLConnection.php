<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-23
 * Time: 17:07
 */

namespace Inhere\Database\Connections\Pdo;

use PDO;

/**
 * Class MysqlConnection
 * @package Inhere\Database\Connections
 */
class MySQLConnection extends PdoConnection
{
    /**
     * The default PDO connection options.
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function hasTable(string $name): bool
    {
        $query = 'SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = ? AND `table_name` = ?';

        return (bool)$this->query($query, [$this->getConfig('database'), $name])->fetchColumn();
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
}
