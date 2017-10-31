<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 下午11:42
 */

namespace Inhere\Database\Builders;

use Inhere\Database\Base\AbstractCompiler;
use Inhere\Library\Helpers\Arr;

/**
 * Class QueryCompiler
 * @package Inhere\Database\Query\Grammars
 */
class QueryCompiler extends AbstractCompiler
{
    /**
     * Query types for parameter ordering.
     */
    const SELECT_QUERY = 'select';
    const UPDATE_QUERY = 'update';
    const DELETE_QUERY = 'delete';
    const INSERT_QUERY = 'insert';

    /**
     * The grammar specific operators.
     * @var array
     */
    protected $operators = [];

    /**
     * The components that make up a insert clause.
     * @var array
     */
    const INSERT_COMPONENTS = [
        'into',
        'columns',
        'values',
    ];

    /**
     * The components that make up a select clause.
     * @var array
     */
    const SELECT_COMPONENTS = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'unions',
        'lock',
    ];

    /**
     * The components that make up a insert clause.
     * @var array
     */
    const UPDATE_COMPONENTS = [
        'from',
        'columns',
        'values',
    ];

    /********************************************************************************
     * compile SQL command methods
     *******************************************************************************/

    /**
     * Compile a select query into SQL.
     * @param  QueryBuilder $query
     * @return string
     */
    public function compileSelect(QueryBuilder $query)
    {
        // If the query does not have any columns set, we'll set the columns to the
        // * character to just get all of the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
        $original = $query->fields;

        if (null === $original) {
            $query->fields = ['*'];
        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
        $sql = trim($this->concatenate($this->compileComponents($query, static::SELECT_COMPONENTS)));

        $query->fields = $original;

        return $sql;
    }

    /**
     * Compile an insert statement into SQL.
     * @param InsertQuery $query
     * @param array $values
     * @param array $columns
     * @return string
     */
    public function compileInsert(InsertQuery $query, array $values, array $columns = [])
    {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if (!$columns) {
            $columns = $this->columnize(array_keys(reset($values)));
        }

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same amount of parameter
        // bindings so we will loop through the record and parameterize them all.
        $parameters = collect($values)->map(function ($record) {
            return '(' . $this->parameterize($record) . ')';
        })->implode(', ');

        return "insert into $table ($columns) values $parameters";
    }

    /**
     * Compile an insert and get ID statement into SQL.
     * @param  InsertQuery $query
     * @param  array $values
     * @param  string $sequence
     * @return string
     */
    public function compileInsertGetId(InsertQuery $query, $values, $sequence)
    {
        return $this->compileInsert($query, $values);
    }

    /**
     * Compile an update statement into SQL.
     * @param  UpdateQuery $query
     * @param  array $values
     * @return string
     */
    public function compileUpdate(UpdateQuery $query, $values)
    {
        $table = $this->wrapTable($query->from);

        // Each one of the columns in the update statements needs to be wrapped in the
        // keyword identifiers, also a place-holder needs to be created for each of
        // the values in the list of bindings so we can make the sets statements.
        $columns = collect($values)->map(function ($value, $key) {
            return $this->wrap($key) . ' = ' . $this->parameter($value);
        })->implode(', ');

        $joins = '';

        if ($query->joins) {
            $joins = ' ' . $this->compileJoins($query, $query->joins);
        }

        $wheres = $this->compileWheres($query);

        return trim("update {$table}{$joins} set $columns $wheres");
    }

    /**
     * Compile a delete statement into SQL.
     * @param  QueryBuilder $query
     * @return string
     */
    public function compileDelete(QueryBuilder $query)
    {
        $wheres = is_array($query->wheres) ? $this->compileWheres($query) : '';

        return trim("delete from {$this->wrapTable($query->from)} $wheres");
    }

    /********************************************************************************
     * compile SQL clause methods
     *******************************************************************************/

    /**
     * Compile the components necessary for a select clause.
     * @param QueryBuilder $query
     * @param array $components
     * @return array
     */
    protected function compileComponents(QueryBuilder $query, array $components)
    {
        $parts = [];

        foreach ($components as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.
            if (null !== $query->$component) {
                $method = 'compile' . ucfirst($component);
                $parts[$component] = $this->$method($query, $query->$component);
            }
        }

        return $parts;
    }

    /**
     * Compile an aggregated select clause.
     * @param  SelectQuery $query
     * @param  array $aggregate
     * @return string
     */
    protected function compileAggregate($aggregate, SelectQuery $query)
    {
        $column = $this->columnize($aggregate['columns']);
        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if ($query->distinct && $column !== '*') {
            $column = 'distinct ' . $column;
        }

        return 'select ' . $aggregate['function'] . '(' . $column . ') as aggregate';
    }

    /**
     * Compile the "select *" portion of the query.
     * @param  QueryBuilder $query
     * @param  array $columns
     * @return string|null
     */
    protected function compileColumns(QueryBuilder $query, $columns)
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if (!$query->aggregate) {
            return null;
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';

        return $select . $this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     * @param  QueryBuilder $query
     * @param  string $table
     * @return string
     */
    protected function compileFrom(QueryBuilder $query, $table)
    {
        return 'from ' . $this->wrapTable($table);
    }

    /**
     * Compile the "join" portions of the query.
     * @param  QueryBuilder $query
     * @param  array $joins
     * @return string
     */
    protected function compileJoins(QueryBuilder $query, $joins)
    {
        return collect($joins)->map(function ($join) use ($query) {
            $table = $this->wrapTable($join->table);

            return trim("{$join->type} join {$table} {$this->compileWheres($join)}");
        })->implode(' ');
    }

    /**
     * Compile the "where" portions of the query.
     * @param  QueryBuilder $query
     * @return string
     */
    protected function compileWheres(QueryBuilder $query)
    {
        if (!$query->wheres) {
            return '';
        }

        if (count($sql = $this->compileWheresToArray($query)) > 0) {
            return $this->concatenateWhereClauses($query, $sql);
        }

        return '';
    }

    /**
     * Get an array of all the where clauses for the query.
     * @param  QueryBuilder $query
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            return $where['boolean'] . ' ' . $this->{"where{$where['type']}"}($query, $where);
        })->all();
    }

    /**
     * Format the where clause statements into one string.
     * @param  QueryBuilder $query
     * @param  array $sql
     * @return string
     */
    protected function concatenateWhereClauses($query, $sql)
    {
        $conjunction = $query instanceof JoinClause ? 'on' : 'where';

        return $conjunction . ' ' . $this->removeLeadingBoolean(implode(' ', $sql));
    }

    /**
     * Compile a raw where clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereRaw(QueryBuilder $query, $where)
    {
        return $where['sql'];
    }

    /**
     * Compile a basic where clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereBasic(QueryBuilder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return $this->wrap($where['column']) . ' ' . $where['operator'] . ' ' . $value;
    }

    /**
     * Compile a "where in" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereIn(QueryBuilder $query, $where)
    {
        if (!empty($where['values'])) {
            return $this->wrap($where['column']) . ' in (' . $this->parameterize($where['values']) . ')';
        }

        return '0 = 1';
    }

    /**
     * Compile a "where not in" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereNotIn(QueryBuilder $query, $where)
    {
        if (!empty($where['values'])) {
            return $this->wrap($where['column']) . ' not in (' . $this->parameterize($where['values']) . ')';
        }

        return '1 = 1';
    }

    /**
     * Compile a where in sub-select clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereInSub(QueryBuilder $query, $where)
    {
        return $this->wrap($where['column']) . ' in (' . $this->compileSelect($where['query']) . ')';
    }

    /**
     * Compile a where not in sub-select clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereNotInSub(QueryBuilder $query, $where)
    {
        return $this->wrap($where['column']) . ' not in (' . $this->compileSelect($where['query']) . ')';
    }

    /**
     * Compile a "where null" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereNull(QueryBuilder $query, $where)
    {
        return $this->wrap($where['column']) . ' is null';
    }

    /**
     * Compile a "where not null" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereNotNull(QueryBuilder $query, $where)
    {
        return $this->wrap($where['column']) . ' is not null';
    }

    /**
     * Compile a "between" where clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereBetween(QueryBuilder $query, $where)
    {
        $between = $where['not'] ? 'not between' : 'between';

        return $this->wrap($where['column']) . ' ' . $between . ' ? and ?';
    }

    /**
     * Compile a "where date" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereDate(QueryBuilder $query, $where)
    {
        return $this->dateBasedWhere('date', $query, $where);
    }

    /**
     * Compile a "where time" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereTime(QueryBuilder $query, $where)
    {
        return $this->dateBasedWhere('time', $query, $where);
    }

    /**
     * Compile a "where day" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereDay(QueryBuilder $query, $where)
    {
        return $this->dateBasedWhere('day', $query, $where);
    }

    /**
     * Compile a "where month" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereMonth(QueryBuilder $query, $where)
    {
        return $this->dateBasedWhere('month', $query, $where);
    }

    /**
     * Compile a "where year" clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereYear(QueryBuilder $query, $where)
    {
        return $this->dateBasedWhere('year', $query, $where);
    }

    /**
     * Compile a date based where clause.
     * @param  string $type
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function dateBasedWhere($type, QueryBuilder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return $type . '(' . $this->wrap($where['column']) . ') ' . $where['operator'] . ' ' . $value;
    }

    /**
     * Compile a where clause comparing two columns..
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereColumn(QueryBuilder $query, $where)
    {
        return $this->wrap($where['first']) . ' ' . $where['operator'] . ' ' . $this->wrap($where['second']);
    }

    /**
     * Compile a nested where clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereNested(QueryBuilder $query, $where)
    {
        // Here we will calculate what portion of the string we need to remove. If this
        // is a join clause query, we need to remove the "on" portion of the SQL and
        // if it is a normal query we need to take the leading "where" of queries.
        $offset = $query instanceof JoinClause ? 3 : 6;

        return '(' . substr($this->compileWheres($where['query']), $offset) . ')';
    }

    /**
     * Compile a where condition with a sub-select.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereSub(QueryBuilder $query, $where)
    {
        $select = $this->compileSelect($where['query']);

        return $this->wrap($where['column']) . ' ' . $where['operator'] . " ($select)";
    }

    /**
     * Compile a where exists clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereExists(QueryBuilder $query, $where)
    {
        return 'exists (' . $this->compileSelect($where['query']) . ')';
    }

    /**
     * Compile a where exists clause.
     * @param  QueryBuilder $query
     * @param  array $where
     * @return string
     */
    protected function whereNotExists(QueryBuilder $query, $where)
    {
        return 'not exists (' . $this->compileSelect($where['query']) . ')';
    }

    /**
     * Compile the "group by" portions of the query.
     * @param  QueryBuilder $query
     * @param  array $groups
     * @return string
     */
    protected function compileGroups(QueryBuilder $query, $groups)
    {
        return 'group by ' . $this->columnize($groups);
    }

    /**
     * Compile the "having" portions of the query.
     * @param  QueryBuilder $query
     * @param  array $havings
     * @return string
     */
    protected function compileHavings(QueryBuilder $query, $havings)
    {
        $sql = implode(' ', array_map([$this, 'compileHaving'], $havings));

        return 'having ' . $this->removeLeadingBoolean($sql);
    }

    /**
     * Compile a single having clause.
     * @param  array $having
     * @return string
     */
    protected function compileHaving(array $having)
    {
        // If the having clause is "raw", we can just return the clause straight away
        // without doing any more processing on it. Otherwise, we will compile the
        // clause into SQL based on the components that make it up from builder.
        if ($having['type'] === 'Raw') {
            return $having['boolean'] . ' ' . $having['sql'];
        }

        return $this->compileBasicHaving($having);
    }

    /**
     * Compile a basic having clause.
     * @param  array $having
     * @return string
     */
    protected function compileBasicHaving($having)
    {
        $column = $this->wrap($having['column']);
        $parameter = $this->parameter($having['value']);

        return $having['boolean'] . ' ' . $column . ' ' . $having['operator'] . ' ' . $parameter;
    }

    /**
     * Compile the "order by" portions of the query.
     * @param  QueryBuilder $query
     * @param  array $orders
     * @return string
     */
    protected function compileOrders(QueryBuilder $query, $orders)
    {
        if (!empty($orders)) {
            return 'order by ' . implode(', ', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
     * @param  QueryBuilder $query
     * @param  array $orders
     * @return array
     */
    protected function compileOrdersToArray(QueryBuilder $query, $orders)
    {
        return array_map(function ($order) {
//            return !isset($order['sql'])
//                ? $this->wrap($order['column']) . ' ' . $order['direction']
//                : $order['sql'];
            return $order['sql'] ?? $this->wrap($order['column']) . ' ' . $order['direction'];
        }, $orders);
    }

    /**
     * Compile the random statement into SQL.
     * @param  string $seed
     * @return string
     */
    public function compileRandom($seed)
    {
        return 'RANDOM()';
    }

    /**
     * Compile the "limit" portions of the query.
     * @param  QueryBuilder $query
     * @param  int $limit
     * @return string
     */
    protected function compileLimit(QueryBuilder $query, $limit)
    {
        return 'limit ' . ((int)$limit ?: '18446744073709551615');
    }

    /**
     * Compile the "offset" portions of the query.
     * @param  QueryBuilder $query
     * @param  int $offset
     * @return string
     */
    protected function compileOffset(QueryBuilder $query, $offset)
    {
        return 'offset ' . (int)$offset;
    }

    /**
     * Compile the "union" queries attached to the main query.
     * @param  QueryBuilder $query
     * @return string
     */
    protected function compileUnions(QueryBuilder $query)
    {
        $sql = '';
        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }

        if (!empty($query->unionOrders)) {
            $sql .= ' ' . $this->compileOrders($query, $query->unionOrders);
        }

        if (null !== $query->unionLimit) {
            $sql .= ' ' . $this->compileLimit($query, $query->unionLimit);
        }

        if (null !== $query->unionOffset) {
            $sql .= ' ' . $this->compileOffset($query, $query->unionOffset);
        }

        return ltrim($sql);
    }

    /**
     * Compile a single union statement.
     * @param  array $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $type = $union['all'] ? ' union all ' : ' union ';

        return $type . $union['query']->toSql();
    }

    /**
     * Compile an exists statement into SQL.
     * @param  QueryBuilder $query
     * @return string
     */
    public function compileExists(QueryBuilder $query)
    {
        $select = $this->compileSelect($query);

        return "select exists({$select}) as {$this->wrap('exists')}";
    }

    /**
     * Prepare the bindings for an update statement.
     * @param  array $bindings
     * @param  array $values
     * @return array
     */
    public function prepareBindingsForUpdate(array $bindings, array $values)
    {
        $cleanBindings = Arr::except($bindings, ['join', 'select']);

        return array_values(
            array_merge($bindings['join'], $values, Arr::flatten($cleanBindings))
        );
    }


    /**
     * Compile a truncate table statement into SQL.
     * @param  QueryBuilder $query
     * @return array
     */
    public function compileTruncate(QueryBuilder $query)
    {
        return ['truncate ' . $this->wrapTable($query->from) => []];
    }

    /**
     * Compile the lock into SQL.
     * @param  QueryBuilder $query
     * @param  bool|string $value
     * @return string
     */
    protected function compileLock(QueryBuilder $query, $value)
    {
        return is_string($value) ? $value : '';
    }

    /**
     * Determine if the grammar supports savepoints.
     * @return bool
     */
    public function supportsSavepoints()
    {
        return true;
    }

    /**
     * Compile the SQL statement to define a savepoint.
     * @param  string $name
     * @return string
     */
    public function compileSavepoint($name)
    {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * Compile the SQL statement to execute a savepoint rollback.
     * @param  string $name
     * @return string
     */
    public function compileSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /********************************************************************************
     * helper methods
     *******************************************************************************/

    /**
     * Remove the leading boolean from a statement.
     * @param  string $value
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * Get the grammar specific operators.
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }
}
