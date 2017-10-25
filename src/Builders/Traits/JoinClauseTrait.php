<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 10:16
 */

namespace Inhere\Database\Builders\Traits;

/**
 * trait JoinClauseTrait
 * @package Inhere\Database\Builders\Traits
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

    /**
     * @see JoinClauseTrait::join()
     * {@inheritdoc}
     */
    public function leftJoin($table, $conditions = null)
    {
        return $this->join($table, $conditions);
    }

    /**
     * @see JoinClauseTrait::join()
     * {@inheritdoc}
     */
    public function rightJoin($table, $conditions = null)
    {
        return $this->join($table, $conditions, 'right');
    }

    /**
     * @see JoinClauseTrait::join()
     * {@inheritdoc}
     */
    public function innerJoin($table, $conditions = null)
    {
        return $this->join($table, $conditions, 'inner');
    }

    /**
     * @see JoinClauseTrait::join()
     * {@inheritdoc}
     */
    public function outerJoin($table, $conditions = null)
    {
        return $this->join($table, $conditions, 'outer');
    }

    /**
     * @see JoinClauseTrait::join()
     * {@inheritdoc}
     */
    public function crossJoin($table, $conditions = null)
    {
        return $this->join($table, $conditions, 'cross');
    }

}