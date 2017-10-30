<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 11:59
 */

namespace Inhere\Database;

use Inhere\Library\Collections\Collection;
use Inhere\Library\DI\Container;
use Inhere\Library\Helpers\Arr;

/**
 * Class DatabaseManager
 * @package Inhere\Database
 */
class DatabaseManager
{
    /**
     * @var array
     */
    private $config = [
        'default' => 'default',
        'aliases' => [
//            'default'  => 'mySql',
//            'db'       => 'mySql',
        ],
        'connections' => [
//            'mySql'     => [
//                'driver'     => Drivers\MySQL\MySQLConnection::class,
//                'debug'  => false,
//                'host' => '127.0.0.1',
//                'port' => 3306,
//                'database'   => 'db name',
//                'username'   => 'username',
//                'password'   => 'password',
//                'options'    => []
//            ],
//            'mySql2'     => [
//                'driver'     => Drivers\MySQL\MySQLConnection::class,
//                'debug'  => false,
//                'host' => '127.0.0.2',
//                'port' => 3306,
//                'database'   => 'db name',
//                'username'   => 'username',
//                'password'   => 'password',
//                'options'    => []
//            ],
        ],
    ];

    /**
     * @var Connection[]
     */
    private $connections = [];

    /**
     * @var Container
     */
    private $container;

    /**
     * DatabaseManager constructor.
     * @param array $config
     * @param Container $container
     */
    public function __construct(array $config, Container $container = null)
    {
        $this->config = array_merge($this->config, $config);
        $this->container = $container;
    }

    /**
     * @param string|null $name The connection name
     * @return Connection
     */
    public function connection(string $name = null)
    {
        return $this->getConnection($name);
    }

    /**
     * @param string|null $name The connection name
     * @return Connection
     */
    public function getConnection(string $name = null)
    {
        if (!$name) {
            $name = $this->config['default'];
        }

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $name = $this->resolveAlias($name);

        if (!$config = $this->getConnectionConfig($name)) {
            throw new DatabaseException("Unable to create connection, no presets for '{$name}' found");
        }

        if (!$class = Arr::remove($config, 'driver')) {
            throw new \LogicException("No driver class setting for the connection: '{$name}'");
        }

        $this->connections[$name] = new $class($config);

        return $this->connections[$name];
    }

    /**
     * @param string $name
     * @return string
     */
    public function resolveAlias(string $name)
    {
        return $this->config['aliases'][$name] ?? $name;
    }

    /**
     * @param string $name
     * @param string|null $key Connection id/name.
     * @param null|mixed $default
     * @return string|array
     */
    public function getConnectionConfig(string $name, string $key = null, $default = null): string
    {
        if ($key === null) {
            return $this->config['connections'][$name] ?? $default;
        }

        return $this->config['connections'][$name][$key] ?? $default;
    }

    /**
     * @param string $connection
     * @return bool
     */
    public function hasConnection(string $connection): bool
    {
        return isset($this->config['connections'][$connection]);
    }

    /**
     * @return array
     */
    public function getConnectionNames(): array
    {
        return array_keys($this->config['connections']);
    }

    /**
     * @return Connection[]
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * @return Collection
     */
    public function getConfig(): Collection
    {
        return $this->config;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}