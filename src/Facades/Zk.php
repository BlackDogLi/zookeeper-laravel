<?php
/**
 * User: ly
 * Date: 2019/5/17
 * Time: 10:22
 */

namespace Ly\Zookeeper\Facades;


use Illuminate\Support\Facades\Facade;

class Zk extends Facade
{
    protected static function getFacadeAccessor ()
    {
        return 'zk';
    }

}