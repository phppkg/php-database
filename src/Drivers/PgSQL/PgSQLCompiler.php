<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-30
 * Time: 15:47
 */

namespace Inhere\Database\Drivers\PgSQL;

use Inhere\Database\Builders\QueryCompiler;

/**
 * Class PgSQLCompiler
 * @package Inhere\Database\Drivers\PgSQL
 */
class PgSQLCompiler extends QueryCompiler
{
    /**
     * All of the available clause operators.
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '|', '#', '<<', '>>', '>>=', '=<<',
        '@>', '<@', '?', '?|', '?&', '||', '-', '-', '#-',
    ];
}