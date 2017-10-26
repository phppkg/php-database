<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 11:45
 */

namespace Inhere\Database;

use Inhere\Database\Connections\Connection;

/**
 * Class Database
 * @package Inhere\Database
 */
class Database
{
    /** @var string */
    private $name;

    /** @var string Table prefix of the database. */
    private $prefix;

    /** @var  Connection */
    private $connection;

    /**
     * Database constructor.
     * @param string $name
     * @param Connection $connection
     * @param string $prefix
     */
    public function __construct(string $name, Connection $connection, string $prefix = '')
    {
        $this->name = $name;
        $this->prefix = $prefix;
        $this->connection = $connection;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}