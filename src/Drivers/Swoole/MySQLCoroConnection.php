<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/23
 * Time: 下午7:41
 */

namespace Inhere\Database\Drivers\Swoole;

use Inhere\Database\DatabaseException;
use Swoole\Coroutine\MySQL;
use Inhere\Database\Connections\SwooleConnection;

/**
 * Class MySQLCoroConnection
 * @package Inhere\Database\Drivers\Swoole
 */
class MySQLCoroConnection extends SwooleConnection
{
    /**
     * @var MySQL
     */
    protected $db;

    /**
     * @return $this
     */
    public function connect()
    {
        if ($this->db && $this->isConnected()) {
            return $this;
        }

        try {
            $opts = $this->options;
            $conn = new MySQL;
            $passed = $conn->connect($opts);

            if ($passed === false) {
                throw new DatabaseException('Connect db failed.');
            }

            $this->db = $conn;
        } catch (\Throwable $e) {
            throw new DatabaseException(
                "Message: {$e->getMessage()}. Error({$conn->connect_errno}): {$conn->connect_error}. on connect to the {$opts['host']} db {$opts['database']}"
            );
        }

        return $this;
    }

    public function disconnect()
    {
        $this->db = null;
    }

    /**
     * @return bool
     */
    public function beginTransaction()
    {
        $this->connect();
        $result = $this->db->query('START TRANSACTION');

        return (bool)$result;
    }

    /**
     * @return bool
     */
    public function commit()
    {
        $this->connect();
        $result = $this->db->query('COMMIT');

        return (bool)$result;
    }

    /**
     * @return bool
     */
    public function rollBack()
    {
        $this->connect();
        $result = $this->db->query('ROLLBACK');

        return (bool)$result;
    }

    /**
     * 在事务中执行语句
     * {@inheritDoc}
     */
    public function transactional(callable $func)
    {
        if (!\is_callable($func)) {
            throw new \InvalidArgumentException('Expected argument of type "callable", got "' . \gettype($func) . '"');
        }

        $this->connect();
        $this->beginTransaction();

        try {
            $return = $func($this);
//            $this->flush();
            $this->commit();

            return $return ?: true;
        } catch (\Throwable $e) {
//            $this->close();
            $this->rollBack();
            throw $e;
        }
    }


    /**
     * @param string $statement
     * @param int $timeout
     * @return array|bool
     */
    public function rawQuery(string $statement, int $timeout = 0)
    {
        $this->connect();

        return $this->db->query($statement, $timeout);
    }

    /**
     * @return int
     */
    public function affectedRows()
    {
        return $this->db->affected_rows;
    }

    /**
     * @return int|string
     */
    public function lastInsertId()
    {
        return $this->db->insert_id;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return (bool)$this->db->connected;
    }

    /**
     * @return MySQL
     */
    public function getDb(): MySQL
    {
        return $this->db;
    }

    /**
     * If no connection set, we escape it with default function.
     * Since mysql_real_escape_string() has been deprecated, we use an alternative one.
     * Please see: http://stackoverflow.com/questions/4892882/mysql-real-escape-string-for-multibyte-without-a-connection
     * @param string|mixed $text
     * @return  string
     */
    protected function escape($text)
    {
        if (\is_int($text) || \is_float($text)) {
            return $text;
        }

        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $text
        );
    }

    /**
     * @param string|mixed $text
     * @return mixed|string
     */
    protected function escapeWithNoConnection($text)
    {
        if (\is_int($text) || \is_float($text)) {
            return $text;
        }

        $text = str_replace("'", "''", $text);

        return addcslashes($text, "\000\n\r\\\032");
    }
}
