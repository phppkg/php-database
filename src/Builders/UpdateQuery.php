<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 10:45
 */

namespace Inhere\Database\Builders;

use Inhere\Database\Connection;
use Inhere\Database\SQLCompileException;

/**
 * Class UpdateQuery
 * @package Inhere\Database\Builders
 */
class UpdateQuery extends QueryBuilder
{
    /**
     * Column names associated with their values.
     * @var array
     */
    protected $values = [];

    /**
     * {@inheritdoc}
     * @param string $table Associated table name.
     */
    public function __construct(Connection $connection, QueryCompiler $compiler = null, string $table = '')
    {
        parent::__construct($connection, $compiler);

        $this->from = $table;
    }

    /**
     * Set target updated table.
     * @param string $table
     * @return $this
     */
    public function table(string $table): self
    {
        $this->from = $table;

        return $this;
    }

    /**
     * @param array $values <column => value>
     * @return $this
     */
    public function values(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Set update value.
     * @param string $column
     * @param mixed $value
     * @return self|$this
     */
    public function set($column, $value): self
    {
        $this->values[$column] = $value;

        return $this;
    }


    /**
     * Increment a column's value by a given amount.
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [])
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Non-numeric value passed to increment method.');
        }

        $wrapped = $this->compiler->wrap($column);

        $columns = array_merge([$column => $this->raw("$wrapped + $amount")], $extra);

        return $this->update($columns);
    }

    /**
     * Decrement a column's value by a given amount.
     * @param  string $column
     * @param  int $amount
     * @param  array $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [])
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Non-numeric value passed to decrement method.');
        }

        $wrapped = $this->compiler->wrap($column);

        $columns = array_merge([$column => $this->raw("$wrapped - $amount")], $extra);

        return $this->update($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function toSql(): string
    {
        if (!$values = $this->values) {
            throw new SQLCompileException('Update values must be setting.');
        }
        var_dump($this->bindings);
        $this->bindings = $this->cleanBindings(
            $this->compiler->prepareBindingsForUpdate($this->bindings, $values)
        );

        return $this->compiler->compileUpdate($this, $values);
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

}