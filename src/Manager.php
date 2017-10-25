<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 下午6:21
 */

namespace Inhere\Database;

use Inhere\Database\Connections\Connection;

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
     * @var \Closure|Connection
     */
    private $default;

    /**
     * @var array
     */
    private $readers = [];

    /**
     * @var array
     */
    private $writers = [];

    /**
     * ConnectionLocator constructor.
     * @param callable|null $default
     * @param array $readers
     * @param array $writers
     */
    public function __construct(callable $default = null, array $readers = [], array $writers = [])
    {
        if ($default) {
            $this->setDefault($default);
        }

        foreach ($readers as $name => $reader) {
            $this->setReader($name, $reader);
        }

        foreach ($writers as $name => $writer) {
            $this->setWriter($name, $writer);
        }
    }

    public function newQuery()
    {

    }

    public function newNativeQuery()
    {

    }

    /**
     * get default connection instance
     * @return Connection
     */
    public function getDefault()
    {
        if ($this->default instanceof \Closure) {
            $this->default = ($this->default)();
        }

        return $this->default;
    }

    /**
     * @param \Closure $default
     */
    public function setDefault(\Closure $default)
    {
        $this->default = $default;
    }

    /**
     * set Writer
     * @param string $name
     * @param callable|\Closure $cb
     */
    public function setWriter($name, \Closure $cb)
    {
        $this->writers[$name] = $cb;
    }

    /**
     * get Writer
     * @param  string $name
     * @return Connection
     */
    public function getWriter($name = null)
    {
        return $this->getConnection(self::WRITER, $name);
    }

    /**
     * get master Writer
     * @return Connection
     */
    public function getMaster()
    {
        return $this->getConnection(self::WRITER, 'master');
    }

    /**
     * [setReader
     * @param string $name
     * @param callable|\Closure $cb
     */
    public function setReader($name, \Closure $cb)
    {
        $this->readers[$name] = $cb;
    }

    /**
     * get Reader
     * @param  string $name
     * @return Connection
     */
    public function getReader($name = null)
    {
        return $this->getConnection(self::READER, $name);
    }


    /**
     * @param array $readers
     */
    public function setReaders(array $readers)
    {
        foreach ($readers as $name => $cb) {
            $this->setReader($name, $cb);
        }
    }

    /**
     * @return array
     */
    public function getReaders(): array
    {
        return $this->readers;
    }

    /**
     * @return array
     */
    public function getWriters(): array
    {
        return $this->writers;
    }

    /**
     * @param array $writers
     */
    public function setWriters(array $writers)
    {
        foreach ($writers as $name => $cb) {
            $this->setWriter($name, $cb);
        }
    }

    /**
     * getConnection
     * @param  string $type
     * @param  string $name
     * @return Connection
     */
    protected function getConnection($type, $name)
    {
        // no reader/writer, return default
        if (!in_array($type, [self::WRITER, self::READER], true)) {
            return $this->getDefault();
        }

        if ($type === self::READER) {
            $connections = &$this->readers;
        } else {
            $connections = &$this->writers;
        }

        if (!$name) {
            // return a random key
            $name = array_rand($connections);
        }

        if (!isset($connections[$name])) {
            throw new \InvalidArgumentException("The connection '{$type}.{$name}' is not exists!");
        }

        // if not be instanced.
        if ($connections[$name] instanceof \Closure) {
            $connections[$name] = $connections[$name]();
        }

        return $connections[$name];
    }
}
