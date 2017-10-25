<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 12:01
 */

namespace Inhere\Database\Builders\Traits;

use Inhere\Library\Helpers\Arr;

/**
 * Trait AggregateClauseTrait
 *  - 在数据库上执行聚合函数的相关语句
 * @package Inhere\Database\Builders\Traits
 */
trait AggregateClauseTrait
{

    /**
     * Retrieve the "count" result of the query.
     * @param  string $columns
     * @return int
     */
    public function count($columns = '*')
    {
        return (int)$this->aggregate(__FUNCTION__, Arr::wrap($columns));
    }

    /**
     * Retrieve the minimum value of a given column.
     * @param  string $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     * @param  string $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
     * @param  string $column
     * @return mixed
     */
    public function sum($column)
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    /**
     * Retrieve the average of the values of a given column.
     * @param  string $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Alias for the "avg" method.
     * @param  string $column
     * @return mixed
     */
    public function average($column)
    {
        return $this->avg($column);
    }

    /**
     * Execute an aggregate function on the database. 在数据库上执行聚合函数
     * @param  string $function
     * @param  array $columns
     * @return mixed
     */
    public function aggregate($function, array $columns = ['*'])
    {
        $results = $this->cloneWithout(['columns'])
            ->cloneWithoutBindings(['select'])
            ->setAggregate($function, $columns)
            ->get($columns);

        if (!$results->isEmpty()) {
            return array_change_key_case((array)$results[0])['aggregate'];
        }
    }
}