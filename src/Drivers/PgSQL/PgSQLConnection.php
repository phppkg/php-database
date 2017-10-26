<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 13:55
 */

namespace Inhere\Database\Connections;

/**
 * Class PgSQLConnection - The Postgres db connection
 * @package Inhere\Database\Connections
 */
class PgSQLConnection extends PDOConnection
{
    /**
     * Is this driver supported.
     * @return  boolean
     */
    public static function isSupported()
    {
        return in_array('pgsql', \PDO::getAvailableDrivers(), true);
    }
}