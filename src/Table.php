<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-26
 * Time: 11:52
 */

namespace Inhere\Database;

/**
 * Class Table
 * @package Inhere\Database
 */
class Table
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Database
     */
    protected $database;

    /**
     * Table constructor.
     * @param string $name
     * @param Database $database
     */
    public function __construct(string $name, Database $database)
    {
        $this->name = $name;
        $this->database = $database;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function fullName(): string
    {
        return $this->database->getPrefix() . $this->name;
    }

    /**
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

}