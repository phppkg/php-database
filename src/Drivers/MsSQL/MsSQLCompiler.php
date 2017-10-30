<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-30
 * Time: 15:45
 */

namespace Inhere\Database\Drivers\MsSQL;

use Inhere\Database\Builders\QueryCompiler;

/**
 * Class MsSQLCompiler
 * @package Inhere\Database\Drivers\MsSQL
 */
class MsSQLCompiler extends QueryCompiler
{
    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '!<', '!>', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '&=', '|', '|=', '^', '^=',
    ];

}