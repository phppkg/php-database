<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 11:01
 */

namespace Inhere\Database\Builders;

use Inhere\Database\Builders\Traits\AggregateClauseTrait;
use Inhere\Database\Builders\Traits\JoinClauseTrait;
use Inhere\Database\Builders\Traits\LimitClauseTrait;
use Inhere\Database\Builders\Traits\UnionClauseTrait;
use Inhere\Database\Builders\Traits\WhereClauseTrait;
use Inhere\Library\Helpers\Arr;

/**
 * Class SelectQuery
 * @package Inhere\Database\Builders
 */
class SelectQuery extends QueryBuilder
{
    use AggregateClauseTrait, JoinClauseTrait, WhereClauseTrait, UnionClauseTrait, LimitClauseTrait;

    /**
     * Indicates if the query returns distinct results.
     * @var bool
     */
    public $distinct = false;

    /**
     * The groupings for the query.
     * @var array
     */
    public $groups = [];

    /**
     * The having constraints for the query.
     * @var array
     */
    public $havings = [];

    /**
     * The orderings for the query.
     * @var array
     */
    public $orders = [];


    public static function make()
    {

    }

    /**
     * Force the query to only return distinct results.
     * @param bool $value
     * @return $this
     */
    public function distinct($value = true)
    {
        $this->distinct = (bool)$value;

        return $this;
    }

    /**
     * Add a "group by" clause to the query.
     * @param  array ...$groups
     * @return $this
     */
    public function groupBy(...$groups)
    {
        foreach ($groups as $group) {
            $this->groups = array_merge((array)$this->groups, Arr::wrap($group));
        }

        return $this;
    }

    public function having($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'Basic';

        $this->havings[] = [$type, $column, $operator, $value, $boolean];

//        if (! $value instanceof Expression) {
//            $this->addBinding($value, 'having');
//        }

        return $this;
    }

    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'or');
    }

    /**
     * Add a raw having clause to the query.
     * @param  string $sql
     * @param  array $bindings
     * @param  string $boolean
     * @return $this
     */
    public function havingRaw($sql, array $bindings = [], $boolean = 'and')
    {
        $type = 'Raw';
        $this->havings[] = [$type, $sql, $boolean];

        $this->addBinding($bindings, 'having');

        return $this;
    }

    /**
     * Add a raw or having clause to the query.
     * @param  string $sql
     * @param  array $bindings
     * @return $this
     */
    public function orHavingRaw($sql, array $bindings = [])
    {
        return $this->havingRaw($sql, $bindings, 'or');
    }

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
     * Execute a query for a single record by ID.
     * @param  int $id
     * @param  array $columns
     * @param string $pkField
     * @return mixed|static
     */
    public function find($id, array $columns = ['*'], $pkField = 'id')
    {
        return $this->where($pkField, '=', $id)->first($columns);
    }

    /**
     * Determine if any rows exist for the current query.
     * @return bool
     */
    public function exists()
    {
        $results = $this->connection->select(
            $this->compiler->compileExists($this), $this->getBindings(), !$this->useWriter
        );

        // If the results has rows, we will get the row and see if the exists column is a
        // boolean true. If there is no results for this query we will return false as
        // there are no rows for this query at all and we can return that info here.
        if (isset($results[0])) {
            $results = (array)$results[0];

            return (bool)$results['exists'];
        }

        return false;
    }
}