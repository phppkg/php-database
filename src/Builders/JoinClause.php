<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/25
 * Time: 下午11:38
 */

namespace Inhere\Database\Builders;

use Closure;

/**
 * Class JoinClause
 * @package Inhere\Database\Builders
 */
class JoinClause extends QueryBuilder
{
    /**
     * The type of join being performed.
     * @var string
     */
    public $type;

    /**
     * The table the join clause is joining to.
     * @var string
     */
    public $table;

    /**
     * The parent query builder instance.
     * @var QueryBuilder
     */
    private $parentQuery;

    /**
     * Create a new join clause instance.
     * @param  QueryBuilder $parentQuery
     * @param  string $type
     * @param  string $table
     * @return void
     */
    public function __construct(QueryBuilder $parentQuery, $type, $table)
    {
        $this->type = $type;
        $this->table = $table;
        $this->parentQuery = $parentQuery;

        parent::__construct($parentQuery->getConnection(), $parentQuery->getCompiler());
    }

    /**
     * Add an "on" clause to the join.
     * On clauses can be chained, e.g.
     *  $join->on('contacts.user_id', '=', 'users.id')
     *       ->on('contacts.info_id', '=', 'info.id')
     * will produce the following SQL:
     * on `contacts`.`user_id` = `users`.`id`  and `contacts`.`info_id` = `info`.`id`
     * @param  \Closure|string $first
     * @param  string|null $operator
     * @param  string|null $second
     * @param  string $boolean
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function on($first, $operator = null, $second = null, $boolean = 'and')
    {
        if ($first instanceof Closure) {
            return $this->whereNested($first, $boolean);
        }

        return $this->whereColumn($first, $operator, $second, $boolean);
    }

    /**
     * Add an "or on" clause to the join.
     * @param  \Closure|string $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return JoinClause
     */
    public function orOn($first, $operator = null, $second = null)
    {
        return $this->on($first, $operator, $second, 'or');
    }

    /**
     * Get a new instance of the join clause builder.
     * @return JoinClause
     */
    public function newQuery()
    {
        return new static($this->parentQuery, $this->type, $this->table);
    }

    /**
     * Create a new query instance for sub-query.
     * @return QueryBuilder
     */
    protected function forSubQuery()
    {
        return $this->parentQuery->newQuery();
    }
}

