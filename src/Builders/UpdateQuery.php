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
 * Class UpdateQuery
 * @package Inhere\Database\Builders
 */
class UpdateQuery extends QueryBuilder
{
    use WhereClauseTrait, LimitClauseTrait;
}