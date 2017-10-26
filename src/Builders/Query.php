<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 11:01
 */

namespace Inhere\Database\Builders;

/**
 * Class Query
 * @package Inhere\Database\Builders
 */
class Query
{
    /**
     * @return SelectQuery
     */
    public static function select(): SelectQuery
    {
        return new SelectQuery();
    }

    /**
     * @return InsertQuery
     */
    public static function insert(): InsertQuery
    {
        return new InsertQuery();
    }

    /**
     * @return UpdateQuery
     */
    public static function update(): UpdateQuery
    {
        return new UpdateQuery();
    }

    /**
     * @return DeleteQuery
     */
    public static function delete(): DeleteQuery
    {
        return new DeleteQuery();
    }
}