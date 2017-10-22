<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/1 0001
 * Time: 22:02
 */

namespace SimpleAR\Connections;

/**
 * Class MasterSlave
 * @package SimpleAR\Connections
 *
 * ```
 * $config = [
 *     'master' => [
 *         'dsn'  => 'mysql:host=localhost;port=3306;dbname=db_name;charset=UTF8',
 *         'user' => 'root',
 *         'pass' => '',
 *         'opts' => []
 *     ],
 *     'slaves' => [
 *         'slave1' => [
 *              'dsn'  => 'mysql:host=localhost;port=3307;dbname=db_name;charset=UTF8',
 *              'user' => 'root',
 *              'pass' => '',
 *              'opts' => []
 *         ],
 *         'slave2' => [],
 *         'slave3' => [],
 *         ...
 *     ],
 * ];
 *
 * $client = new MasterSlave($config);
 * ```
 *
 */
class MasterSlave extends Connections
{
    const MODE = 'master-slave';

    const TYPE_WRITER = 'writer';
    const TYPE_READER = 'reader';

    /**
     * @var array
     */
    protected $typeNames = [
        'writer' => [
            // 'master'
        ],
        'reader' => [
            // 'slave1','slave2',
        ],
    ];

    /**
     * @var array
     */
    // protected $names = [
    // 'writer.master' => true,// has been connected
    // 'reader.slave1' => false,
    // ];

    /**
     * connection callback list
     * @var array
     */
    // protected $callbacks = [
    // 'writer.master' => function(){},
    // 'reader.slave1' => function(){},
    // ];

    /**
     * instanced connections
     * @var \PDO[]
     */
    // protected $connections = [
    // 'writer.master' => Object (\PDO),
    // ];

    /**
     * @var array
     */
    // protected $config = [
    // 'writer.master' => [],
    // 'reader.slave1' => [],
    // 'reader.slave2' => [],
    // ];

    /**
     * @inheritdoc
     */
    public function setConfig(array $config)
    {
        if ($config) {

            // Compatible
            if (isset($config['master'])) {
                $this->config['writer.master'] = $config['master'];
            }

            if (isset($config['writers']) && is_array($config['writers'])) {
                foreach ($config['writers'] as $name => $conf) {
                    $this->config['writer.' . $name] = $conf;
                }
            }

            // Compatible
            if (isset($config['slaves'])) {
                foreach ($config['slaves'] as $name => $conf) {
                    $this->config['reader.' . $name] = $conf;
                }
            }

            if (isset($config['readers'])) {
                foreach ($config['readers'] as $name => $conf) {
                    $this->config['reader.' . $name] = $conf;
                }
            }

            // create callbacks
            $this->setCallbacks($this->config);
        }
    }

    /**
     * @inheritdoc
     */
    protected function setCallback($name)
    {
        list($type, $rawName) = explode('.', $name, 2);

        $this->typeNames[$type][] = $rawName;

        parent::setCallback($name);
    }

    /**
     * @return \PDO
     */
    public function master()
    {
        return $this->getWriter('master');
    }

    /**
     * @param null $name
     * @return \PDO
     */
    public function slave($name = null)
    {
        return $this->getReader($name);
    }

    /**
     * @param null $name
     * @return \PDO
     */
    public function getReader($name = null)
    {
        if (!($typeNames = $this->typeNames[self::TYPE_READER])) {
            throw new \RuntimeException('Without any reader(slave) database config!');
        }

        // return a random connection
        if (null === $name) {
            $key = array_rand($typeNames);
            $name = $typeNames[$key];
        }

        return $this->getConnection('reader.' . $name);
    }

    /**
     * @param null|string $name
     * @return \PDO
     */
    public function getWriter($name = null)
    {
        if (null === $name) {
            $name = 'master';
        }

        return $this->getConnection('writer.' . $name);
    }

    /**
     * getConnection
     * @param  string $name
     * @return \PDO
     */
    protected function getConnection($name = null)
    {
        if (!$name || strpos($name, '.') <= 0) {
            throw new \RuntimeException('Connection name don\'t allow empty or format error.');
        }

        // list($type, $rawName) = explode('.', $name, 2);

        return parent::getConnection($name);
    }
}
