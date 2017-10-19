<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/1 0001
 * Time: 22:02
 */

namespace SimpleAR\Connections;

use Inhere\Exceptions\UnknownCalledException;

/**
 * Class Singleton
 * @package SimpleAR\Connections
 *
 * ```
 * $client = new Singleton($config);
 * ```
 */
class Singleton extends Connections
{
    const MODE = 'singleton';

    /**
     * @var array
     */
    protected static $defaultConfig = [
        'dsn' => 'mysql:host=localhost;port=3306;dbname=db_name;charset=UTF8',
        'user' => 'root',
        'pass' => '',
        'opts' => [],

        'tblPrefix' => '',

        // retry times.
        'retry' => 0,
    ];

    /**
     * @param null $name
     * @return \PDO
     */
    protected function getConnection($name = null)
    {
        return parent::getConnection(self::MODE);
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        if ($config) {
            $this->config[self::MODE] = array_merge(self::$defaultConfig, $config);

            $this->setCallback(self::MODE);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, array $args)
    {
        $conn = $this->getConnection();

        // exists and enabled
        if (method_exists($conn, $method)) {
            return call_user_func_array([$conn, $method], $args);
        }

        throw new UnknownCalledException("Call the method [$method] don't exists!");
    }
}
