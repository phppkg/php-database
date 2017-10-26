<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 13:57
 */

namespace Inhere\Database\Drivers\MySQL;

use Inhere\Database\Connections\PDOConnection;

/**
 * Class MsSQLConnection - The Microsoft SQL Server Connection
 * @package Inhere\Database\Drivers\MySQL
 */
class MsSQLConnection extends PDOConnection
{
    /**
     * Is this driver supported.
     * @return  boolean
     */
    public static function isSupported()
    {
        return in_array('mssql', \PDO::getAvailableDrivers(), true);
    }
}