<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 10:16
 */

namespace SimpleAR\Builders\Traits;

/**
 * trait JoinClauseTrait
 * @package SimpleAR\Builders\Traits
 */
trait JoinClauseTrait
{

    /**
     * The table joins for the query.
     * @var array
     */
    public $joins = [];


    /**
     * @param string $type 'INNER'
     * @param string $table
     * @param array|string $conditions
     * @return $this
     */
    public function join($table, $conditions = null, $type = 'left')
    {
        if (is_string($table)) {
            $table .= $conditions ? ' ON ' . implode(' AND ', (array)$conditions) : '';
        }

        $this->joins[] = [strtoupper($type) . ' JOIN', $table];

        return $this;
    }

    public function leftJoin($table, $conditions = null)
    {
        return $this->join($table, $conditions);
    }

    public function rightJoin($table, $conditions = null)
    {
        return $this->join($table, $conditions, 'right');
    }

    public function crossJoin($table, $conditions = null)
    {
        return $this->join($table, $conditions, 'cross');
    }

}