<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:36
 */

namespace Inhere\Database\Connections;

use Inhere\Database\Builders\QueryCompiler;
use Inhere\Library\Traits\LiteEventTrait;

/**
 * Class Connection
 * @package Inhere\Database\Base
 */
abstract class Connection implements PDOInterface
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
        'pass' => '',
        'database' => 'test',
        'charset' => 'utf8',

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
    protected $config = [];

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * All of the queries run against the connection.
     * @var array
     */
    protected $queryLog = [];

    /** @var string */
    protected $tablePrefix;

    /** @var string */
    protected $prefixPlaceholder = '{pfx}';

    /**
     * @var QueryCompiler
     */
    protected $queryCompiler;

    public function __construct($database = '', $tablePrefix = '', array $config = [])
    {

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

    /**
     * @param $sql
     * @return mixed
     */
    public function replaceTablePrefix($sql)
    {
        return str_replace($this->prefixPlaceholder, $this->tablePrefix, (string)$sql);
    }

    /**
     * {@inheritDoc}
     */
    public function transactional($func)
    {
        if (!is_callable($func)) {
            throw new \InvalidArgumentException('Expected argument of type "callable", got "' . gettype($func) . '"');
        }

        $this->conn->beginTransaction();

        try {
            $return = $func($this);
            $this->flush();
            $this->conn->commit();

            return $return ?: true;
        } catch (\Throwable $e) {
            $this->close();
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @param string $category
     */
    public function log(string $message, array $context = [], $category = 'select')
    {
        if ($this->debug) {
            $context['category'] = 'db.' . $category;
            $this->queryLog[] = [microtime(1), $message, $context];
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
    public function getConfig($name = null, $default = null)
    {
        if (!$name) {
            return $this->config;
        }

        return $this->config[$name] ?? $default;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
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
