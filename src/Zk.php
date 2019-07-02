<?php
/**
 * User: ly
 * Date: 2019/5/16
 * Time: 18:32
 */

namespace Ly\Zookeeper;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Ly\Zookeeper\Exceptions\ErrorInfoException;
use Ly\Zookeeper\Exceptions\FrameworkNotSupportException;
use Zookeeper;
use Illuminate\Config\Repository;

class Zk
{
    
    /**
     * Container
     * @var \Illuminate\Contracts\Container\Container;
     */
    protected $container;

    /**
     * @var string
     */
    protected $framework;

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
    protected  $zkRootPath;   //zookeeper root node

    /**
     * @var
     */
    protected $data;        //zookeeper node and nodeValue

    /**
     * @var
     */
    protected $file;

    /**
     * Zk constructor.
     * @param Container $container
     * @param string $framework
     * @param array $config
     * @throws \Exception
     */
    public function __construct (Container $container, $framework, $config)
    {
        $this->container = $container;
         $aa = $this->container->make('config');
        $this->setFramework($framework);
        $this->setConfig($config);
        $this->init();
    }

    /**
     * @Desc get Zk node
     */
    public function run ()
    {
        $this->getNode($this->zkRootPath);
    }

    /**
     * @Desc set Framework
     *
     * @param string $framework
     *
     * @throws \Exception
     */
    protected function setFramework ($framework)
    {
        $framework = strtolower($framework);

        if (! in_array($framework, ['laravel', 'lumen'])) {
            throw new FrameworkNotSupportException($framework);
        }

        $this->framework = $framework;
    }

    /**
     * @Desc set config
     *
     * @param array $config
     *
     * @throws \Exception
     */
    protected function setConfig ($config)
    {
        if (!is_array($config)) {
            throw new ErrorInfoException('Zk config need array');
        }
        $this->config = $config;
    }

    /**
     * @Desc return zookeeper config.
     * @return Repository
     */
    public function getConfig ()
    {
        return $this->config;
    }
    /**
     * @Desc connection init
     */
    protected function init()
    {
        //Set Zookeeper Rootpath
        $this->setZkRootPath();

        try {
            $this->zk = new Zookeeper($this->config['host']);
        } catch (\Exception $e) {
            echo 'Zookeeper connected failed';
            exit();
        }
    }

    /**
     * @Desc 监控回调事件{连接事件 节点事件 子节点事件}
     * @param $eventType
     * @param $connectionState
     * @param $path
     */
    public function watch($eventType, $connectionState, $path)
    {
        switch ($eventType) {
            case Zookeeper::CREATED_EVENT:
                // 1 数据监控返回,节点创建,需要watch一个不存在的节点,通过exists监控,通过create操作触发
            case Zookeeper::DELETED_EVENT:
                // 2 数据监控返回,节点删除,通过 exists 和 get 监控,通过 delete 操作触发
            case Zookeeper::CHANGED_EVENT:
                // 3 数据监控返回, 节点数据改变, 通过 exists 和 get 监控, 通过set操作触发
                $this->getNodeValue($path);
                break;
            case Zookeeper::CHILD_EVENT:
                // 4 节点监控返回,通过 getchild 监控, 通过子节点的 delete 和 create 操作触发
                $this->getNode($path);
                break;
            case Zookeeper::SESSION_EVENT:
                // -1 会话监控返回,客户端与服务端断开或重连时触发
                if (3 == $connectionState) {
                    $this->getNode($this->zkRootPath);
                }
                break;
            case Zookeeper::NOTWATCHING_EVENT:
                // -2 watch移除事,服务端不再回调客户端
            default:
        }
    }

    /**
     * @Desc get the node
     * @param $root
     */
    protected function getNode($path)
    {
        if ($this->zk->exists($path)) {
            $nodes = $this->zk->getChildren($path, [$this, 'watch']);
            if (empty($nodes)) {
                $this->getNodeValue($path);
            } else {
                foreach ($nodes as $node) {
                    $this->getNode($path . DIRECTORY_SEPARATOR . $node);
                }
            }
        }
    }
    /**
     * @Desc get nodeValue and cache it
     * @param $nodePath
     */
    protected function getNodeValue($nodePath)
    {
        $node = str_replace($this->zkRootPath . DIRECTORY_SEPARATOR,'', $nodePath);

        if ($this->zk->exists($nodePath)) {
            $stat = [];
            $nodeValue = $this->zk->get($nodePath, [$this, 'watch'], $stat);
            $this->cacheNodeVulue($node, $nodeValue);
        } else {
            $this->dropNode ($node);
        }
    }

    /**
     *
     * @param $node
     * @param $nodeValue
     */
    protected function cacheNodeVulue ($node, $nodeValue)
    {
        $node = trim(str_replace('/', '.', $node));
        $this->data[$node] = $nodeValue;
        $this->cacheData();
    }

    /**
     * @Desc drop the from CaceConfig
     * @param $node
     */
    protected function dropNode ($node)
    {
        $data = require $this->config['cache'] . '/config.php';
        if (isset($data[$node])) {
            unset ($data[$node]);
        }
        $this->file->put($this->config['cache'] . '/config.php', '<?php return '. var_export($data, true).';'.PHP_EOL);
    }
    /**
     * @Desc Cache the data
     */
    protected function cacheData ()
    {
        $this->file = $this->container->make('files');
        if (! $this->file->isDirectory($this->config['cache'])) {
            $this->file->makeDirectory($this->config['cache']);
        }

        $this->file->put($this->config['cache'] . '/config.php', '<?php return '. var_export($this->data, true).';'.PHP_EOL);
    }


    /**
     * @Desc return zookeeper data
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Set zkRootPath
     */
    protected function setZkRootPath ()
    {
        $this->zkRootPath = $this->config['path'] . DIRECTORY_SEPARATOR . $this->config['version'];
    }

    /**
     * @param string $nodeName
     * @return string
     */
    public function getConf ($nodeName = '')
    {
        $data = '';
        $path = $this->zkRootPath. DIRECTORY_SEPARATOR . trim($nodeName, '/');
        if ($this->zk->exists($path)) {
            $data = rtrim($this->zk->get($path), '/');
        }
        return $data;
    }
}