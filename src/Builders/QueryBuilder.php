<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 下午2:57
 */

namespace SimpleAR\Builders;

use Closure;
use Inhere\Library\Helpers\Arr;
use Inhere\Library\Helpers\Str;
use SimpleAR\Connections\Connection;
use SimpleAR\Builders\Grammars\DefaultGrammar;

/**
 * Class QueryBuilder
 * @package SimpleAR
 * @link https://github.com/illuminate/database/blob/master/Query/Builder.php
 */
class QueryBuilder extends BaseQuery
{
    /**
     * Sort directions.
     */
    const SORT_ASC  = 'ASC';
    const SORT_DESC = 'DESC';

    /** @var Connection */
    public $connection;

    /**
     * @var DefaultGrammar
     */
    private $grammar;

    /**
     * @var QueryCompiler
     */
    private $compiler;

    /**
     * The current query value bindings.
     * @var array
     */
    public $bindings = [
        'select' => [],
        'join' => [],
        'where' => [],
        'having' => [],
        'order' => [],
        'union' => [],
    ];
    /**
     * An aggregate function and column to be run.
     * @var array
     */
    public $aggregate;

    /**
     * The columns that should be returned.
     * @var array
     */
    public $fields;

    /**
     * Indicates if the query returns distinct results.
     * @var bool
     */
    public $distinct = false;

    /**
     * The table which the query is targeting.
     * @var string
     */
    public $from;

    /**
     * The table joins for the query.
     * @var array
     */
    public $joins = [];

    /**
     * The where constraints for the query.
     * @var array
     */
    public $wheres = [];

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
     * The query union statements.
     * @var array
     */
    public $unions = [];

    /**
     * The maximum number of union records to return.
     * @var int
     */
    public $unionLimit;

    /**
     * The number of union records to skip.
     * @var int
     */
    public $unionOffset;

    /**
     * The orderings for the union query.
     * @var array
     */
    public $unionOrders = [];

    /**
     * Indicates whether row locking is being used.
     * @var string|bool
     */
    public $lock;

    /**
     * All of the available clause operators.
     * @var array
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Whether use write pdo for select.
     * @var bool
     */
    public $useWritePdo = false;

    /**
     * 'select' 'insert' 'update' 'delete'
     * @var string
     */
    private $type = 'select';

    /**
     * @var array
     */
    private $delete;

    /**
     * @var array
     */
    private $update;

    /**
     * @var array
     */
    private $sets;

    /**
     * @var array
     */
    private $columns;

    /**
     * @var array
     */
    private $values;

    private $sql;

    public function __construct(Connection $connection, DefaultGrammar $grammar = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar;
    }

    /********************************************************************************
     * select statement methods
     *******************************************************************************/

    /**
     * Insert a new record
     * @param  array $values
     * @return $this
     */
    public function insert(array $values)
    {
        if (!$values) {
            return $this;
        }

        $this->type = 'insert';
        $this->values = $values;

        return $this;
    }

    /**
     * @param null $table
     * @return $this
     */
    public function delete($table = null)
    {
        $this->type = 'delete';
        $this->delete = ['DELETE'];

        if (!$table) {
            $this->from($table);
        }

        return $this;
    }

    /**
     * @param array $values
     * @param null $table
     * @return $this
     */
    public function update(array $values, $table = null)
    {
        $this->type = 'update';
        $this->update = ['UPDATE'];

        $this->values($values);

        if (!$table) {
            $this->from($table);
        }

        return $this;
    }

    public function set($conditions, $glue = ',')
    {
        $this->sets[] = $conditions;

        return $this;
    }

    /**
     * @param string|array $columns The column name(s) to insert/update.
     * @return $this
     */
    public function columns($columns)
    {
        $this->columns = $columns;


        return $this;
    }

    public function values(array $values)
    {
        foreach ($values as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $values[$key] = implode(',', (array)$value);
            }
        }

        $this->values[] = ['()', $values, '), ('];

        return $this;
    }

    /********************************************************************************
     * select statement methods
     *******************************************************************************/

    /**
     * @param array ...$fields
     * @return $this
     */
    public function select(...$fields)
    {
        if (!$fields) {
            $fields = ['*'];
        }

        // e.g. select(['field', 'field1'])
        if (isset($fields[0])) {
            $fields = $fields[0];
        }

        $this->fields = $fields;

        return $this;
    }

    /**
     * Add a new select column to the query.
     * @param  array ...$fields
     * @return $this
     */
    public function addSelect(...$fields)
    {
        $this->fields = array_merge((array)$this->fields, $fields[0] ?? $fields);

        return $this;
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
     * Set the table which the query is targeting.
     * @param  string $table
     * @return $this
     */
    public function from($table)
    {
        $this->from = $table;

        return $this;
    }


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


    /********************************************************************************
     * -- other nodes methods
     *******************************************************************************/

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

    /**
     * Add a union statement to the query.
     * @param  static|\Closure $query
     * @param  bool $all
     * @return $this
     */
    public function union($query, $all = false)
    {
        if ($query instanceof Closure) {
            $query($query = $this->newQuery());
        }

        $this->unions[] = [$query, $all];

        $this->addBinding($query->getBindings(), 'union');

        return $this;
    }

    public function unionAll($query)
    {
        return $this->union($query, true);
    }


    /**
     * Lock the selected rows in the table.
     * @param  string|bool $value
     * @return $this
     */
    public function lock($value = true)
    {
        $this->lock = $value;

        if (null !== $this->lock) {
            $this->useWritePdo();
        }

        return $this;
    }

    /**
     * Lock the selected rows in the table for updating.
     * @return $this
     */
    public function lockForUpdate()
    {
        return $this->lock(true);
    }

    /**
     * Share lock the selected rows in the table.
     * @return $this
     */
    public function sharedLock()
    {
        return $this->lock(false);
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
            $this->grammar->compileExists($this), $this->getBindings(), !$this->useWritePdo
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
     * Execute an aggregate function on the database.
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

    /**
     * Get the SQL representation of the query.
     * @return string
     */
    public function toSql()
    {
        return $this->grammar->compileSelect($this);
    }

    /**
     * Set the bindings on the query builder.
     * @param  array $bindings
     * @param  string $type
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setBindings(array $bindings, $type = 'where')
    {
        if (!array_key_exists($type, $this->bindings)) {
            throw new \InvalidArgumentException("Invalid binding type: {$type}.");
        }
        $this->bindings[$type] = $bindings;

        return $this;
    }

    /**
     * Add a binding to the query.
     * @param  mixed $value
     * @param  string $type
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addBinding($value, $type = 'where')
    {
        if (!array_key_exists($type, $this->bindings)) {
            throw new \InvalidArgumentException("Invalid binding type: {$type}.");
        }

        if (is_array($value)) {
            $this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
        } else {
            $this->bindings[$type][] = $value;
        }

        return $this;
    }

    /**
     * Merge an array of bindings into our bindings.
     * @param  self $query
     * @return $this
     */
    public function mergeBindings(self $query)
    {
        $this->bindings = array_merge_recursive($this->bindings, $query->bindings);

        return $this;
    }

    /**
     * Remove all of the expressions from a list of bindings.
     * @param  array $bindings
     * @return array
     */
    protected function cleanBindings(array $bindings)
    {
        return array_values(array_filter($bindings, function ($binding) {
            return !$binding instanceof Expression;
        }));
    }

    /********************************************************************************
     * helper methods
     *******************************************************************************/


    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get the current query value bindings in a flattened array.
     * @return array
     */
    public function getBindings()
    {
        return Arr::flatten($this->bindings);
    }

    /**
     * Get the raw array of bindings.
     * @return array
     */
    public function getRawBindings()
    {
        return $this->bindings;
    }

    /**
     * Get a new instance of the query builder.
     * @return static
     */
    public function newQuery()
    {
        return new static($this->connection, $this->grammar, $this->processor);
    }

    /**
     * Determine if the given operator is supported.
     * @param  string $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return !in_array(strtolower($operator), $this->operators, true);
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

    /**
     * @param $text
     * @param bool|string $extra
     * @return bool|string
     */
    public function escape($text, $extra = false)
    {
        if (is_int($text) || is_float($text)) {
            return $text;
        }

        if (!method_exists($this->connection, 'quote')) {
            $result = $this->escapeWithNoConnection($text);
        } else {
            $result = substr($this->connection->quote($text), 1, -1);
        }

        if ($extra) {
            $extra = ($extra === true) ? '%_' : $extra;

            $result = addcslashes($result, $extra);
        }

        return $result;
    }

    public function e($text, $extra = false)
    {
        return $this->escape($text, $extra);
    }

    /**
     * @return QueryCompiler
     */
    public function getCompiler(): QueryCompiler
    {
        return $this->compiler;
    }

    /**
     * @param QueryCompiler $compiler
     */
    public function setCompiler(QueryCompiler $compiler)
    {
        $this->compiler = $compiler;
    }
}
