<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/23
 * Time: 下午7:41
 */

namespace Inhere\Database\Connections\Swoole;


class MysqlConnection
{
    /**
     * If no connection set, we escape it with default function.
     * Since mysql_real_escape_string() has been deprecated, we use an alternative one.
     * Please see: http://stackoverflow.com/questions/4892882/mysql-real-escape-string-for-multibyte-without-a-connection
     * @param string|mixed $text
     * @return  string
     */
    protected function escape($text)
    {
        if (is_int($text) || is_float($text)) {
            return $text;
        }

        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $text
        );
    }

    /**
     * @param string|mixed $text
     * @return mixed|string
     */
    protected function escapeWithNoConnection($text)
    {
        if (is_int($text) || is_float($text)) {
            return $text;
        }

        $text = str_replace("'", "''", $text);

        return addcslashes($text, "\000\n\r\\\032");
    }
}
