<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 下午11:32
 */

namespace SimpleAR\Query\Grammars;

/**
 * Class MySqlGrammar
 * @package SimpleAR\Query\Grammars
 */
class MySqlGrammar extends DefaultGrammar
{
    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
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
