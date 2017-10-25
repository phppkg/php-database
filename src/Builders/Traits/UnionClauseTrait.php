<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 13:13
 */

namespace Inhere\Database\Builders\Traits;

use Closure;

/**
 * Trait UnionClauseTrait
 * @package Inhere\Database\Builders\Traits
 */
trait UnionClauseTrait
{
    /**
     * The query union statements.
     * @var array
     */
    public $unions = [];

    /**
     * The maximum number of union records to return.
     * @var int
     */
    public $unionLimit;

    /**
     * The number of union records to skip.
     * @var int
     */
    public $unionOffset;

    /**
     * The orderings for the union query.
     * @var array
     */
    public $unionOrders = [];

    /**
     * Add a union statement to the query.
     * @param  static|\Closure|self $query
     * @param  bool $all
     * @return $this
     */
    public function union($query, $all = false)
    {
        if ($query instanceof Closure) {
            $query($query = $this->newQuery());
        }

        $this->unions[] = [$query, $all];

        $this->addBinding($query->getBindings(), 'union');

        return $this;
    }

    public function unionAll($query)
    {
        return $this->union($query, true);
    }
}