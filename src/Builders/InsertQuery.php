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
class InsertQuery extends QueryBuilder
{
    /**
     * Query type.
     */
    const QUERY_TYPE = QueryCompiler::INSERT_QUERY;

    /**
     * @var string
     */
    protected $table = '';

    /**
     * Column names associated with insert.
     * @var array
     */
    protected $columns = [];

    /**
     * values to be inserted.
     * @var array
     */
    protected $values = [];

    /**
     * {@inheritdoc}
     * @param string $table Associated table name.
     */
    public function __construct(Connection $connection, QueryCompiler $compiler, string $table = '')
    {
        parent::__construct($connection, $compiler);

        $this->table = $table;
    }

    /**
     * Set target insertion table.
     * @param string $into
     * @return self
     */
    public function into(string $into): self
    {
        $this->table = $into;

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
        return $this->compiler->compileInsert($this->table, $this->columns, $this->values);
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
}
