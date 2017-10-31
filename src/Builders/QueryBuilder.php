<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 下午2:57
 */

namespace Inhere\Database\Builders;

use Inhere\Database\Builders\Traits\JoinClauseTrait;
use Inhere\Database\Builders\Traits\OrderLimitUnionClauseTrait;
use Inhere\Database\Builders\Traits\WhereClauseTrait;
use Inhere\Database\Connection;
use Inhere\Library\Collections\LiteCollection;
use Inhere\Library\Helpers\Arr;

/**
 * Class QueryBuilder
 * @package Inhere\Database
 * @link https://github.com/illuminate/database/blob/master/Query/Builder.php
 */
class QueryBuilder
{
    use JoinClauseTrait, WhereClauseTrait, OrderLimitUnionClauseTrait;

    /* The query types. */
    const INSERT = 1;
    const SELECT = 2;
    const DELETE = 3;
    const UPDATE = 4;

    /* The builder states. */
    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /* Tokens for nested OR and AND conditions. */
    const TOKEN_AND = '@and';
    const TOKEN_OR = '@or';

    /* operator constants */
    const EQ = '=';
    const NEQ = '!=';
    const LT = '<';
    const LTE = '<=';
    const GT = '>';
    const GTE = '>=';

    /* Sort directions. */
    const SORT_ASC = 'ASC';
    const SORT_DESC = 'DESC';

    /* 字段修饰符: field-modifier */
    const IS = 'IS';
    const IS_NOT = 'IS NOT';

    const IN = 'IN';
    const NOT_IN = 'NOT IN';

    const LIKE = 'LIKE';
    const NOT_LIKE = 'NOT LIKE';

    const NULL = 'NULL';
    const NOT_NULL = 'NOT NULL';

    const BETWEEN = 'BETWEEN';
    const NOT_BETWEEN = 'NOT BETWEEN';

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
     * The table which the query is targeting.
     * @var string
     */
    public $from;

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

    /** @var Connection */
    protected $connection;

    /**
     * @var QueryCompiler
     */
    protected $compiler;

    /**
     * constructor.
     * @param Connection $connection
     * @param QueryCompiler|null $compiler
     */
    public function __construct(Connection $connection, QueryCompiler $compiler = null)
    {
        $this->connection = $connection;
        $this->compiler = $compiler ?: $connection->getQueryCompiler();
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
     * -- common nodes methods
     *******************************************************************************/


    /********************************************************************************
     * -- other nodes methods
     *******************************************************************************/

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
     * Get the SQL representation of the query.
     * @return string
     */
    public function toSql(): string
    {
        return $this->compiler->compileSelect($this);
    }

    public function queryString()
    {
        return $this->compiler->compileSelect($this);
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

    /********************************************************************************
     * execute statement methods
     *******************************************************************************/

    /**
     * Update a record in the database.
     * @param  array $values
     * @return int
     */
    public function update(array $values)
    {
        $sql = $this->compiler->compileUpdate($this, $values);

        return $this->connection->update($sql, $this->cleanBindings(
            $this->compiler->prepareBindingsForUpdate($this->bindings, $values)
        ));
    }

    /**
     * Insert or update a record matching the attributes, and fill it with values.
     * @param  array $attributes
     * @param  array $values
     * @return bool
     */
    public function updateOrInsert(array $attributes, array $values = [])
    {
        if (!$this->where($attributes)->exists()) {
            return $this->insert(array_merge($attributes, $values));
        }

        return (bool)$this->limit(1)->update($values);
    }

    /**
     * Delete a record from the database.
     * @param  mixed $id
     * @return int
     */
    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check the
        // ID to let developers to simply and quickly remove a single row from this
        // database without manually specifying the "where" clauses on the query.
        if (null !== $id) {
            $this->where($this->from . '.id', '=', $id);
        }

        return $this->connection->delete(
            $this->compiler->compileDelete($this), $this->getBindings()
        );
    }

    /**
     * Run a truncate statement on the table.
     * @return void
     */
    public function truncate()
    {
        foreach ($this->compiler->compileTruncate($this) as $sql => $bindings) {
            $this->connection->execute($sql, $bindings);
        }
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
     * @param $name
     * @param $elements
     * @param string $glue
     * @return Fragment
     */
    public function newFragment($name, $elements, $glue = ',')
    {
        return new Fragment($name, $elements, $glue);
    }

    /**
     * Get a new instance of the query builder.
     * @return static
     */
    public function newQuery()
    {
        return new static($this->connection, $this->compiler);
    }

    /**
     * Create a new query instance for a sub-query.
     * @return static
     */
    protected function forSubQuery()
    {
        return $this->newQuery();
    }

    /**
     * Create a raw database expression.
     *
     * @param  mixed  $value
     * @return Expression
     */
    public function raw($value)
    {
        return $this->connection->raw($value);
    }

    /**
     * @param array $props
     * @return QueryBuilder
     */
    public function cloneWithout(array $props = [])
    {
        return tap(clone $this, function ($clone) use ($props) {
            foreach ($props as $property) {
                $clone->{$property} = null;
            }
        });
    }

    /**
     * Clone the query without the given bindings.
     * @param  array $except
     * @return static
     */
    public function cloneWithoutBindings(array $except)
    {
        return tap(clone $this, function ($clone) use ($except) {
            foreach ($except as $type) {
                $clone->bindings[$type] = [];
            }
        });
    }

    /**
     * Determine if the given operator is supported.
     * @param  string $operator
     * @return bool
     */
    protected function invalidOperator($operator)
    {
        return !in_array(strtolower($operator), $this->operators, true) &&
            !in_array(strtolower($operator), $this->compiler->getOperators(), true);
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

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return QueryCompiler
     */
    public function getCompiler(): QueryCompiler
    {
        return $this->compiler;
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

}
