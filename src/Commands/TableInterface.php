<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午11:10
 */

namespace Inhere\Database\Commands;

use Inhere\Database\Schema\Column;

/**
 * Interface TableInterface
 * @package Inhere\Database\Commands
 */
interface TableInterface
{
    public function create($ifNotExists = false, array $columns = []);

    public function drop($ifNotExists = false);

    public function isExists();

    public function update();

    public function reset();

    public function rename(string $newName, $returnNew = true);

    public function lock();

    public function unlock();

    public function truncate();

    /**
     * The columns
     */

    /**
     * @param string $name
     * @return Column
     */
    public function addColumn(string $name);

    /**
     * @param string $name
     * @return Column
     */
    public function getColumn(string $name);

    /**
     * @param string $name
     * @return bool
     */
    public function hasColumn(string $name);

    /**
     * @param string $name
     * @param Column $column
     * @return bool
     */
    public function updateColumn(string $name, Column $column);

    /**
     * @param string $name
     * @return bool
     */
    public function dropColumn(string $name);

    public function getColumnNames($toString = false);

    public function getColumnDetails($full = true);

    public function getColumnDetail(string $name, $full = true);
}
