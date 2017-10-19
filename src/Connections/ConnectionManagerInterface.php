<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/1 0001
 * Time: 22:14
 */

namespace SimpleAR\Connections;

/**
 * Interface ConnectionInterface
 * @package SimpleAR\Connections
 */
interface ConnectionManagerInterface
{
    // mode: singleton master-slave cluster
    const MODE = '';

    //
    // event lists
    // connect disconnect beforeExecute afterExecute
    //

    //
    const CONNECT = 'connect';
    const DISCONNECT = 'disconnect';

    // connection roles
    const ROLE_READER = 'reader';
    const ROLE_WRITER = 'writer';

    // will provide ($sql, $type, $data)
    // $sql - executed SQL
    // $type - operate type.  e.g 'insert'
    // $data - data
    const BEFORE_EXECUTE = 'beforeExecute';
    const AFTER_EXECUTE = 'afterExecute';

    //
    // Operate types
    //

    const TYPE_SELECT = 'select';
    const TYPE_INSERT = 'insert';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';

//    public function connect();

    /**
     * @param null|string $name
     * @return \PDO
     */
    public function getReader($name = null);

    /**
     * @param null|string $name
     * @return \PDO
     */
    public function getWriter($name = null);

    public function disconnect();

    public function fireEvent($event, array $args = []);

    public static function supportedEvents();
}
