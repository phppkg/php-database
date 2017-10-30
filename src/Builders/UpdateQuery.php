<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 10:45
 */

namespace Inhere\Database\Builders;

use Inhere\Database\Builders\Traits\JoinClauseTrait;
use Inhere\Database\Builders\Traits\LimitClauseTrait;
use Inhere\Database\Builders\Traits\WhereClauseTrait;
use Inhere\Database\Connection;

/**
 * Class UpdateQuery
 * @package Inhere\Database\Builders
 */
class UpdateQuery extends QueryBuilder
{
    use JoinClauseTrait, WhereClauseTrait, LimitClauseTrait;

    /** @var string */
    public $table;

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

        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    public function from($table)
    {
        return $this->table($table);
    }

    /**
     * Set target updated table.
     * @param string $table
     * @return $this
     */
    public function table(string $table): self
    {
        $this->table = $table;

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
     * @param string $column
     * @param int $step
     * @param array $updates Update some other columns
     */
    public function increment(string $column, $step = 1, array $updates = [])
    {

    }

    public function decrement(string $column, $step = -1, array $updates = [])
    {

    }

    /**
     * {@inheritdoc}
     */
    public function toSql(): string
    {
        return $this->compiler->compileUpdate($this, $this->values);
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

}