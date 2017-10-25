<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 10:16
 */

namespace SimpleAR\Builders\Traits;

use Inhere\Library\Helpers\Str;

/**
 * trait WhereClauseTrait
 * @package SimpleAR\Builders\Traits
 */
trait WhereClauseTrait
{
    /**
     * The where constraints for the query.
     * @var array
     */
    public $wheres = [];

    /********************************************************************************
     * -- where nodes
     *******************************************************************************/

    /**
     * Add a basic where clause to the query.
     * @param  string|array|\Closure $column
     * @param  string|null $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $type = 'Basic';
        $this->wheres[] = [$type, $column, $operator, $value, $boolean];

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Add a raw where clause to the query.
     * @param  string $sql
     * @param  mixed $bindings
     * @param  string $boolean
     * @return $this
     */
    public function whereRaw($sql, $bindings = null, $boolean = 'and')
    {
        $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean];

        $this->addBinding((array)$bindings, 'where');

        return $this;
    }

    public function orWhereRaw($sql, $bindings = null)
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    /**
     * Add a "where in" clause to the query.
     * @param  string $column
     * @param  mixed $values
     * @param  string $boolean
     * @param  bool $not
     * @return $this
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotIn' : 'In';
        $this->wheres[] = [$type, $column, $values, $boolean];

        return $this;
    }

    /**
     * Add an "or where in" clause to the query.
     * @param  string $column
     * @param  mixed $values
     * @return $this
     */
    public function orWhereIn($column, $values)
    {
        return $this->whereIn($column, $values, 'or');
    }

    /**
     * Add a "where not in" clause to the query.
     * @param  string $column
     * @param  mixed $values
     * @param  string $boolean
     * @return $this
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    /**
     * Add an "or where not in" clause to the query.
     * @param  string $column
     * @param  mixed $values
     * @return $this
     */
    public function orWhereNotIn($column, $values)
    {
        return $this->whereNotIn($column, $values, 'or');
    }

    /**
     * Add a "where null" clause to the query.
     * @param  string $column
     * @param  string $boolean
     * @param  bool $not
     * @return $this
     */
    public function whereNull($column, $boolean = 'and', $not = false)
    {
        $type = $not ? 'NotNull' : 'Null';
        $this->wheres[] = [$type, $column, $boolean];

        return $this;
    }

    /**
     * Add an "or where null" clause to the query.
     * @param  string $column
     * @return $this
     */
    public function orWhereNull($column)
    {
        return $this->whereNull($column, 'or');
    }


    /**
     * Add a "where not null" clause to the query.
     * @param  string $column
     * @param  string $boolean
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        return $this->whereNull($column, $boolean, true);
    }

    /**
     * Add a where between statement to the query.
     * @param  string $column
     * @param  array $values
     * @param  string $boolean
     * @param  bool $not
     * @return $this
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'between';
//        $this->wheres[] = compact('column', 'type', 'boolean', 'not');
        $this->wheres[] = [$column, $type, $values, $boolean, $not];
        $this->addBinding($values, 'where');

        return $this;
    }

    /**
     * Add an or where between statement to the query.
     * @param  string $column
     * @param  array $values
     * @return $this
     */
    public function orWhereBetween($column, array $values)
    {
        return $this->whereBetween($column, $values, 'or');
    }

    /**
     * Add a where not between statement to the query.
     * @param  string $column
     * @param  array $values
     * @param  string $boolean
     * @return $this
     */
    public function whereNotBetween($column, array $values, $boolean = 'and')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    /**
     * Add an or where not between statement to the query.
     * @param  string $column
     * @param  array $values
     * @return $this
     */
    public function orWhereNotBetween($column, array $values)
    {
        return $this->whereNotBetween($column, $values, 'or');
    }

    /**
     * Add an "or where not null" clause to the query.
     * @param  string $column
     * @return $this
     */
    public function orWhereNotNull($column)
    {
        return $this->whereNotNull($column, 'or');
    }

    /**
     * Handles dynamic "where" clauses to the query.
     * @param  string $method
     * @param  string|array $parameters
     * @return $this
     */
    public function dynamicWhere($method, $parameters)
    {
        $finder = substr($method, 5);
        $segments = preg_split(
            '/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE
        );
        // The connector variable will determine which connector will be used for the
        // query condition. We will change it as we come across new boolean values
        // in the dynamic method strings, which could contain a number of these.
        $connector = 'and';
        $index = 0;

        foreach ($segments as $segment) {
            // If the segment is not a boolean connector, we can assume it is a column's name
            // and we will add it to the query as a new constraint as a where clause, then
            // we can keep iterating through the dynamic method string's segments again.
            if ($segment !== 'And' && $segment !== 'Or') {
                $this->addDynamic($segment, $connector, $parameters, $index);
                $index++;
            }
            // Otherwise, we will store the connector so we know how the next where clause we
            // find in the query should be connected to the previous ones, meaning we will
            // have the proper boolean connector to connect the next where clause found.
            else {
                $connector = $segment;
            }
        }

        return $this;
    }

    /**
     * Add a single dynamic where clause statement to the query.
     * @param  string $segment
     * @param  string $connector
     * @param  array $parameters
     * @param  int $index
     * @return void
     */
    protected function addDynamic($segment, $connector, $parameters, $index)
    {
        // Once we have parsed out the columns and formatted the boolean operators we
        // are ready to add it to this query as a where clause just like any other
        // clause on the query. Then we'll increment the parameter index values.
        $bool = strtolower($connector);

        $this->where(Str::toSnake($segment, ' '), '=', $parameters[$index], $bool);
    }

    /**
     * Merge an array of where clauses and bindings.
     * @param  array $wheres
     * @param  array $bindings
     * @return void
     */
    public function mergeWheres($wheres, $bindings)
    {
        $this->wheres = array_merge($this->wheres, (array)$wheres);

        $this->bindings['where'] = array_values(
            array_merge($this->bindings['where'], (array)$bindings)
        );
    }

}