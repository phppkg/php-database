<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 10:45
 */

namespace Inhere\Database\Builders;

use Inhere\Database\Builders\Traits\LimitClauseTrait;
use Inhere\Database\Builders\Traits\WhereClauseTrait;

/**
 * Class DeleteQuery
 * @package Inhere\Database\Builders
 */
class DeleteQuery extends QueryBuilder
{
    use WhereClauseTrait, LimitClauseTrait;

    /**
     * {@inheritdoc}
     */
    public function toSql(): string
    {
        return $this->compiler->compileDelete($this);
    }

}