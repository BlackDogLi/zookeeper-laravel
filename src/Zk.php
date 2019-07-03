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
        $this->container->make('config');
        $this->setFramework($framework);
        $this->setConfig($config);
        $this->setZkRootPath();
    }

    /**
     * @Desc get Zk node
     */
    public function run ()
    {
        $this->init();
    }

    /**
     * @Desc cache
     */
    public function cache ()
    {
        $this->init(false);
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
     * @param bool $isWatch
     */
    protected function init($isWatch = true)
    {
        try {
            if (! $isWatch) {
                $this->zk = new Zookeeper($this->config['host']);
            } else {
                $this->zk = new Zookeeper($this->config['host'],[$this,'watch'], 10000);
            }
        } catch (\Exception $e) {
            echo 'Zookeeper connected failed';
            exit();
        }
    }

    /**
     * @Desc Monitor callback events{connection nodeEvent childNodeEvent}
     * @param $eventType
     * @param $connectionState
     * @param $path
     */
    public function watch($eventType, $connectionState, $path)
    {
        switch ($eventType) {
            case Zookeeper::CREATED_EVENT:
                // 1 create node event 
            case Zookeeper::DELETED_EVENT:
                // 2 delete node event
            case Zookeeper::CHANGED_EVENT:
                // 3 change the nodeValue
                $this->getNodeValue($path);
                break;
            case Zookeeper::CHILD_EVENT:
                // 4 watch the child create or delete event
                $this->getNode($path);
                break;
            case Zookeeper::SESSION_EVENT:
                // -1 client disconnects or reconnect
                if (3 == $connectionState) {
                    $this->getNode($this->zkRootPath);
                }
                break;
            case Zookeeper::NOTWATCHING_EVENT:
                // -2 remove the watch event
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
        $node = trim(str_replace('/', '.', str_replace($this->zkRootPath . DIRECTORY_SEPARATOR,'', $nodePath)));
        if ($this->zk->exists($nodePath)) {
            $stat = [];
            $nodeValue = $this->zk->get($nodePath, [$this, 'watch'], $stat);
            $this->data[$node] = $nodeValue;
        } else {
            if(isset ($this->data[$node])) {
                unset ($this->data[$node]);
            }
        }

        $this->cacheData();
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
    public function getNodeData ($nodeName = '')
    {
        $this->init(false);

        $data = '';
        $path = $this->zkRootPath. DIRECTORY_SEPARATOR . trim($nodeName, '/');
        if ($this->zk->exists($path)) {
            $data = rtrim($this->zk->get($path), '/');
        }
        return $data;
    }
}