<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/3/2 0002
 * Time: 22:33
 * @referrer https://github.com/auraphp/Aura.Sql
 */

namespace Inhere\Database;

use Inhere\Database\Database\AbstractDriver;

/**
 * $connections = new ConnectionLocator;
 * $connections->setDefault(function () {
 *     return DbFactory::getDbo([
 *         'dsn' => 'mysql:host=default.db.localhost;dbname=database',
 *         'user' => 'username',
 *         'pwd' => 'password'
 *     ]);
 * });
 */
class ConnectionLocator
{
    const READER = 'reader';
    const WRITER = 'writer';

    /**
     * @var \Closure|AbstractDriver|null
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

    /**
     * get default connection instance
     * @return AbstractDriver
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
     * @return AbstractDriver
     */
    public function getWriter($name = null)
    {
        return $this->getConnection(self::WRITER, $name);
    }

    /**
     * get master Writer
     * @return AbstractDriver
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
     * @return AbstractDriver
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
     * @return AbstractDriver
     */
    protected function getConnection($type, $name)
    {
        // no reader/writer, return default
        if (!\in_array($type, [self::WRITER, self::READER], true)) {
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
