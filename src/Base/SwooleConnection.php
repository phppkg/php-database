<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-11-02
 * Time: 16:37
 */

namespace Inhere\Database\Connections;

/**
 * Class SwooleConnection
 * @package Inhere\Database\Connections
 */
class SwooleConnection
{
    /**
     * @var array
     */
    protected $options = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'database' => 'test',

        'timeout' => 0,
        'charset' => 'utf8',
    ];

    /**
     * SwooleConnection constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }


    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }
}