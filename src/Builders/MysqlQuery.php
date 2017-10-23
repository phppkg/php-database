<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 下午9:17
 */

namespace SimpleAR\Builders;


class MysqlQuery extends BaseQuery
{
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
