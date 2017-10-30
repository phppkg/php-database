<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 10:02
 */

use Inhere\Database\DatabaseManager;
use Inhere\Database\Drivers\MySQL\MySQLConnection;

require __DIR__ . '/simple-load.php';

$config = [
    'debug' => 1,

    'host' => '127.0.0.1',
    'port' => '3306',
    'user' => 'root',
    'password' => 'root',
    'database' => 'mysql',
];
$conn = new MySQLConnection($config);

$rows = $conn->fetchAll('SELECT * FROM  `help_keyword` LIMIT 0 , 10');
$row = $conn->fetchOne('SELECT * FROM  `help_keyword` LIMIT 1');

var_dump($rows, $row, $conn->getQueryLog());

$config['driver'] = MySQLConnection::class;
$dm = new DatabaseManager([
    'connections' => [
        'mydb' => $config
    ]
]);

$conn = $dm->getConnection('mydb');

// help_keyword, name
$rows = $conn->fetchAll('SELECT * FROM  `help_keyword` LIMIT 0 , 10');
$row = $conn->fetchOne('SELECT * FROM  `help_keyword` LIMIT 1');

var_dump($rows, $row, $conn->getQueryLog());
