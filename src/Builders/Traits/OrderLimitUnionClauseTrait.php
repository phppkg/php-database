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
trait OrderLimitUnionClauseTrait
{
    /**
     * The orderings for the query.
     * @var array
     */
    public $orders = [];

    /**
     * The maximum number of records to return.
     * @var int
     */
    public $limit;

    /**
     * The number of records to skip.
     * @var int
     */
    public $offset;

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
     * Add an "order by" clause to the query.
     * @param  string $column
     * @param  string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $info = [
            'column' => $column,
            'direction' => strtolower($direction) === 'asc' ? 'asc' : 'desc',
        ];

        if ($this->unions) {
            $this->unionOrders[] = $info;
        } else {
            $this->orders[] = $info;
        }

        return $this;
    }

    /**
     * Add a descending "order by" clause to the query.
     * @param  string $column
     * @return $this
     */
    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'desc');
    }

    /**
     * Add a raw "order by" clause to the query.
     * @param  string $sql
     * @param  array $bindings
     * @return $this
     */
    public function orderByRaw($sql, $bindings = null)
    {
        $type = 'Raw';
        $info = [$type, $sql];

        if ($this->unions) {
            $this->unionOrders[] = $info;
        } else {
            $this->orders[] = $info;
        }

        $this->addBinding($bindings, 'order');

        return $this;
    }

    /**
     * Set the "offset" value of the query.
     * @param  int $value
     * @return $this
     */
    public function offset($value)
    {
        $property = $this->unions ? 'unionOffset' : 'offset';
        $this->$property = max(0, (int)$value);

        return $this;
    }

    public function limit($limit, $offset = null)
    {
        $property = $this->unions ? 'unionLimit' : 'limit';

        if ($limit >= 0) {
            $this->$property = $limit;
        }

        if (null !== $offset) {
            $this->offset($offset);
        }

        return $this;
    }

    /**
     * @param int $page
     * @param int $pageSize
     * @return $this
     */
    public function forPage($page, $pageSize = 15)
    {
        return $this->offset(($page - 1) * $pageSize)->limit($pageSize);
    }

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