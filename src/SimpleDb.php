<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/1 0001
 * Time: 21:35
 */

namespace micro\db;

/**
 * Class SimpleDb
 * @package micro
 */
class SimpleDb
{
    // mode: singleton master-slave cluster
    const MODE_SINGLETON    = 1;
    const MODE_MASTER_SLAVE = 2;
    const MODE_CLUSTER      = 3;

    /**
     * createClient
     * @param  array   $config
     * @return AbstractClient
     */
    public static function createClient(array $config)
    {
        $mode = self::MODE_SINGLETON;

        if ( isset($config['mode']) ) {
            $mode = self::isSupportedMode($config['mode']) ? $config['mode'] : self::MODE_SINGLETON;
            unset($config['mode']);
        }

        if ($mode === self::MODE_CLUSTER) {
            return new ClusterClient($config);
        } elseif ($mode === self::MODE_MASTER_SLAVE) {
            return new MasterSlaveClient($config);
        }

        return new SingletonClient($config);
    }

    /**
     * @param $mode
     * @return bool
     */
    public static function isSupportedMode($mode)
    {
        return in_array($mode, [self::MODE_SINGLETON, self::MODE_MASTER_SLAVE, self::MODE_CLUSTER], true);
    }

}
