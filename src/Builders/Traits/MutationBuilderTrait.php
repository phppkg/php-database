<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-25
 * Time: 13:04
 */

namespace SimpleAR\Builders\Traits;

/**
 * Trait MutationBuilderTrait
 * - insert, update, delete query builder
 * @package SimpleAR\Builders\Traits
 */
trait MutationBuilderTrait
{
    /**
     * @var array
     */
    public $delete;

    /**
     * @var array
     */
    public $update;

    /**
     * @var array
     */
    public $sets;

    /**
     * @var array
     */
    public $columns;

    /**
     * @var array
     */
    public $values;

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
}