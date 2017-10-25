<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:56
 * Use : 索引键信息
 * KEY ('id','gid')
 *   array(
 *       'type'     => 'KEY',
 *       'name'     => null,
 *       'columns'  => array('id','gid'),
 *       'comment'  => 'this a comment'
 *    );
 */

namespace Inhere\Database\Schema;

/**
 * Class Index
 * @package Inhere\Database\Schema
 */
class Index
{
    const UNIQUE = 'unique';

    const INDEX = 'index';

    const PRIMARY = 'primary';

    const FULLTEXT = 'fulltext';

    /**
     * the index type.
     * @var string
     */
    public $type;

    /**
     * the index  name.
     * @var string
     */
    public $name;

    /**
     * the index columns. 当前类型的索引键 有哪些列(字段)
     * @var array
     */
    public $columns = [];

    /**
     * the index comment.
     * @var string
     */
    public $comment;

}
