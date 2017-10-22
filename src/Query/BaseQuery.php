<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:35
 */

namespace SimpleAR\Base;

/**
 * Class BaseQuery
 * @package SimpleAR\Base
 */
class BaseQuery
{
    /**
     * If no connection set, we escape it with default function.
     *
     * Since mysql_real_escape_string() has been deprecated, we use an alternative one.
     * Please see: http://stackoverflow.com/questions/4892882/mysql-real-escape-string-for-multibyte-without-a-connection
     *
     * @param string $text
     *
     * @return  string
     */
    protected function escapeWithNoConnection($text)
    {
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $text
        );
    }
}
