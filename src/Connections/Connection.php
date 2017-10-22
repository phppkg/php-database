<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:36
 */

namespace SimpleAR\Connections;

use Inhere\Library\Traits\LiteEventTrait;
use SimpleAR\Query\Grammars\DefaultGrammar;

/**
 * Class Connection
 * @package SimpleAR\Base
 */
abstract class Connection implements PdoInterface
{
    use LiteEventTrait;

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

        'tblPrefix' => '',

        // retry times.
        'retry' => 0,
    ];

    public static $supportedEvents = [
        self::CONNECT, self::DISCONNECT, self::BEFORE_EXECUTE, self::AFTER_EXECUTE
    ];

    protected $config = [];

    /**
     * All of the queries run against the connection.
     *
     * @var array
     */
    protected $queryLog = [];

    /** @var string  */
    protected $tablePrefix;

    /**
     * @var DefaultGrammar
     */
    protected $queryGrammar;

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
}
