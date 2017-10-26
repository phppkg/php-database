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

]);

$rows = $conn->fetchAll('show tables limit 10');

var_dump($rows);