<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 13:57
 */

namespace Inhere\Database\Drivers\MsSQL;

use Inhere\Database\Builders\QueryCompiler;
use Inhere\Database\PDOConnection;

/**
 * Class MsSQLConnection - The Microsoft SQL Server Connection
 * @package Inhere\Database\Drivers\MsSQL
 */
class MsSQLConnection extends PDOConnection
{

    /**
     * Get the default query grammar instance.
     * @return QueryCompiler
     */
    protected function getDefaultQueryCompiler()
    {
        return $this->withTablePrefix(new MsSQLCompiler());
    }

    /**
     * Is this driver supported.
     * @return  boolean
     */
    public static function isSupported()
    {
        return in_array('mssql', \PDO::getAvailableDrivers(), true);
    }
}