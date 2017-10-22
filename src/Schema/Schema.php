<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:58
 * Use : 一个表的架构信息,包含了各个字段列的信息(class Column)
 */

namespace SimpleAR\Schema;

use Inhere\Library\Helpers\Obj;

/**
 * Class Schema
 * @package SimpleAR\Schema
 */
class Schema
{
    /**
     * the columns.
     * @var  Column[]
     */
    public $columns = [];

    /**
     * Property indexes.
     * 一张表可以有多个主键索引
     *  e.g. mysql
     * create table student(
     *      stu_id int, coures_id int, name varchar,
     *      primary key(stu_id,coures_id)
     * );
     *       $define = array(
     *           'type' => 'KEY',
     *           'name' => null,
     *           'columns' => array(),
     *           'comment' => ''
     *       );
     * @var  array
     */
    public $indexes = [];

    /**
     * 一张表可以有多个主键索引
     *  e.g. mysql
     * create table student(stu_id int, cour_id int, name varchar,
     *   primary key(stu_id,cour_id)
     *   );
     * Property priKey, primaryKeys.
     * @var  string[]
     */
    public $priKey = [];

    /**
     * Property foreKey, foreignKeys.
     * @var  array
     */
    public $foreKey = [];

    /**
     * (主键)自动增长从多少开始
     * Property autoIncrement.
     * @var  int
     */
    public $autoIncrement;

    /**
     * Property engine.
     * @var  string
     */
    public $engine;

    /**
     * Property defaultCharset.
     * @var  string
     */
    public $defaultCharset = 'utf8';

    /**
     * Property collation.
     * @var  string
     */
    public $collation = 'utf8_general_ci';

    /**
     * Property comment.
     * @var  string
     */
    public $comment;

    /**
     * @param array $options
     * @return static
     */
    public static function makeByArray(array $options)
    {
        return Obj::init(new static, $options);
    }

    /**
     * {@inheritdoc}
     * @see Schema::__construct()
     */
    public static function make(
        array $columns = [], array $indexes = [], $autoIncrement = null, string $engine = 'InnoDB',
        string $defaultCharset = 'utf8', $collation = 'utf8_general_ci', string $comment = null
    )
    {
        return new static($columns, $indexes, $autoIncrement, $engine, $defaultCharset, $collation, $comment);
    }

    /**
     * @param array $columns
     * @param array $indexes
     * @param integer $autoIncrement
     * @param string $engine
     * @param string $defaultCharset
     * @param string $collation
     * @param string $comment
     */
    public function __construct(
        array $columns = [], array $indexes = [], $autoIncrement = null, string $engine = 'InnoDB',
        string $defaultCharset = 'utf8', $collation = 'utf8_general_ci', string $comment = null
    )
    {
        Obj::init($this, [
            'indexes' => $indexes,
            'columns' => $columns,
            'engine' => $engine,
            'autoIncrement' => $autoIncrement,
            'defaultCharset' => $defaultCharset,
            'collation' => $collation,
            'comment' => $comment,
        ]);
    }

    /**
     * setColumn
     * @param Column $column
     * @return  static
     */
    public function setColumn(Column $column)
    {
        $this->columns[$column->name] = $column;

        return $this;
    }

    /**
     * getColumn
     * @param string $name
     * @return  Column
     */
    public function getColumn($name)
    {
        if (empty($this->columns[$name])) {
            return null;
        }

        return $this->columns[$name];
    }

    /**
     * @return array
     */
    public function getColumnNames()
    {
        return array_keys($this->columns);
    }
}
