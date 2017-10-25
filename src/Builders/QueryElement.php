<?php
/**
 * @from Windwalker
 */

namespace Inhere\Database\Builders;

/**
 * Class QueryElement
 * @package Inhere\Database\Builders
 * ```php
 * echo new QueryElement('WHERE', array('a = b', 'c = d'), ' OR '); // WHERE a = b OR c = d
 * echo new QueryElement('()', array('a = b', 'c = d'), ' OR '); //  (a = b OR c = d)
 * echo new QueryElement('IN()', array(1, 2, 3, 4)); // IN(1, 2, 3, 4)
 * ```
 */
class QueryElement
{
    /**
     * @var string  The name of the element.
     */
    protected $name;

    /**
     * @var array  An array of elements.
     */
    protected $elements;

    /**
     * @var string  Glue piece.
     */
    protected $glue;

    /**
     * Constructor.
     * @param   string $name The name of the element.
     * @param   mixed $elements String or array.
     * @param   string $glue The glue for elements.
     */
    public function __construct($name, $elements, $glue = ',')
    {
        $this->elements = [];
        $this->name = $name;
        $this->glue = $glue;

        $this->append($elements);
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
            return ' ' . substr($this->name, 0, -2) . '(' . implode($this->glue, $this->elements) . ')';
        }

        return ' ' . $this->name . ' ' . implode($this->glue, $this->elements);
    }

    /**
     * Appends element parts to the internal list.
     * @param   mixed $elements String or array.
     * @return  void
     */
    public function append($elements)
    {
        if (is_array($elements)) {
            $this->elements = array_merge($this->elements, $elements);
        } else {
            $this->elements = array_merge($this->elements, [$elements]);
        }
    }

    /**
     * Gets the elements of this element.
     * @return  array
     */
    public function getElements()
    {
        return $this->elements;
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
