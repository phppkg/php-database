<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 11:17
 */

namespace Inhere\Database\Builders\Traits;

/**
 * Trait LimitClauseTrait
 * @package Inhere\Database\Builders\Traits
 */
trait LimitClauseTrait
{
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

}