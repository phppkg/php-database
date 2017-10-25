<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午11:33
 */

namespace Inhere\Database\Helpers;


class DbHelper
{
    /**
     * @param string $name
     * @param string $quoteChar
     * @return array
     */
    public static function resolveName(string $name, $quoteChar = '`')
    {
        $parts = explode('.', str_replace($quoteChar, '', $name), 2);

        if (isset($parts[1])) {
            return $parts;
        }

        return [null, $name];
    }
}
