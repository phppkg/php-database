<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:36
 */

namespace Inhere\Database;

use Inhere\Database\Base\ConnectionInterface;
use Inhere\Database\Builders\Expression;
use Inhere\Database\Builders\QueryCompiler;
use Inhere\Database\Helpers\DetectConnectionLostTrait;
use Inhere\Library\Traits\LiteEventTrait;

/**
 * Class Connection
 * @package Inhere\Database
 */
abstract class Connection implements ConnectionInterface
{
    use LiteEventTrait, DetectConnectionLostTrait;

    //
    const CONNECT = 'connect';
    const DISCONNECT = 'disconnect';

    // will provide ($sql, $type, $data)
    // $sql - executed SQL
    // $type - operate type.  e.g 'insert'
    // $data - data
    const BEFORE_EXECUTE = 'beforeExecute';
    const AFTER_EXECUTE = 'afterExecute';

    /**
     * @var array
     */
    const DEFAULT_CONFIG = [
        // 'dsn' => 'mysql:host=localhost;port=3306;dbname=db_name;charset=UTF8',
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'password' => '',
        'database' => 'test',
        'charset' => 'utf8',

        'timeout' => 0,
        'timezone' => null,
        'collation' => 'utf8_unicode_ci',

        'options' => [],

        'tablePrefix' => '',

        // retry times.
        'retry' => 0,
    ];

    public static $supportedEvents = [
        self::CONNECT, self::DISCONNECT, self::BEFORE_EXECUTE, self::AFTER_EXECUTE
    ];

    /** @var array */
    protected $options = [
        'driver' => 'mysql',

        'debug' => false,

        'host' => 'localhost',
        'port' => '3306',
        'user' => 'root',
        'password' => '',
        'database' => 'test',

        'tablePrefix' => '',
        'charset' => 'utf8',
        'timezone' => null,
        'collation' => 'utf8_unicode_ci',

        'options' => [],

        // retry times.
        'retry' => 0,
    ];

    /** @var bool */
    protected $debug = false;

    /** @var string */
    protected $databaseName;

    /** @var string */
    protected $tablePrefix;

    /** @var string */
    protected $prefixPlaceholder = '{pfx}';

    /**
     * @var QueryCompiler
     */
    protected $queryCompiler;

    /**
     * All of the queries run against the connection.
     * @var array
     * [
     *  [time, category, message, context],
     *  ... ...
     * ]
     */
    protected $queryLog = [];

//    public function __construct($database = '', $tablePrefix = '', array $options)
    public function __construct(array $options)
    {
        $this->setOptions($options);

        $this->debug = (bool)$this->options['debug'];
        $this->tablePrefix = $this->options['tablePrefix'];
        $this->databaseName = $this->options['database'];

        $this->useDefaultQueryCompiler();
    }

    /**
     * Set the query compiler to the default implementation.
     * @return void
     */
    public function useDefaultQueryCompiler()
    {
        $this->queryCompiler = $this->getDefaultQueryCompiler();
    }

    /**
     * Get the default query compiler instance.
     * @return QueryCompiler
     */
    protected function getDefaultQueryCompiler()
    {
        return new QueryCompiler;
    }

    /**
     * connect
     */
    abstract public function connect();

    /**
     * disconnect
     */
    public function disconnect()
    {
        $this->fire(self::DISCONNECT, [$this]);
    }

    /**
     * Check whether the connection is available
     * @return bool
     */
    abstract public function ping();

    /**
     * @return bool
     */
    abstract public function isConnected(): bool;


    /********************************************************************************
     * basic command methods
     *******************************************************************************/

    /**
     * Run a select statement
     * @param  string $statement
     * @param  array $bindings
     * @return array
     */
    public function select($statement, array $bindings = [])
    {
        return $this->fetchAll($statement, $bindings);
    }

    /**
     * Run a insert statement
     * @param  string $statement
     * @param  array $bindings
     * @param null|string $sequence For special driver, like PgSQL
     * @return int
     */
    public function insert($statement, array $bindings = [], $sequence = null)
    {
        $this->fetchAffected($statement, $bindings);

        return $this->lastInsertId($sequence);
    }

    /**
     * Run a update statement
     * @param  string $statement
     * @param  array $bindings
     * @return int
     */
    public function update($statement, array $bindings = [])
    {
        return $this->fetchAffected($statement, $bindings);
    }

    /**
     * Run a delete statement
     * @param  string $statement
     * @param  array $bindings
     * @return int
     */
    public function delete($statement, array $bindings = [])
    {
        return $this->fetchAffected($statement, $bindings);
    }

    /********************************************************************************
     * helper methods
     *******************************************************************************/

    /**
     * Get a new raw query expression.
     * @param  mixed $value
     * @return Expression
     */
    public function raw($value)
    {
        return new Expression($value);
    }

    public function database()
    {
        //
    }

    public function table($name)
    {
        //
    }

    public function tableSchema()
    {
        //
    }

    /********************************************************************************
     * getter/setter methods
     *******************************************************************************/

    /**
     * Get the name of the connected database.
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * Set the name of the connected database.
     * @param  string $database
     */
    public function setDatabaseName($database)
    {
        $this->databaseName = $database;
    }

    /**
     * Get the table prefix for the connection.
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
     * @param  string $prefix
     * @return void
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        $this->getQueryCompiler()->setTablePrefix($prefix);
    }

    /**
     * Set the table prefix and return the grammar.
     * @param  QueryCompiler $compiler
     * @return QueryCompiler
     */
    public function withTablePrefix(QueryCompiler $compiler)
    {
        $compiler->setTablePrefix($this->tablePrefix);

        return $compiler;
    }

    /**
     * @param $sql
     * @return mixed
     */
    public function replaceTablePrefix($sql)
    {
        return str_replace($this->prefixPlaceholder, $this->tablePrefix, (string)$sql);
    }

    /**
     * @param string $message
     * @param array $context
     * @param string $category
     */
    public function log(string $message, array $context = [], $category = 'query')
    {
        if ($this->debug) {
            $this->queryLog[] = [microtime(1), 'db.' . $category, $message, $context];
        }
    }

    /**
     * @return array
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return array|mixed
     */
    public function getOptions($name = null, $default = null)
    {
        if (!$name) {
            return $this->options;
        }

        return $this->options[$name] ?? $default;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param QueryCompiler $queryCompiler
     */
    public function setQueryCompiler(QueryCompiler $queryCompiler)
    {
        $this->queryCompiler = $queryCompiler;
    }

    /**
     * @return QueryCompiler
     */
    public function getQueryCompiler(): QueryCompiler
    {
        return $this->queryCompiler;
    }

}
