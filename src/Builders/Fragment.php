<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-23
 * Time: 17:24
 */

namespace Inhere\Database\Builders;

/**
 * Class Fragment
 * @package Inhere\Database\Builders
 */
class Fragment
{
    /**
     * @var string  The name of the element.
     */
    protected $name;

    /**
     * @var array  An array of data.
     */
    protected $data;

    /**
     * @var string  Glue piece.
     */
    protected $glue;

    /**
     * Constructor.
     * @param   string $name The name of the element.
     * @param   mixed $data String or array.
     * @param   string $glue The glue for data.
     */
    public function __construct($name, $data, $glue = ',')
    {
        $this->data = [];
        $this->name = $name;
        $this->glue = $glue;

        $this->append($data);
    }

    /**
     * Magic function to convert the query element to a string.
     * @return  string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * toString
     * @return  string
     */
    public function toString()
    {
        // '()' 'in ()'
        if (substr($this->name, -2) === '()') {
            return ' ' . substr($this->name, 0, -2) . '(' . implode($this->glue, $this->data) . ')';
        }

        return ' ' . $this->name . ' ' . implode($this->glue, $this->data);
    }

    /**
     * Appends element parts to the internal list.
     * @param   mixed $data String or array.
     * @return  void
     */
    public function append($data)
    {
        if (is_array($data)) {
            $this->data = array_merge($this->data, $data);
        } else {
            $this->data = array_merge($this->data, [$data]);
        }
    }

    /**
     * Gets the data of this element.
     * @return  array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Method to provide deep copy support to nested objects and arrays when cloning.
     * @return  void
     */
    public function __clone()
    {
        foreach (get_object_vars($this) as $k => $v) {
            if (is_object($v) || is_array($v)) {
                $this->{$k} = unserialize(serialize($v), []);
            }
        }
    }

    /**
     * Method to get property Glue
     * @return  string
     */
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * Method to set property glue
     * @param   string $glue
     * @return  static  Return self to support chaining.
     */
    public function setGlue($glue)
    {
        $this->glue = $glue;

        return $this;
    }

    /**
     * Method to get property Name
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Method to set property name
     * @param   string $name
     * @return  static  Return self to support chaining.
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}