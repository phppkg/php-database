<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-23
 * Time: 17:42
 */

namespace SimpleAR\Builders;


use PDO;
use SimpleAR\Connections\PdoConnection;
use SimpleAR\SQLCompileException;

/**
 * Class QueryCompiler
 * @package SimpleAR\Builders
 * @link https://github.com/spiral/database/blob/master/source/Spiral/Database/Entities/QueryCompiler.php
 */
class QueryCompiler
{
    /**
     * @var PdoConnection
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
     * @param mixed $value
     * @param int $type
     * @return string
     */
    protected function quote($value, $type = PDO::PARAM_STR)
    {
        return $this->connection->quote($value, $type);
    }

    public function compileInsert(string $table, array $columns, array $values)
    {
        if (!$columns || !$values) {
            throw new SQLCompileException('Unable compile the insert sql, the columns and values cannot be empty.');
        }

        $table = $this->quote($table);
        $columnString = $this->quote($columns);
        $valueString = $this->quote($values);

        return "INSERT INTO {$table} {$columnString} VALUES {$valueString}";
    }

    public function compileUpdate(string $table, array $updates, array $wheres = [])
    {
        $table = $this->quote($table);
        $updateString = $this->prepareUpdates($updates);
        $whereString = $this->compileWhere($wheres);

        return trim("UPDATE {$table} SET {$updateString} {$whereString}");
    }

    public function compileDelete(string $table, array $wheres = [])
    {
        $table = $this->quote($table);
        $whereString = $this->compileWhere($wheres);

        return trim("DELETE FROM {$table} {$whereString}");
    }

    protected function prepareUpdates(array $updates)
    {

        return '';
    }

    protected function compileWhere(array $wheres)
    {

        return '';
    }
}
