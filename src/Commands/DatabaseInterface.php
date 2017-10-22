<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午11:07
 */

namespace SimpleAR\Commands;

/**
 * Interface DatabaseInterface
 * @package SimpleAR\Commands
 */
interface DatabaseInterface
{
    public function create($isNotExists = false, $charset = null, $collate = null);

    public function drop($ifNotExists = false);

    public function isExists();

    public function setCharset($charset='utf8');

    public function hasTable(string $name);

    /**
     * @param string $name
     * @return TableInterface
     */
    public function getTable(string $name);

    public function getTableNames($refresh=false);
}
