<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/23
 * Time: 下午7:44
 */

namespace Inhere\Database\Connections;

/**
 * Class SqliteConnection - Sqlite Connection
 * @package Inhere\Database\Connections
 */
class SQLiteConnection extends MySQLConnection
{
    /**
     * Is this driver supported.
     * @return  boolean
     */
    public static function isSupported()
    {
        return in_array('sqlite', \PDO::getAvailableDrivers(), true);
    }
}
