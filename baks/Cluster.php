<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/1 0001
 * Time: 22:02
 */

namespace SimpleAR\Connections;

use Inhere\Exceptions\UnknownMethodException;

/**
 * Class Cluster
 * @package SimpleAR\Connections
 *
 * ```
 * $config = [
 *     'name1' => [
 *         'dsn'  => 'mysql:host=localhost;port=3306;dbname=db_name;charset=UTF8',
 *         'user' => 'root',
 *         'pass' => '',
 *         'opts' => []
 *     ],
 *     'name2' => [],
 *     ...
 * ];
 * $client = new Cluster($config);
 * ```
 *
 */
class Cluster extends Connections
{
    const MODE = 'cluster';

    /**
     * The fixed writer connection. when `$openTransaction = true`
     * @var \PDO
     */
    protected $fixedWriter;

    /**
     * @inheritdoc
     */
    public function getWriter($name = null)
    {
        // in Transaction, return the fixed connection
        if ($this->fixedWriter) {
            return $this->fixedWriter;
        }

        $writer = parent::getWriter($name);

        // When open Transaction, will fixed the writer until close the transaction(commit/rollback).
        if ($this->openTransaction) {
            $this->fixedWriter = $writer;
        }

        return $writer;
    }

    protected function clearTransaction()
    {
        // clear
        $this->openTransaction = false;
        $this->fixedWriter = null;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, array $args)
    {
        $conn = $this->getConnection();

        // exists and enabled
        if (method_exists($conn, $method)) {
            return \call_user_func_array([$conn, $method], $args);
        }

        throw new UnknownMethodException("Call the method [$method] don't exists!");
    }
}
