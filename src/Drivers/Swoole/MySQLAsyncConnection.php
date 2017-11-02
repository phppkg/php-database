<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/23
 * Time: 下午7:41
 */

namespace Inhere\Database\Drivers\Swoole;

use Inhere\Database\Connections\SwooleConnection;
use Inhere\Database\DatabaseException;
use Swoole\MySQL;

/**
 * Class MySQLAsyncConnection
 * @package Inhere\Database\Drivers\Swoole
 */
class MySQLAsyncConnection extends SwooleConnection
{
    /**
     * @var MySQL
     */
    protected $db;

    /**
     * @var \stdClass
     */
    private $temp;

    /**
     * @var string
     */
    private $statement;

    /**
     * @var callable
     */
    private $completed;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $options)
    {
        parent::__construct($options);

        $this->temp = new \stdClass();

        // init
        $this->resetTemp();
    }

    /**
     * @return $this
     */
    public function connect()
    {
        // reset it every time
        $this->resetTemp();

        if (!$this->db) {
            $this->db = new MySQL();
        }

        if (!$this->completed) {
            throw new \LogicException("Must define the 'completed' callback.");
        }

        return $this;
    }

    public function resetTemp()
    {
        $this->temp->affectedRows = 0;
        $this->temp->insertId = 0;
        $this->temp->result = null;
    }

    /**
     * @param string $statement
     * @return array|bool
     */
    public function rawQuery(string $statement)
    {
        $this->connect();
        $this->statement = $statement;

        return $this->db->connect($this->options, [$this, 'onConnect']);
    }

    /**
     * @param MySQL $db
     * @param bool $connected
     */
    public function onConnect(MySQL $db, $connected)
    {
        if ($connected === false) {
            throw new DatabaseException("Error({$db->connect_errno}):{$db->connect_error} on connect to {$this->options['host']}");
        }

        $db->query($this->statement, [$this, 'onExecuted']);
    }

    /**
     * @param MySQL $db
     * @param bool|array $result
     */
    public function onExecuted(MySQL $db, $result)
    {
        if ($result === false) {
            throw new DatabaseException("Error({$db->connect_errno}):{$db->connect_error} on execute SQL: {$this->statement}");
        }

        if ($result === true) {
            $this->temp->affectedRows = $db->affected_rows;
            $this->temp->insertId = $db->insert_id;
        }

        $this->temp->result = $result;

        // call callback.
        ($this->completed)($this);

        $this->statement = null;
        $db->close();
    }

    /**
     * @param callable $callback
     */
    public function onCompleted(callable $callback)
    {
        $this->setCompleted($callback);
    }


    /**
     * 在事务中执行语句
     * {@inheritDoc}
     */
    public function transactional(callable $callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Expected argument of type "callable", got "' . gettype($callback) . '"');
        }

        $this->connect();
        $this->db->connect($this->options, function (MySQL $db, $connected) use($callback) {
            if ($connected === false) {
                throw new DatabaseException("Error({$db->connect_errno}):{$db->connect_error} on connect to {$this->options['host']}");
            }

            $db->begin(function(MySQL $db, $result) use($callback) {
                $callback($db);
            });
        });

        try {
            $return = $callback($this);
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
     * @return int
     */
    public function getResult()
    {
        return $this->temp->result;
    }

    /**
     * @return int
     */
    public function affectedRows()
    {
        return $this->temp->affectedRows;
    }

    /**
     * @return int|string
     */
    public function lastInsertId()
    {
        return $this->temp->insertId;
    }

    /**
     * @return MySQL
     */
    public function getDb(): MySQL
    {
        return $this->db;
    }

    /**
     * @param callable $completed
     */
    public function setCompleted(callable $completed)
    {
        $this->completed = $completed;
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
        if (is_int($text) || is_float($text)) {
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
        if (is_int($text) || is_float($text)) {
            return $text;
        }

        $text = str_replace("'", "''", $text);

        return addcslashes($text, "\000\n\r\\\032");
    }
}
