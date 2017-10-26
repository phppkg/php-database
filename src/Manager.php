<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 下午6:21
 */

namespace Inhere\Database;

use Inhere\Database\Builders\QueryBuilder;
use Inhere\Database\Builders\SchemaBuilder;
use Inhere\Library\DI\Container;

/**
 * $mgr = new Manager;
 * $mr->setDefault(function () {
 *     return DbFactory::getDbo([
 *         'dsn' => 'mysql:host=default.db.localhost;dbname=database',
 *         'user' => 'username',
 *         'pwd' => 'password'
 *     ]);
 * });
 */
class Manager
{
    const READER = 'reader';
    const WRITER = 'writer';

    /**
     * @var self
     */
    public static $self;

    /**
     * The database manager instance.
     * @var DatabaseManager
     */
    protected $manager;

    /**
     * @var Container
     */
    private $container;

    /**
     * constructor.
     * @param array $config
     * @param Container|null $container
     */
    public function __construct(array $config, Container $container = null)
    {
        $this->setupContainer($container ?: new Container);
        $this->setupManager($config);
    }

    /**
     * Build the database manager instance.
     * @param array $config
     * @return void
     */
    protected function setupManager(array $config)
    {
//        $factory = new ConnectionFactory($this->container);

        $this->manager = new DatabaseManager($config, $this->container);
    }

    protected function setupContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param null $name
     * @return Connection
     */
    public function getConnection($name = null)
    {
        return $this->manager->getConnection($name);
    }

    /**
     * Get a connection instance from the global manager.
     *
     * @param  string  $connection
     * @return Connection
     */
    public static function connection($connection = null)
    {
        return static::$self->getConnection($connection);
    }

    /**
     * @param $name
     * @param null $connection
     * @return Database
     */
    public static function database($name, $connection = null)
    {
        return static::$self->getConnection($connection)->database();
    }

    /**
     * Get a fluent query builder instance.
     *
     * @param  string  $table
     * @param  string  $connection
     * @return Table
     */
    public static function table($table, $connection = null)
    {
        return static::$self->getConnection($connection)->table($table);
    }

    /**
     * Get a schema builder instance.
     *
     * @param  string  $connection
     * @return SchemaBuilder
     */
    public static function schema($connection = null)
    {
        return static::$self->getConnection($connection)->getSchemaBuilder();
    }

    public function newQuery()
    {
//        return new QueryBuilder();
    }

    public function newNativeQuery()
    {

    }


}
