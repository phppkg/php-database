<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 10:45
 */

namespace Inhere\Database\Builders;

use Inhere\Database\Connection;
use Inhere\Library\Helpers\Arr;

/**
 * Class InsertQuery
 * @package Inhere\Database\Builders
 */
class InsertQuery
{
    /**
     * Query type.
     */
    const QUERY_TYPE = QueryCompiler::INSERT_QUERY;

    /** @var Connection */
    protected $connection;

    /**
     * @var QueryCompiler
     */
    protected $compiler;

    public $from;

    /**
     * Column names associated with insert.
     * @var array
     */
    public $columns = [];

    /**
     * values to be inserted.
     * @var array
     */
    public $values = [];

    /**
     * {@inheritdoc}
     * @param string $table Associated table name.
     */
    public function __construct(Connection $connection, QueryCompiler $compiler = null, string $table = '')
    {
        $this->connection = $connection;
        $this->compiler = $compiler ?: $connection->getQueryCompiler();

        $this->from = $table;
    }

    /**
     * Set target insertion table.
     * @param string $into
     * @return self
     */
    public function into(string $into): self
    {
        $this->from = $into;

        return $this;
    }
    public function table(string $into): self
    {
        $this->from = $into;

        return $this;
    }

    /**
     * Set insertion column names. Names can be provided as array, set of parameters or comma
     * separated string.
     * Examples:
     * $insert->columns(["name", "email"]);
     * $insert->columns("name", "email");
     * $insert->columns("name, email");
     * @param array|string $columns
     * @return self
     */
    public function columns(...$columns): self
    {
        $this->columns = $this->fetchIdentifiers($columns);

        return $this;
    }

    /**
     * Set insertion rowset values or multiple values. Values can be provided in multiple forms
     * (method parameters, array of values, array or values). Columns names will be automatically
     * fetched (if not already specified) from first provided rowset based on rowset keys.
     * Examples:
     * $insert->columns("name", "balance")->values("Wolfy-J", 10);
     * $insert->values([
     *      "name" => "Wolfy-J",
     *      "balance" => 10
     * ]);
     * $insert->values([
     *  [
     *      "name" => "Wolfy-J",
     *      "balance" => 10
     *  ],
     *  [
     *      "name" => "Ben",
     *      "balance" => 20
     *  ]
     * ]);
     * @param mixed $values
     * @return self
     */
    public function values($values): self
    {
        if (!is_array($values)) {
            return $this->values(func_get_args());
        }

        if (empty($values)) {
            throw new \LogicException('Insert values must not be empty');
        }

        //Checking if provided set is array of multiple
        reset($values);

        if (!is_array($values[key($values)])) {
            if (empty($this->columns)) {
                $this->columns = array_keys($values);
            }

            $this->values[] = array_values($values);
        } else {
            /** @var array $values */
            foreach ($values as $row) {
                $this->values[] = array_values($row);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(): array
    {
        return Arr::flatten($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function toSql(): string
    {
        return $this->compiler->compileInsert($this, $this->columns, $this->values);
    }

    /********************************************************************************
     * execute statement methods
     *******************************************************************************/

    /**
     * Insert a new record into the database.
     * @param  array $values
     * @return bool
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient when building these
        // inserts statements by verifying these elements are actually an array.
        if (empty($values)) {
            return true;
        }

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        // Here, we will sort the insert keys for every record so that each insert is
        // in the same order for the record. We need to make sure this is the case
        // so there are not any errors or problems when inserting these records.
        else {
            foreach ($values as $key => $value) {
                ksort($value);

                $values[$key] = $value;
            }
        }

        // Finally, we will run this query against the database connection and return
        // the results. We will need to also flatten these bindings before running
        // the query so they are all in one huge, flattened array for execution.
        return $this->connection->insert(
            $this->compiler->compileInsert($this, $values),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }

    /**
     * Insert a new record and get the value of the primary key.
     * @param  array $values
     * @param  string|null $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $sql = $this->compiler->compileInsertGetId($this, $values, $sequence);

        $values = $this->cleanBindings($values);

        return $this->connection->insert($sql, $values, $sequence);
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        //This must execute our query
        $this->connection->execute($this->toSql());

        return $this->connection->lastInsertId();
    }

    /**
     * Reset all insertion values to make builder reusable (columns still set).
     */
    public function flushValues()
    {
        $this->values = [];
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
}
