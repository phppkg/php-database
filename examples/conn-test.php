<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 10:02
 */

use Inhere\Database\Drivers\MySQL\MySQLConnection;

require __DIR__ . '/simple-load.php';

$conn = new MySQLConnection([
    'debug' => 1,

    'host' => '127.0.0.1',
    'port' => '3306',
    'user' => 'root',
    'password' => 'password',
    'database' => 'y2_admin',
]);

$rows = $conn->fetchOne('show tables');

var_dump($rows);
