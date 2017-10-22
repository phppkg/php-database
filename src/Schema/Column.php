<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/22
 * Time: 上午10:50
 */

namespace SimpleAR\Schema;

use Inhere\Library\Helpers\Obj;

/**
 * Class Column
 * @package SimpleAR\Schema
 */
class Column
{
    const SIGNED = true;
    const UNSIGNED = false;

    const ALLOW_NULL = true;
    const NOT_NULL = false;

    // types for $type
    const STRING	= 1;
    const INTEGER	= 2;
    const DECIMAL	= 3;
    const DATETIME	= 4;
    const DATE		= 5;
    const TIME		= 6;

    /**
     * Map a type to an column type.
     * @static
     * @var array
     */
    const TYPE_MAPPING = [
        'datetime'	=> self::DATETIME,
        'timestamp'	=> self::DATETIME,
        'date'		=> self::DATE,
        'time'		=> self::TIME,
        'tinyint'	=> self::INTEGER,
        'smallint'	=> self::INTEGER,
        'mediumint'	=> self::INTEGER,
        'int'		=> self::INTEGER,
        //'integer'	=> self::INTEGER,
        'bigint'	=> self::INTEGER,
        'float'		=> self::DECIMAL,
        'double'	=> self::DECIMAL,
        'numeric'	=> self::DECIMAL,
        'decimal'	=> self::DECIMAL,
        'dec'		=> self::DECIMAL
    ];

    /**
     * 主键索引
     * mark is primary Key.
     * @var boolean
     */
    private $priKey = false;

    /** @var bool mark is foreign key */
    private $foreKey = false;

    /** @var string The name. */
    public $name;

    /** @var string The data type. */
    public $type;

    /** @var int The length. */
    public $length;

    /** @var bool Mark is signed. */
    public $signed = false;

    /** @var bool Mark is auto-increment. */
    public $autoIncrement = false;

    /** @var string The collation. */
    public $collation;

    /** @var bool Mark is allow Null */
    public $allowNull = false;

    /** @var string The default value */
    public $default;

    /** @var string The comment. */
    public $comment;

    /**
     * 索引
     * The key.
     * @var string
     */
    public $key;

    /**
     * 权限
     * The privilege.
     * @var  string
     */
    public $privilege;

    /** @var int The position in the table */
    public $position;

    /**
     * @param array $props
     * @param null|int $position
     */
    public function __construct(array $props = [], $position = null)
    {
        Obj::init($this, $props);

        $this->position = $position;
    }

    /**
     * @param bool $priKey
     * @return Column
     */
    public function setPriKey($priKey): Column
    {
        if (null === $priKey) {
            return $this;
        }

        if ($this->priKey = (bool)$priKey) {
            // $this->signed        = false;
            $this->allowNull = false;
            $this->autoIncrement = true;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isPriKey(): bool
    {
        return $this->priKey;
    }

    /**
     * @return bool
     */
    public function isForeKey(): bool
    {
        return $this->foreKey;
    }

    /**
     * @param bool $foreKey
     */
    public function setForeKey($foreKey)
    {
        $this->foreKey = (bool)$foreKey;
    }
}
