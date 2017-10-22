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

namespace SimpleAR\Schema;

/**
 * Class Index
 * @package SimpleAR\Schema
 */
class Index
{
    /**
     * @var string
     */
    const UNIQUE = 'unique';

    /**
     * @var string
     */
    const INDEX = 'index';

    /**
     * @var string
     */
    const PRIMARY = 'primary';

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
