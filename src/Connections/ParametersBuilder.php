<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/23
 * Time: 下午7:36
 */

namespace SimpleAR\Connections;


class ParametersBuilder
{
    /**
     *
     * Prepares the sub-parts of a query with placeholders.
     *
     * @param array $subs The query subparts.
     *
     * @return string The prepared subparts.
     *
     */
    protected function prepareValuePlaceholders(array $subs)
    {
        $str = '';

        foreach ($subs as $i => $sub) {
            if ($sub[0] === '?') {
                $str .= $this->preparePlaceholderBinding($sub);
            } elseif ($sub[0] === ':') {
                $str .= $this->prepareNamedBinding($sub);
            } else {
                $str .= $sub;
            }
        }

        return $str;
    }

    protected function preparePlaceholderBinding($sub)
    {
        return '';
    }

    protected function prepareNamedBinding($sub)
    {
        return '';
    }
}
