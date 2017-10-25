<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/1 0001
 * Time: 22:02
 */

namespace Inhere\Database\Connections;

use Inhere\Exceptions\UnknownMethodException;

/**
 * Class AbstractConnections
 * @package Inhere\Database\Connections
 */
abstract class Connections implements ManagerInterface
{
    // mode: singleton master-slave cluster
    const MODE_SINGLETON = 1;
    const MODE_MASTER_SLAVE = 2;
    const MODE_CLUSTER = 3;

    const READER = 'reader';
    const WRITER = 'writer';

    /** @var int */
    private $mode = self::MODE_SINGLETON;

    /**
     * connection names
     * if value is TRUE, has been connected
     * @var array
     */
    protected $names = [
        // 'name1' => false,
        // 'name2' => true, // has been connected
    ];

    /**
     * connection callback list
     * @var array
     */
    protected $callbacks = [
        // 'name1' => function(){},
        // 'name2' => function(){},
    ];

    /**
     * There are instanced connections
     * @var \PDO[]
     */
    protected $connections = [
        // 'name2' => Object (\PDO),
    ];

    /**
     * db config
     * @var array
     */
    protected $config = [];

    /**
     * event handlers
     * @var array[]
     */
    private $eventCallbacks = [];

    /**
     * The current active connection, it is always latest connection.
     * @var \PDO
     */
    protected $activated;

    ////
    // When open Transaction, will fixed the writer until close the transaction(commit/rollback).
    ////

    /**
     * @var bool
     */
    protected $openTransaction = false;

    /**
     * SimpleDb constructor.
     * @param array $config The db connection config
     * @param array $settings The client settings
     */
    public function __construct(array $config = [], array $settings = [])
    {
        $this->setConfig($config);
    }

    /**
     * @param null $name
     * @return \PDO
     */
    public function getReader($name = null)
    {
        // return a random connection
        if (null === $name) {
            $name = array_rand($this->names);
        }

        return $this->getConnection($name);
    }

    /**
     * @inheritdoc
     */
    public function getWriter($name = null)
    {
        // return a random connection
        if (null === $name) {
            $name = array_rand($this->names);
        }

        return $this->getConnection($name);
    }

    /**
     * getConnection
     * @param  string $name
     * @return \PDO
     */
    protected function getConnection($name = null)
    {
        // no config
        if (!$this->config) {
            throw new \RuntimeException('No connection config for connect to the database');
        }

        if (!isset($this->names[$name])) {
            throw new \InvalidArgumentException("The connection [$name] don't exists!");
        }

        // no config for $name connection
        if (!$this->config[$name]) {
            throw new \RuntimeException('No config for the connection: ' . $name);
        }

        // if not be instanced.
        if (!isset($this->connections[$name])) {
            $cb = $this->callbacks[$name];
            $config = $this->config[$name];

            // create connection
            $this->names[$name] = true;
            $this->connections[$name] = $cb($config);

            // trigger success connected
            $this->fireEvent(self::CONNECT, [$name, static::MODE, $config]);

            // is old connection, check it is available. if disconnect, retry connect
        } elseif (!$this->ping($this->connections[$name])) {
            $cb = $this->callbacks[$name];
            $config = $this->config[$name];

            $this->connections[$name] = $cb($config);
            $config['_message'] = 'connection has gone away,it is retry connection';

            // trigger success connected
            $this->fireEvent(self::CONNECT, [$name, static::MODE, $config]);
        }

        // the current connection always latest.
        return ($this->activated = $this->connections[$name]);
    }


    /**
     * @param int $mode
     */
    public function setMode(int $mode)
    {
        $this->mode = $mode;
    }

    /**
     * @param array $config
     */
    protected function setCallbacks(array $config)
    {
        foreach ($config as $name => $conf) {
            if ($conf) {
                $this->setCallback($name);
            }
        }
    }

    /**
     * @param $name
     */
    protected function setCallback($name)
    {
        if (isset($this->names[$name]) && true === $this->names[$name]) {
            throw new \LogicException("Connection $name has been connected, don't allow override it.");
        }

        // not connected
        $this->names[$name] = false;
        $this->callbacks[$name] = $this->createCallback();
    }

    /**
     * @return \Closure
     */
    protected function createCallback()
    {
        return function (array $config) {
            $config = array_merge([
                'dsn' => 'mysql:host=localhost;port=3306;dbname=db_name;charset=UTF8',
                'user' => 'root',
                'pass' => '',
                'opts' => [],
                'retry' => 0, // retry connect times.
            ], $config);

            $retry = (int)$config['retry'];
            $retry = ($retry > 0 && $retry <= 5) ? $retry : 0;
            $options = is_array($config['opts']) ? $config['opts'] : [];

            do {
                try {
                    $connection = new \PDO($config['dsn'], $config['user'], $config['pass'], $options);

                    $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                    $connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

                    break;
                } catch (\PDOException $e) {
                    if ($retry <= 0) {
                        throw new \PDOException('Could not connect to DB: ' . $e->getMessage() . '. DSN: ' . $config['dsn']);
                    }
                }

                $retry--;
                usleep(50000);
            } while ($retry >= 0);

            return $connection;
        };
    }

    /**
     * @return bool
     */
    public function isOpenTransaction()
    {
        return $this->openTransaction;
    }

    /**
     * @return \PDO
     */
    public function getActivated()
    {
        return $this->activated;
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return array
     */
    public function getNames()
    {
        return $this->names;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        if ($config) {
            $this->config = $config;

            $this->setCallbacks($this->config);
        }
    }

    /**
     * disconnect
     */
    public function clear()
    {
        $this->activated = null;
        $this->connections = [];
    }

    /**
     * @inheritdoc
     */
    public function __call($method, array $args)
    {
        throw new UnknownMethodException("Call the method $method don't exists!");
    }

    /////////////////////////////////////////////////////////////////////////////////////
    //// basic database operate
    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param $sql
     * @param array $params
     * @return string
     */
    public function insertBySql($sql, array $params = [])
    {
        // trigger before event
        $this->fireEvent(self::BEFORE_EXECUTE, [$sql, 'insert', ['params' => $params]]);

        $db = $this->getWriter();

        if (false === $db->exec($sql)) {
            throw new \RuntimeException('Insert data to the database failed.');
        }

        $id = $db->lastInsertId();

        // trigger after event
        $this->fireEvent(self::AFTER_EXECUTE, [$sql, 'insert', ['id' => $id]]);

        return $id;
    }

    /**
     * @param $sql
     * @param array $params
     * @return int
     */
    public function updateBySql($sql, array $params = [])
    {
        // trigger before event
        $this->fireEvent(self::BEFORE_EXECUTE, [$sql, 'update', ['params' => $params]]);

        if ($params) {
            $st = $this->getWriter()->prepare($sql);
            $st->execute($params);

            $affected = $st->rowCount();
            $st->closeCursor();
        } else {
            $affected = $this->getWriter()->exec($sql);
        }

        // trigger after event
        $this->fireEvent(self::AFTER_EXECUTE, [$sql, 'update', ['affected' => $affected]]);

        return $affected;
    }

    /**
     * @param $sql
     * @param array $params
     * @return int
     */
    public function deleteBySql($sql, array $params = [])
    {
        // trigger before event
        $this->fireEvent(self::BEFORE_EXECUTE, [$sql, 'delete', ['params' => $params]]);

        if ($params) {
            $st = $this->getWriter()->prepare($sql);
            $st->execute($params);

            $affected = $st->rowCount();
            $st->closeCursor();
        } else {
            $affected = $this->getWriter()->exec($sql);
        }

        // trigger after event
        $this->fireEvent(self::AFTER_EXECUTE, [$sql, 'delete', ['affected' => $affected]]);

        return $affected;
    }

    /**
     * @var array
     */
    protected $queryNodes = [
        'select' => '*', // string: 'id, name' array: ['id', 'name']
        'from' => '',

        'join' => '', // [$type, $table, $condition]
        // can also:
        'leftJoin' => '', // [$table, $condition]
        'rightJoin' => '', // [$table, $condition]
        'innerJoin' => '', // [$table, $condition]
        'outerJoin' => '', // [$table, $condition]

        'having' => '', // [$conditions, $glue = 'AND']
        'group' => '', // 'id, type'
        'order' => '', // 'created ASC' OR ['created ASC', 'publish DESC']
        'limit' => 1, // 10 OR [2, 10]
    ];

    protected $queryOptions = [
        /* data index column. */
        'indexKey' => null,

        /*
        data load type, in :
        // 'model'      -- return object, instanceof `BaseModel`
        \stdClass::class -- return object, instanceof `stdClass`
        'array'      -- return array, only  [ column's value ]
        'assoc'      -- return array, Contain  [ column's name => column's value]
         */
        'loadType' => 'assoc',
    ];

    public function byConn($type = 'reader')
    {
        # code...
    }

    /**
     * @param $sql
     * @param array $params
     * @param array $options
     * @return mixed
     */
    public function fetchBySql($sql, array $params = [], array $options = [])
    {
        // trigger event
        $this->fireEvent(self::BEFORE_EXECUTE, [$sql, 'select', [
            'params' => $params,
            'options' => $options,
        ]]);

        $role = self::ROLE_READER;
        if (isset($options['role']) && in_array($options['role'], [self::ROLE_READER, self::ROLE_WRITER], true)) {
            $role = $options['role'];
        }

//        if ( !isset($options['fetch_style']) ) {
        //            $options['fetch_style'] = \PDO::FETCH_ASSOC;
        //        }

        /** @var \PDOStatement $st */
        if ($params) {
            $st = $this->$role()->prepare($sql);
            $st->execute($params);
        } else {
            $st = $this->$role()->query($sql);
        }

        $fStyle = $options['fetch_style'] ?? \PDO::FETCH_ASSOC;
        $ori = $options['ori'] ?? null;
        $offset = $options['offset'] ?? 0;

        $row = $st->fetch($fStyle, $ori, $offset);

        $st->closeCursor();

        // trigger event
        $this->fireEvent(self::AFTER_EXECUTE, [$sql, 'select', [
            'params' => $params,
            'row' => $row,
        ]]);

        return $row;
    }

    /**
     * @param $sql
     * @param array $params
     * @param array $options
     * @return mixed
     */
    public function fetchAllBySql($sql, array $params = [], array $options = [])
    {
        // trigger event
        $this->fireEvent(self::BEFORE_EXECUTE, [$sql, 'select', [
            'params' => $params,
            'options' => $options,
        ]]);

        if (!isset($options['fetch_style'])) {
            $options['fetch_style'] = \PDO::FETCH_ASSOC;
        }

        $role = self::ROLE_READER;
        if (isset($options['role']) && in_array($options['role'], [self::ROLE_READER, self::ROLE_WRITER], true)) {
            $role = $options['role'];
        }

//        $fStyle = isset($options['fetch_style']) ? $options['fetch_style'] : \PDO::FETCH_ASSOC;
        //        $fArg = isset($options['fetch_arg']) ? $options['fetch_arg'] : null;
        //        $ctorArgs = isset($options['ctor_args']) ? $options['ctor_args'] : null;

        /** @var \PDOStatement $st */
        if ($params) {
            $st = $this->$role()->prepare($sql);
            $st->execute($params);
            // $rows = $st->fetchAll($fStyle, $fArg, $ctorArgs);
        } else {
            $st = $this->$role()->query($sql);
            // $st->setFetchMode($fStyle); // , $fArg, $ctorArgs
            // $rows = $st->fetchAll();
        }

        $rows = call_user_func_array([$st, 'fetchAll'], $options);

        $st->closeCursor();

        // trigger event
        $this->fireEvent(self::AFTER_EXECUTE, [$sql, 'select', [
            'params' => $params,
            'rows' => $rows,
        ]]);

        return $rows;
    }

    /**
     * @var \PDOStatement
     */
    private $cursor;

    public function executeBySql($sql, array $params = [], array $options = [])
    {
        $role = self::ROLE_READER;
        if (isset($options['role']) && in_array($options['role'], [self::ROLE_READER, self::ROLE_WRITER], true)) {
            $role = $options['role'];
        }

        if ($params) {
            $this->cursor = $this->$role()->prepare($sql);
            $this->cursor->execute($params);
            // $rows = $this->cursor->fetchAll($fStyle, $fArg, $ctorArgs);
        } else {
            $this->cursor = $this->$role()->query($sql);
            // $this->cursor->setFetchMode($fStyle); // , $fArg, $ctorArgs
            // $rows = $this->cursor->fetchAll();
        }

        return $this;
    }

////////////////////////////////////// Read record //////////////////////////////////////

    public function loadAll($key = null, $class = \stdClass::class)
    {
        if (strtolower($class) === 'array') {
            return $this->loadArrayList($key);
        }

        if (strtolower($class) === 'assoc') {
            return $this->loadAssocList($key);
        }

        return $this->loadObjectList($key, $class);
    }

    /**
     * loadOne
     * @param string $class
     * @return  mixed
     */
    public function loadOne($class = \stdClass::class)
    {
        if (strtolower($class) === 'array') {
            return $this->loadArray();
        }

        if (strtolower($class) === 'assoc') {
            return $this->loadAssoc();
        }

        return $this->loadObject($class);
    }

    /**
     * @return array|bool
     */
    public function loadResult()
    {
        $this->execute();

        // Get the first row from the result set as an array.
        $row = $this->fetchArray();

        if ($row && is_array($row) && isset($row[0])) {
            $row = $row[0];
        }

        // Free up system resources and return.
        $this->freeResult();

        return $row;
    }

    /**
     * @param int $offset
     * @return array
     */
    public function loadColumn($offset = 0)
    {
        $this->execute();
        $array = [];

        // Get all of the rows from the result set as arrays.
        while ($row = $this->fetchArray()) {
            if ($row && is_array($row) && isset($row[$offset])) {
                $array[] = $row[$offset];
            }
        }

        // Free up system resources and return.
        $this->freeResult();

        return $array;
    }

    public function loadArray()
    {
        $this->execute();

        // Get the first row from the result set as an array.
        $array = $this->fetchArray();

        // Free up system resources and return.
        $this->freeResult();

        return $array;
    }

    public function loadArrayList($key = null)
    {
        $this->execute();
        $array = [];

        // Get all of the rows from the result set as arrays.
        while ($row = $this->fetchArray()) {
            if ($key !== null && is_array($row)) {
                $array[$row[$key]] = $row;
            } else {
                $array[] = $row;
            }
        }

        // Free up system resources and return.
        $this->freeResult();

        return $array;
    }

    public function loadAssoc()
    {
        $this->execute();

        // Get the first row from the result set as an associative array.
        $array = $this->fetchAssoc();

        // Free up system resources and return.
        $this->freeResult();

        return $array;
    }

    public function loadAssocList($key = null)
    {
        $this->execute();
        $array = [];

        // Get all of the rows from the result set.
        while ($row = $this->fetchAssoc()) {
            if ($key) {
                $array[$row[$key]] = $row;
            } else {
                $array[] = $row;
            }
        }

        // Free up system resources and return.
        $this->freeResult();

        return $array;
    }

    public function loadObject($class = 'stdClass')
    {
        $this->execute();

        // Get the first row from the result set as an object of type $class.
        $object = $this->fetchObject($class);

        // Free up system resources and return.
        $this->freeResult();

        return $object;
    }

    public function loadObjectList($key = null, $class = \stdClass::class)
    {
        $this->execute();
        $array = [];

        // Get all of the rows from the result set as objects of type $class.
        while ($row = $this->fetchObject($class)) {
            if ($key) {
                $array[$row->$key] = $row;
            } else {
                $array[] = $row;
            }
        }

        // Free up system resources and return.
        $this->freeResult();

        return $array;
    }

    /**
     * @return array|bool
     */
    public function fetchArray()
    {
        return $this->fetch(\PDO::FETCH_NUM);
    }

    /**
     * Method to fetch a row from the result set cursor as an associative array.
     * @return  mixed  Either the next row from the result set or false if there are no more rows.
     */
    public function fetchAssoc()
    {
        return $this->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Method to fetch a row from the result set cursor as an object.
     * @param   string $class Unused, only necessary so method signature will be the same as parent.
     * @return  mixed   Either the next row from the result set or false if there are no more rows.
     */
    public function fetchObject($class = \stdClass::class)
    {
        return $this->getCursor()->fetchObject($class);
    }

    /**
     * fetch
     * @param int $type
     * @param int $ori
     * @param int $offset
     * @see http://php.net/manual/en/pdostatement.fetch.php
     * @return  bool|mixed
     */
    public function fetch($type = \PDO::FETCH_ASSOC, $ori = null, $offset = 0)
    {
        return $this->getCursor()->fetch($type, $ori, $offset);
    }

    /**
     * fetchAll
     * @param int $type
     * @param array $args
     * @param array $ctorArgs
     * @see http://php.net/manual/en/pdostatement.fetchall.php
     * @return  array|bool
     */
    public function fetchAll($type = \PDO::FETCH_ASSOC, $args = null, $ctorArgs = null)
    {
        return $this->getCursor()->fetchAll($type, $args, $ctorArgs);
    }

////////////////////////////////////// transaction method //////////////////////////////////////

    /**
     * Initiates a transaction
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @param bool $throwException throw a exception on failure.
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function beginTransaction($throwException = true)
    {
        return $this->beginTrans($throwException);
    }

    public function beginTrans($throwException = true)
    {
        $this->openTransaction = true;
        $result = $this->getWriter()->beginTransaction();

        if ($throwException && false === $result) {
            // clear
            $this->clearTransaction();

            throw new \RuntimeException('Begin a transaction is failure!!');
        }

        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Commits a transaction
     * @link http://php.net/manual/en/pdo.commit.php
     * @param bool $throwException throw a exception on failure.
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function commit($throwException = true)
    {
        if (!$this->inTransaction()) {
            throw new \LogicException('Transaction must be turned on before committing a transaction!!');
        }

        $result = $this->getWriter()->commit();

        // clear
        $this->clearTransaction();

        if ($throwException && false === $result) {
            throw new \RuntimeException('Committing a transaction is failure!!');
        }

        return $result;
    }

    /**
     * (PHP 5 &gt;= 5.1.0, PECL pdo &gt;= 0.1.0)<br/>
     * Rolls back a transaction
     * @link http://php.net/manual/en/pdo.rollback.php
     * @param bool $throwException throw a exception on failure.
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function rollBack($throwException = true)
    {
        if (!$this->inTransaction()) {
            throw new \LogicException('Transaction must be turned on before rolls back a transaction!!');
        }

        $result = $this->getWriter()->rollBack();

        // clear
        $this->clearTransaction();

        if ($throwException && false === $result) {
            throw new \RuntimeException('Committing a transaction is failure!!');
        }

        return $result;
    }

    /**
     * Check connection is in transaction
     * @return bool
     */
    public function inTransaction()
    {
        return $this->inTrans();
    }

    public function inTrans()
    {
        if (!$this->openTransaction) {
            return false;
        }

        return $this->getWriter()->inTransaction();
    }

    protected function clearTransaction()
    {
        // clear
        $this->openTransaction = false;
    }

/////////////////////////////////////////////////////////////////////////////////////
    //// help method
    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param null|PdoStatement $cursor
     * @return $this
     */
    public function freeResult($cursor = null)
    {
        $cursor = $cursor ?: $this->cursor;

        if ($cursor instanceof \PDOStatement) {
            $cursor->closeCursor();

            $cursor = null;
        }

        return $this;
    }

    /**
     * Get the number of affected rows for the previous executed SQL statement.
     * Only applicable for DELETE, INSERT, or UPDATE statements.
     * @return  integer  The number of affected rows.
     */
    public function countAffected()
    {
        return $this->getCursor()->rowCount();
    }

    /**
     * Method to get the auto-incremented value from the last INSERT statement.
     * @return  string  The value of the auto-increment field from the last inserted row.
     */
    public function insertId()
    {
        // Error suppress this to prevent PDO warning us that the driver doesn't support this operation.
        return @$this->getPdo()->lastInsertId();
    }

/////////////////////////////////////////////////////////////////////////////////////
    //// help method
    /////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param array $data
     * @return string
     */
    public function buildDataSql(array $data)
    {
        $str = '';

        foreach ($data as $field => $value) {
            // 'dtStart = dtStart + 3600'
            if (is_int($field) && $value) {
                $str .= $value;
                continue;
            }

            if (null === $value) {
                $value = 'NULL';
            } else if (is_string($value)) {
                $value = "'$value'";
            }

            $str .= " `$field` = $value,";
        }

        return rtrim($str, ',');
    }

    /**
     * handleConditions
     * @param  string|array $wheres
     * array(
     *     'name' => 'text',
     *     'date >=' => '2017-01-12',
     *     'dtInsert <= concat(CURDATE(),' 10:00:00')'
     * )
     * @return array
     */
    public function handleConditions($wheres)
    {
        $whereArr = $params = [];

        // is string, like 'id = 23'
        if (is_string($wheres)) {
            return [$wheres, $params];
        }

        foreach ((array)$wheres as $key => $value) {
            if (is_int($key)) {
                // concat(CURDATE(),' 10:00:00')

                $whereArr[] = $value;
                continue;
            }

            $key = trim($key);

            // is a 'in|not in' statement. eg: $value like [2,3,5] ['foo', 'bar', 'baz']
            if (is_array($value) || is_object($value)) {
                $value = array_map(function ($val) {
                    return "'$val'";
                }, (array)$value);

                $inWhere = implode(',', $value);

                // check $key exists keyword 'in|not in|IN|NOT IN'
                $where = $key . (1 === preg_match('/ in$/i', $key) ? '' : ' IN') . " ($inWhere)";

                // 'sName LIKE' => 'name'
            } elseif (strpos($key, ' LIKE')) {
                // ERROR: $params[] = $value; $where = "$key '%?%'";
                $params[] = "%$value%";
                $where = "$key ?";
            } else {
                $params[] = $value;
                // check exists operator '<' '>' '<=' '>=' '!='
                $where = $key . (1 === preg_match('/[<>=]/', $key) ? ' ?' : ' = ?');
            }

            // have table name
            // eg: 'mt.field', 'mt.field >='
            if (strpos($where, '.') > 1) {
                $where = preg_replace('/^(\w+)\.(\w+)(.*)$/', '`$1`.`$2`$3', $where);
                // eg: 'field >='
            } elseif (strpos($where, ' ') > 1) {
                $where = preg_replace('/^(\w+)(.*)$/', '`$1`$2', $where);
            }

            $whereArr[] = $where;

        } // end foreach

        return [implode(' AND ', $whereArr), $params];
    }
}
