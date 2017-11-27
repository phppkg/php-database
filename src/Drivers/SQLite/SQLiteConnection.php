<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/23
 * Time: 下午7:44
 */

namespace Inhere\Database\Drivers\SQLite;

use Inhere\Database\Builders\QueryCompiler;
use Inhere\Database\Base\PDOConnection;

/**
 * Class SqliteConnection - Sqlite Connection
 * @package Inhere\Database\Drivers\SQLite
 */
class SQLiteConnection extends PDOConnection
{
    /**
     * Get the default query grammar instance.
     * @return QueryCompiler
     */
    protected function getDefaultQueryCompiler()
    {
        return $this->withTablePrefix(new SQLiteCompiler());
    }

    /**
     * Is this driver supported.
     * @return  boolean
     */
    public static function isSupported()
    {
        return \in_array('sqlite', \PDO::getAvailableDrivers(), true);
    }
}
