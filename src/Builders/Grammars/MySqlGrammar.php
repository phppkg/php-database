<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 下午11:32
 */

namespace SimpleAR\Builders\Grammars;

/**
 * Class MySqlGrammar
 * @package SimpleAR\Builders\Grammars
 */
class MySqlGrammar extends DefaultGrammar
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
}
