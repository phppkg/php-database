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
use SimpleAR\Builders\Grammars\DefaultGrammar;
use SimpleAR\Builders\Traits\AggregateClauseTrait;
use SimpleAR\Builders\Traits\JoinClauseTrait;
use SimpleAR\Builders\Traits\MutationBuilderTrait;
use SimpleAR\Builders\Traits\UnionClauseTrait;
use SimpleAR\Builders\Traits\WhereClauseTrait;
use SimpleAR\Connections\Connection;

/**
 * Class QueryBuilder
 * @package SimpleAR
 * @link https://github.com/illuminate/database/blob/master/Query/Builder.php
 */
class QueryBuilder
{
    use AggregateClauseTrait,
        JoinClauseTrait,
        WhereClauseTrait,
        MutationBuilderTrait,
        UnionClauseTrait;

    /* The query types. */
    const SELECT = 0;
    const DELETE = 1;
    const UPDATE = 2;

    /* The builder states. */
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /* Sort directions. */
    const SORT_ASC = 'ASC';
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
     * 'select' 'insert' 'update' 'delete'
     * @var string
     */
    protected $type = self::SELECT;

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
    public $useWriter = false;

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
     * Lock the selected rows in the table.
     * @param  string|bool $value
     * @return $this
     */
    public function lock($value = true)
    {
        $this->lock = $value;

        if (null !== $this->lock) {
            $this->useWriter();
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
            $this->grammar->compileExists($this), $this->getBindings(), !$this->useWriter
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

    public function useWriter()
    {
        $this->useWriter = true;

        return $this;
    }

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
        return new static($this->connection, $this->grammar);
    }

    /**
     * @param array $props
     * @return QueryBuilder
     */
    public function cloneWithout(array $props = [])
    {
        $new = clone $this;

        foreach ($props as $prop) {
            $new->$prop = null;
        }

        return $new;
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

    protected function escapeWithNoConnection($text)
    {
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $text
        );
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
