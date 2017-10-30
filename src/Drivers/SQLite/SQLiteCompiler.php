<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-30
 * Time: 15:44
 */

namespace Inhere\Database\Drivers\SQLite;


use Inhere\Database\Builders\QueryCompiler;

/**
 * Class SQLiteCompiler
 * @package Inhere\Database\Drivers\SQLite
 */
class SQLiteCompiler extends QueryCompiler
{
    const SELECT_COMPONENTS = [
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
     * All of the available clause operators.
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '|', '<<', '>>',
    ];

}