<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:36
 */

namespace SimpleAR\Connections;

use Inhere\Exceptions\UnknownMethodException;
use Inhere\Library\Helpers\Php;
use PDO;
use PDOStatement;
use SimpleAR\Helpers\DsnHelper;

/**
 * Class Connection
 * @package SimpleAR\Base
 */
class PdoConnection extends Connection
{
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
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * @return static
     */
    public function connect()
    {
        if ($this->pdo) {
            return $this;
        }

        $config = array_merge(self::DEFAULT_CONFIG, $this->config);

        $retry = (int)$config['retry'];
        $retry = ($retry > 0 && $retry <= 5) ? $retry : 0;
        $options = is_array($config['options']) ? $config['options'] : [];
        $dsn = DsnHelper::getDsn($config);

        do {
            try {
                $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
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
        $this->pdo = null;

        $this->fire(self::DISCONNECT, [$this]);
    }

    /**
     * Set the timezone on the connection.
     *
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
     * basic command methods
     *******************************************************************************/

    /**
     * Run a select statement
     *
     * @param  string  $statement
     * @param  array   $bindings
     * @return array
     */
    public function select($statement, array $bindings = [])
    {
        return $this->fetchAll($statement, $bindings);
    }

    /**
     * Run a insert statement
     *
     * @param  string  $statement
     * @param  array   $bindings
     * @return int
     */
    public function insert($statement, array $bindings = [])
    {
        return $this->fetchAffected($statement, $bindings);
    }

    /**
     * Run a update statement
     *
     * @param  string  $statement
     * @param  array   $bindings
     * @return int
     */
    public function update($statement, array $bindings = [])
    {
        return $this->fetchAffected($statement, $bindings);
    }

    /**
     * Run a delete statement
     *
     * @param  string  $statement
     * @param  array   $bindings
     * @return int
     */
    public function delete($statement, array $bindings = [])
    {
        return $this->fetchAffected($statement, $bindings);
    }

    /********************************************************************************
     * fetch data methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @param array $values
     * @return int
     */
    public function fetchAffected($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        return $sth->rowCount();
    }

    public function fetchAll($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchAssoc($statement, array $values = [])
    {
        $data = [];
        $sth = $this->execute($statement, $values);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $data[current($row)] = $row;
        }

        return $data;
    }

    public function fetchColumn($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function fetchGroup($statement, array $values = [], $style = PDO::FETCH_COLUMN)
    {
        $sth = $this->execute($statement, $values);

        return $sth->fetchAll(PDO::FETCH_GROUP | $style);
    }

    public function fetchObject($statement, array $values = [], $class = 'stdClass', array $args = [])
    {
        $sth = $this->execute($statement, $values);

        if (!empty($args)) {
            return $sth->fetchObject($class, $args);
        }

        return $sth->fetchObject($class);
    }

    public function fetchObjects($statement, array $values = [], $class = 'stdClass', array $args = [])
    {
        $sth = $this->execute($statement, $values);

        if (!empty($args)) {
            return $sth->fetchAll(PDO::FETCH_CLASS, $class, $args);
        }

        return $sth->fetchAll(PDO::FETCH_CLASS, $class);
    }

    public function fetchOne($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchPairs($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        return $sth->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function fetchValue($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        return $sth->fetchColumn();
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
    }

    /**
     * @param string $statement
     * @param array $values
     * @return \Generator
     */
    public function yieldAll($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function yieldAssoc($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $key = current($row);
            yield $key => $row;
        }
    }

    public function yieldColumn($statement, array $values = [])
    {
        $sth = $this->execute($statement, $values);

        while ($row = $sth->fetch(PDO::FETCH_NUM)) {
            yield $row[0];
        }
    }

    /********************************************************************************
     * extended methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @param array $values
     * @return PDOStatement
     */
    public function execute($statement, array $values = [])
    {
        $sth = $this->prepareWithValues($statement, $values);

        $sth->execute();

        return $sth;
    }

    /**
     * @param string $statement
     * @param array $values
     * @return PDOStatement
     */
    public function prepareWithValues($statement, array $values = [])
    {
        // if there are no values to bind ...
        if (empty($values)) {
            // ... use the normal preparation
            return $this->prepare($statement);
        }

        $this->connect();

        // rebuild the statement and values
        $parser = clone $this->parser;
        list ($statement, $values) = $parser->rebuild($statement, $values);

        // prepare the statement
        $sth = $this->pdo->prepare($statement);

        // for the placeholders we found, bind the corresponding data values
        /** @var array $values */
        foreach ($values as $key => $val) {
            $this->bindValue($sth, $key, $val);
        }

        // done
        return $sth;
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

        return Php::call([$this->pdo, $name], ...$arguments);
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

    public function quoteName($name)
    {
        if (strpos($name, '.') === false) {
            return $this->quoteSingleName($name);
        }
        return implode(
            '.',
            array_map([$this, 'quoteSingleName'], explode('.', $name))
        );
    }

    public function quoteSingleName($name)
    {
        $name = str_replace(
            $this->quoteNameEscapeChar,
            $this->quoteNameEscapeReplace,
            $name
        );

        return $this->quoteNamePrefix . $name . $this->quoteNameSuffix;
    }


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

    /********************************************************************************
     * Pdo methods
     *******************************************************************************/

    /**
     * @param string $statement
     * @return int
     */
    public function exec($statement)
    {
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
        // trigger before event
        $this->fire(self::BEFORE_EXECUTE, [$statement, 'query']);

        $sth = $this->pdo->query($statement, ...$fetch);

        // trigger after event
        $this->fire(self::AFTER_EXECUTE, [$statement, 'query']);

        return $sth;
    }

    /**
     * @param string $statement
     * @param null|string $options
     * @return PDOStatement
     */
    public function prepare($statement, $options = null)
    {
        return $this->pdo->prepare($statement, $options);
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

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        return $this->pdo->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        return $this->pdo->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
        return $this->pdo->errorCode();
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo()
    {
        return $this->pdo->errorInfo();
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($attribute)
    {
        return $this->pdo->getAttribute($attribute);
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute($attribute, $value)
    {
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
    public function freeResult($sth)
    {
        if ($sth instanceof PDOStatement) {
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
