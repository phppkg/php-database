<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/28
 * Time: 下午10:36
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

$inhereDir = dirname(__DIR__, 2);

require $inhereDir . '/library/src/exceptions.php';
require $inhereDir . '/library/src/functions.php';

spl_autoload_register(function($class) use ($inhereDir)
{

    $vendorDir = dirname(__DIR__, 3);
    $map = [
        'Inhere\Database\\' => dirname(__DIR__) . '/src',
        'Inhere\Library\\' => $inhereDir . '/library/src',
        'Psr\Log\\' => $vendorDir . '/psr/log/Psr/Log',
    ];

    foreach ($map as $np => $dir) {
        if (0 === strpos($class, $np)) {
            $path = str_replace('\\', '/', substr($class, strlen($np)));
            $file = $dir . "/{$path}.php";

            if (is_file($file)) {
                include_file($file);
            }
        }
    }
});

function include_file($file) {
    include $file;
}
