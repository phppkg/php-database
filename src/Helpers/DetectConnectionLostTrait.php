<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/24
 * Time: 下午11:51
 */

namespace Inhere\Database\Helpers;

use Inhere\Library\Helpers\Str;

/**
 * Trait DetectsLostConnections
 * @package Inhere\Database\Connections
 */
trait DetectConnectionLostTrait
{
    /**
     * Determine if the given exception was caused by a lost connection.
     * @param  \Throwable $e
     * @return bool
     */
    protected function causedByLostConnection(\Throwable $e)
    {
        return Str::contains($e->getMessage(), [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
        ]);
    }
}

