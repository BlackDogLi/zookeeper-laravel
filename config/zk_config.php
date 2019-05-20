<?php
/**
 * zookeeper_config.
 * User: ly
 * Date: 2019/5/16
 * Time: 18:23
 */

return [
    'host' => env('ZK_HOST', '127.0.0.1:2181'),
    'path' => env('ZK_PATH', ''),
    'version' => env('ZK_VERSION', 'V1.0')
];