<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 17:43
 */

namespace Inhere\Database\Builders\Compilers;

use Inhere\Database\Builders\QueryCompiler;

/**
 * Class MySQLCompiler
 * @package Inhere\Database\Builders\Compilers
 */
class MySQLCompiler extends QueryCompiler
{
    /**
     * The components that make up a select clause.
     * @var array
     */
    protected static $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    ];

    /**
     * If no connection set, we escape it with default function.
     * Since mysql_real_escape_string() has been deprecated, we use an alternative one.
     * Please see: http://stackoverflow.com/questions/4892882/mysql-real-escape-string-for-multibyte-without-a-connection
     * @param string $text
     * @return  string
     */
    protected function escapeWithNoConnection($text)
    {
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $text
        );
    }
}