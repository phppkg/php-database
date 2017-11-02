<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:36
 */

namespace Inhere\Database\Base;

use Inhere\Database\Connection;
use Inhere\Database\Helpers\DsnHelper;
use Inhere\Exceptions\UnknownMethodException;
use PDO;
use PDOStatement;

/**
 * Class Connection
 * @package Inhere\Database\Base
 */
class PDOConnection extends Connection
{
    const DATETIME = 'Y-m-d H:i:s';

    /**
     * @var PDO|\Closure
     */
    protected $pdo;

    /** @var string */
    protected $quoteNamePrefix = '"';

    /** @var string */
    protected $quoteNameSuffix = '"';

    /** @var string */
    protected $quoteNameEscapeChar = '"';

    /** @var string */
    protected $quoteNameEscapeReplace = '""';

    /**
     * The default PDO connection options.
     * @var array
     */
    protected static $pdoOptions = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"',
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * @var PDOStatement
     */
//    protected $cursor;

    /**
     * @return static
     */
    public function connect()
    {
        if ($this->pdo) {
            return $this;
        }

        $this->options['options'] = array_merge($this->options['options'], static::$pdoOptions);

        $config = $this->options;
        $retry = (int)$config['retry'];
        $retry = ($retry > 0 && $retry <= 5) ? $retry : 0;
        $options = is_array($config['options']) ? $config['options'] : [];
        $dsn = DsnHelper::getDsn($config);

        do {
            try {
                $pdo = new PDO($dsn, $config['user'], $config['password'], $options);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

                break;
            } catch (\PDOException $e) {
                if ($retry <= 0) {
                    throw new \PDOException('Could not connect to DB: ' . $e->getMessage() . '. DSN: ' . $dsn);
                }
            }

            $retry--;
            usleep(50000);
        } while ($retry >= 0);

        $this->pdo = $pdo;
        $this->log('connect to DB server', ['config' => $config], 'connect');
        $this->fire(self::CONNECT, [$this]);

        return $this;
    }

    /**
     * reconnect
     */
    public function reconnect()
    {
        $this->pdo = null;

        $this->connect();
    }

    /**
     * disconnect
     */
    public function disconnect()
    {
        parent::disconnect();

        $this->pdo = null;
    }

    /**
     * Set the timezone on the connection.
     * @param  \PDO $connection
     * @param  array $config
     * @return void
     */
    protected function configureTimezone($connection, array $config)
    {
        if (isset($config['timezone'])) {
            $connection->exec('SET time_zone="' . $config['timezone'] . '"');
        }
    }

    /********************************************************************************
     * fetch data methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @param array $bindings
     * @return int
     */
    public function fetchAffected($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);
        $affected = $sth->rowCount();

        $this->freeResource($sth);

        return $affected;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAssoc($statement, array $bindings = [])
    {
        $data = [];
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $data[current($row)] = $row;
        }

        $this->freeResource($sth);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $column = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

        $this->freeResource($sth);

        return $column;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchGroup($statement, array $bindings = [], $style = PDO::FETCH_COLUMN)
    {
        $sth = $this->execute($statement, $bindings);

        $group = $sth->fetchAll(PDO::FETCH_GROUP | $style);
        $this->freeResource($sth);

        return $group;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObject($statement, array $bindings = [], $class = 'stdClass', array $args = [])
    {
        $sth = $this->execute($statement, $bindings);

        if (!empty($args)) {
            $result = $sth->fetchObject($class, $args);
        } else {
            $result = $sth->fetchObject($class);
        }

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchObjects($statement, array $bindings = [], $class = 'stdClass', array $args = [])
    {
        $sth = $this->execute($statement, $bindings);

        if (!empty($args)) {
            $result = $sth->fetchAll(PDO::FETCH_CLASS, $class, $args);
        } else {
            $result = $sth->fetchAll(PDO::FETCH_CLASS, $class);
        }

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchOne($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $result = $sth->fetch(PDO::FETCH_ASSOC);

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchPairs($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->freeResource($sth);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchValue($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        $result = $sth->fetchColumn();

        $this->freeResource($sth);

        return $result;
    }

    /********************************************************************************
     * Generator methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @param array $bindings
     * @param int $fetchType
     * @return \Generator
     */
    public function cursor($statement, array $bindings = [], $fetchType = PDO::FETCH_ASSOC)
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch($fetchType)) {
            $key = current($row);
            yield $key => $row;
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return \Generator
     */
    public function yieldAssoc($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $key = current($row);
            yield $key => $row;
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return \Generator
     */
    public function yieldAll($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return \Generator
     */
    public function yieldColumn($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_NUM)) {
            yield $row[0];
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @param string $class
     * @param array $args
     * @return \Generator
     */
    public function yieldObjects($statement, array $bindings = [], $class = 'stdClass', array $args = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetchObject($class, $args)) {
            yield $row;
        }

        $this->freeResource($sth);
    }

    /**
     * @param string $statement
     * @param array $bindings
     * @return \Generator
     */
    public function yieldPairs($statement, array $bindings = [])
    {
        $sth = $this->execute($statement, $bindings);

        while ($row = $sth->fetch(PDO::FETCH_KEY_PAIR)) {
            yield $row;
        }

        $this->freeResource($sth);
    }

    /********************************************************************************
     * extended methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @param array $params
     * @return PDOStatement
     */
    public function execute($statement, array $params = [])
    {
        $sth = $this->prepareWithBindings($statement, $params);

        $sth->execute();

        return $sth;
    }

    /**
     * @param string $statement
     * @param array $params
     * @return PDOStatement
     */
    public function prepareWithBindings($statement, array $params = [])
    {
        $this->connect();

        // if there are no values to bind ...
        if (empty($params)) {
            // ... use the normal preparation
            return $this->prepare($statement);
        }

        // rebuild the statement and values
//        $parser = clone $this->parser;
//        list ($statement, $bindings) = $parser->rebuild($statement, $bindings);

        // prepare the statement
        $sth = $this->pdo->prepare($statement);

        $this->log($statement, $params);

        // for the placeholders we found, bind the corresponding data values
        /** @var array $params */
        foreach ($params as $key => $val) {
            $this->bindValue($sth, $key, $val);
        }

        // done
        return $sth;
    }

    /**
     * 事务
     * {@inheritDoc}
     */
    public function transactional(callable $func)
    {
        if (!is_callable($func)) {
            throw new \InvalidArgumentException('Expected argument of type "callable", got "' . gettype($func) . '"');
        }

        $this->connect();
        $this->pdo->beginTransaction();

        try {
            $return = $func($this);
//            $this->flush();
            $this->pdo->commit();

            return $return ?: true;
        } catch (\Throwable $e) {
//            $this->close();
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     * @throws UnknownMethodException
     */
    public function __call($name, array $arguments)
    {
        $this->connect();

        if (!method_exists($this->pdo, $name)) {
            $class = get_class($this);
            throw new UnknownMethodException("Class '{$class}' does not have a method '{$name}'");
        }

        return $this->pdo->$name(...$arguments);
    }

    /**
     * @param PDOStatement $statement
     * @param array|\ArrayIterator $bindings
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * @param PDOStatement $sth
     * @param $key
     * @param $val
     * @return bool
     */
    protected function bindValue(PDOStatement $sth, $key, $val)
    {
        if (is_int($val)) {
            return $sth->bindValue($key, $val, PDO::PARAM_INT);
        }

        if (is_bool($val)) {
            return $sth->bindValue($key, $val, PDO::PARAM_BOOL);
        }

        if (null === $val) {
            return $sth->bindValue($key, $val, PDO::PARAM_NULL);
        }

        if (!is_scalar($val)) {
            $type = gettype($val);
            throw new \RuntimeException("Cannot bind value of type '{$type}' to placeholder '{$key}'");
        }

        return $sth->bindValue($key, $val);
    }

    /**
     * {@inheritdoc}
     */
    public function qn(string $name)
    {
        return $this->quoteName($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function quoteName(string $name)
    {
        if (strpos($name, '.') === false) {
            return $this->quoteSingleName($name);
        }

        return implode('.', array_map([$this, 'quoteSingleName'], explode('.', $name)));
    }

    /**
     * {@inheritdoc}
     */
    public function quoteSingleName(string $name)
    {
        $name = str_replace($this->quoteNameEscapeChar, $this->quoteNameEscapeReplace, $name);

        return $this->quoteNamePrefix . $name . $this->quoteNameSuffix;
    }

    /**
     * {@inheritdoc}
     */
    protected function initQuoteName($driver)
    {
        switch ($driver) {
            case 'mysql':
                $this->quoteNamePrefix = '`';
                $this->quoteNameSuffix = '`';
                $this->quoteNameEscapeChar = '`';
                $this->quoteNameEscapeReplace = '``';

                return;
            case 'sqlsrv':
                $this->quoteNamePrefix = '[';
                $this->quoteNameSuffix = ']';
                $this->quoteNameEscapeChar = ']';
                $this->quoteNameEscapeReplace = '][';

                return;
            default:
                $this->quoteNamePrefix = '"';
                $this->quoteNameSuffix = '"';
                $this->quoteNameEscapeChar = '"';
                $this->quoteNameEscapeReplace = '""';

                return;
        }
    }

    public function q($value, $type = PDO::PARAM_STR)
    {
        return $this->quote($value, $type);
    }

    /**
     * @param string|array $value
     * @param int $type
     * @return string
     */
    public function quote($value, $type = PDO::PARAM_STR)
    {
        $this->connect();

        // non-array quoting
        if (!is_array($value)) {
            return $this->pdo->quote($value, $type);
        }

        // quote array values, not keys, then combine with commas
        /** @var array $value */
        foreach ((array)$value as $k => $v) {
            $value[$k] = $this->pdo->quote($v, $type);
        }

        return implode(', ', $value);
    }

    /********************************************************************************
     * Pdo methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @return int
     */
    public function exec($statement)
    {
        $this->connect();

        // trigger before event
        $this->fire(self::BEFORE_EXECUTE, [$statement, 'exec']);

        $affected = $this->pdo->exec($statement);

        // trigger after event
        $this->fire(self::AFTER_EXECUTE, [$statement, 'exec']);

        return $affected;
    }

    /**
     * {@inheritDoc}
     * @return PDOStatement
     */
    public function query($statement, ...$fetch)
    {
        $this->connect();

        // trigger before event
        $this->fire(self::BEFORE_EXECUTE, [$statement, 'query']);

        $sth = $this->pdo->query($statement, ...$fetch);

        // trigger after event
        $this->fire(self::AFTER_EXECUTE, [$statement, 'query']);

        return $sth;
    }

    /**
     * @param string $statement
     * @param array $options
     * @return PDOStatement
     */
    public function prepare($statement, array $options = [])
    {
        $this->connect();
        $this->log($statement, $options);

        return $this->pdo->prepare($statement, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        $this->connect();

        return $this->pdo->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function inTransaction()
    {
        $this->connect();

        return $this->pdo->inTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        $this->connect();

        return $this->pdo->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        $this->connect();

        return $this->pdo->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
        $this->connect();

        return $this->pdo->errorCode();
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo()
    {
        $this->connect();

        return $this->pdo->errorInfo();
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        $this->connect();

        return $this->pdo->lastInsertId($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($attribute)
    {
        $this->connect();

        return $this->pdo->getAttribute($attribute);
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute($attribute, $value)
    {
        $this->connect();

        return $this->pdo->setAttribute($attribute, $value);
    }

    /**
     * Check whether the connection is available
     * @return bool
     */
    public function ping()
    {
        try {
            $this->pdo->query('select 1')->fetchColumn();
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'server has gone away') !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public static function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers();
    }

    /**
     * @param PDOStatement $sth
     * @return $this
     */
    public function freeResource($sth = null)
    {
        if ($sth && $sth instanceof PDOStatement) {
            $sth->closeCursor();
        }

        return $this;
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        if ($this->pdo instanceof \Closure) {
            return $this->pdo = ($this->pdo)($this);
        }

        return $this->pdo;
    }

    /**
     * @param PDO $pdo
     */
    public function setPdo(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return (bool)$this->pdo;
    }
}
