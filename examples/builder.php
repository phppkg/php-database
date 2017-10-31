<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 10:02
 */

use Inhere\Database\Builders\InsertQuery;
use Inhere\Database\Builders\UpdateQuery;
use Inhere\Database\DatabaseManager;
use Inhere\Database\Drivers\MySQL\MySQLConnection;

require __DIR__ . '/simple-load.php';

$config = [
    'debug' => 1,
    'driver' => MySQLConnection::class,

    'host' => '127.0.0.1',
    'port' => '3306',
    'user' => 'root',
    'password' => 'root',
    'database' => 'test',
];

$dm = new DatabaseManager([
    'connections' => [
        'mydb' => $config
    ]
]);

$conn = $dm->getConnection('mydb');

$iq = new InsertQuery($conn);
$iq->into('user')->values([
    'username' => 'new-name',
    'nickname' => 'my-nick',
    'createdAt' => time(),
]);
pr('- insert SQL:', $iq->toSql(), $iq->getBindings());

$uq = new UpdateQuery($conn);

$uq->table('user')->values(['username' => 'new-name'])->where('id', '=', 2);

pr('- update SQL:', $uq->toSql(), $uq->getBindings());