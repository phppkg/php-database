<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-23
 * Time: 17:42
 */

namespace Inhere\Database\Bak;

use Inhere\Database\Builders\Compilers\AbstractCompiler;
use Inhere\Database\Connections\PDOConnection;
use Inhere\Database\SQLCompileException;
use PDO;

/**
 * Class QueryCompiler
 * @package Inhere\Database\Builders
 * @link https://github.com/spiral/database/blob/master/source/Spiral/Database/Entities/QueryCompiler.php
 */
class QueryCompilerB extends AbstractCompiler
{
    /**
     * @var PDOConnection
     */
    protected $connection;

    /**
     * Query types for parameter ordering.
     */
    const SELECT_QUERY = 'select';
    const UPDATE_QUERY = 'update';
    const DELETE_QUERY = 'delete';
    const INSERT_QUERY = 'insert';

    /**
     * quote (field/table) name
     * @param string|array $name
     * @return string
     */
    protected function qn($name)
    {
        if (\is_string($name)) {
            return $this->connection->quoteName($name);
        }

        return array_map(function ($name) {
            return $this->connection->quoteName($name);
        }, (array)$name);
    }

    /**
     * quote value
     * @param mixed $value
     * @param int $type
     * @return string
     */
    protected function q($value, $type = PDO::PARAM_STR)
    {
        return $this->connection->quote($value, $type);
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $values
     * @return string
     */
    public function compileInsert(string $table, array $columns, array $values)
    {
        if (!$columns || !$values) {
            throw new SQLCompileException('Unable compile the insert sql, the columns and values cannot be empty.');
        }

        $table = $this->qn($table);
        $columnString = $this->qn($columns);
        $valueString = $this->q($values);

        return "INSERT INTO {$table} {$columnString} VALUES {$valueString}";
    }

    /**
     * @param string $table
     * @param array $updates
     * @param array $wheres
     * @return string
     */
    public function compileUpdate(string $table, array $updates, array $wheres = [])
    {
        $table = $this->qn($table);
        $updateString = $this->prepareUpdates($updates);
        $whereString = $this->compileWheres($wheres);

        return trim("UPDATE {$table} SET {$updateString} {$whereString}");
    }

    /**
     * @param string $table
     * @param array $wheres
     * @return string
     */
    public function compileDelete(string $table, array $wheres = [])
    {
        $table = $this->qn($table);
        $whereString = $this->compileWheres($wheres);

        return trim("DELETE FROM {$table} {$whereString}");
    }

    protected function prepareUpdates(array $updates)
    {
        foreach ($updates as $column => &$value) {
//            if ($value instanceof FragmentInterface) {
//                $value = $this->prepareFragment($value);
//            } else {
            //Simple value (such condition should never be met since every value has to be
            //wrapped using parameter interface)
//                $value = '?';
//            }

            $value = "{$this->q($column)} = {$value}";
            unset($value);
        }

        return trim(implode(', ', $updates));
    }

    protected function compileWheres(array $wheres)
    {

        return '';
    }
}
