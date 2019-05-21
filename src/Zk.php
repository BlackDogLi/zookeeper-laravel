<?php
/**
 * User: ly
 * Date: 2019/5/16
 * Time: 18:32
 */

namespace Ly\Zookeeper;

use Zookeeper;
use Illuminate\Config\Repository;

class Zk
{
    /**
     * @var Repository
     */
    protected $config;      //config of zookeeper

    /**
     * @var
     */
    protected $zk;          //zookeeper connection

    /**
     * @var
     */
    private  $zkRootPath;   //zookeeper root node

    /**
     * Zk constructor.
     * @param Repository $config
     */
    public function __construct (Repository $config)
    {
        $this->config = $config;
        self::init();
        self::setZkRootPath();
    }

    /**
     * @Desc connection init
     */
    private function init()
    {
        try {
            $this->zk = new Zookeeper($this->config->get('zk_config.host'));
        } catch (\Exception $e) {
            echo 'Zookeeper connected failed';
            exit();
        }
    }

    /**
     * se zkRootPath
     */
    private function setZkRootPath ()
    {
        $this->zkRootPath = $this->config->get('zk_config.path') . DIRECTORY_SEPARATOR . $this->config->get('zk_config.version');
    }

    /**
     * @param string $nodeName
     * @return string
     */
    public function getNode ($nodeName = '')
    {
        $data = '';
        $path = $this->zkRootPath. DIRECTORY_SEPARATOR . trim($nodeName, '/');
        if ($this->zk->exists($path)) {
            $data = rtrim($this->zk->get($path), '/');
        }
        return $data;
    }
}