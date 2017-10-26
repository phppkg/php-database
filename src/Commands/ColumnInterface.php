<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 8:52
 */

namespace Inhere\Database\Commands;

/**
 * Interface ColumnInterface
 * @package Inhere\Database\Commands
 */
interface ColumnInterface
{
    /**
     * @see ColumnInterface::create()
     * {@inheritdoc}
     * @return mixed
     */
    public function add();

    public function create();

    public function update();

    /**
     * @see ColumnInterface::delete()
     * {@inheritdoc}
     * @return mixed
     */
    public function drop();

    public function delete();

    public function alter();
}