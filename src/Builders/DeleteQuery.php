<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 10:45
 */

namespace Inhere\Database\Builders;

/**
 * Class DeleteQuery
 * @package Inhere\Database\Builders
 */
class DeleteQuery extends QueryBuilder
{
    /**
     * {@inheritdoc}
     */
    public function toSql(): string
    {
        return $this->compiler->compileDelete($this);
    }

}