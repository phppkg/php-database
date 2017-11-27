<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 13:55
 */

namespace Inhere\Database\Drivers\PgSQL;

use Inhere\Database\Builders\QueryCompiler;
use Inhere\Database\Base\PDOConnection;

/**
 * Class PgSQLConnection - The Postgres db connection
 * @package Inhere\Database\Drivers\PgSQL
 */
class PgSQLConnection extends PDOConnection
{
    /**
     * Get the default query grammar instance.
     * @return QueryCompiler
     */
    protected function getDefaultQueryCompiler()
    {
        return $this->withTablePrefix(new PgSQLCompiler());
    }

    /**
     * Is this driver supported.
     * @return  boolean
     */
    public static function isSupported()
    {
        return \in_array('pgsql', \PDO::getAvailableDrivers(), true);
    }
}