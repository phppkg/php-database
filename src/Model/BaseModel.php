<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2016/2/19 0019
 * Time: 23:35
 */

namespace SimpleAR\Model;

use Inhere\Library\Collections\SimpleCollection;
use Inhere\Library\Types;
use Inhere\Validate\ValidationTrait;

/**
 * Class BaseModel
 * @package slimExt
 */
abstract class BaseModel extends SimpleCollection
{
    use ValidationTrait;

    /**
     * @var bool
     */
    protected $enableValidate = true;

    /**
     * if true, will only save(insert/update) safe's data -- Through validation's data
     * @var bool
     */
    protected $onlySaveSafeData = true;

    /**
     * Validation class name
     */
    //protected $validateHandler = '\inhere\validate\Validation';

    /**
     * @var array
     */
    private static $_models = [];

    /**
     * @param string $class
     * @return static
     */
    public static function model($class = '')
    {
        $class = $class ?: static::class;

        if (!isset(self::$_models[$class]) || !(self::$_models[$class] instanceof self)) {
            $model = new $class;

            if (!($model instanceof self)) {
                throw new \RuntimeException("The model class [$class] must instanceof " . self::class);
            }

            self::$_models[$class] = $model;
        }

        return self::$_models[$class];
    }

    /**
     * @param $data
     * @return static
     */
    public static function load($data)
    {
        return new static($data);
    }

    /**
     * define model field list
     * in sub class:
     * ```
     * public function columns()
     * {
     *    return [
     *          // column => type
     *          'id'          => 'int',
     *          'title'       => 'string',
     *          'createTime'  => 'int',
     *    ];
     * }
     * ```
     * @return array
     */
    abstract public function columns();

    /**
     * {@inheritDoc}
     */
    public function translates()
    {
        return [
            // 'field' => 'translate',
            // e.g. 'name'=>'名称',
        ];
    }

    /**
     * format column's data type
     * @param $column
     * @param $value
     * @return SimpleCollection
     */
    public function set($column, $value)
    {
        // belong to the model.
        if (isset($this->columns()[$column])) {
            $type = $this->columns()[$column];

            if ($type === Types::T_INT) {
                $value = (int)$value;
            }
        }

        return parent::set($column, $value);
    }

    /**
     * @return array
     */
    public function getColumnsData()
    {
        $source = $this->onlySaveSafeData ? $this->getSafeData() : $this;
        $data = [];

        foreach ($source as $col => $val) {
            if (isset($this->columns()[$col])) {
                $data[$col] = $val;
            }
        }

        return $data;
    }
}
