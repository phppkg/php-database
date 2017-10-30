<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-30
 * Time: 16:10
 */

namespace Inhere\Database\Base;

use Inhere\Database\Builders\QueryCompiler;
use Inhere\Database\Connection;

/**
 * Class AbstractBuilder
 * @package Inhere\Database\Base
 */
abstract class AbstractBuilder
{
    /** @var Connection */
    protected $connection;

    /**
     * @var QueryCompiler
     */
    protected $compiler;

    /**
     * constructor.
     * @param Connection $connection
     * @param QueryCompiler|null $compiler
     */
    public function __construct(Connection $connection, QueryCompiler $compiler = null)
    {
        $this->connection = $connection;
        $this->compiler = $compiler ?: $connection->getQueryCompiler();
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return QueryCompiler
     */
    public function getCompiler(): QueryCompiler
    {
        return $this->compiler;
    }

    /**
     * @param QueryCompiler $compiler
     */
    public function setCompiler(QueryCompiler $compiler)
    {
        $this->compiler = $compiler;
    }

}