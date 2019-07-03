# zookeeper-laravel
zookeeper for laravel^5.6.*

## Installation

`composer require zookeeper/laravel-zookeeper`

If using Laravel, add the Service Provider to the `providers` array in `config/app.php`:
``` php
    [
        'providers' => [
            Ly\Zookeeper\ZookeeperServiceProvider::class,
        ],   
    ]
```

If using Lumen, appending the following line to `bootstrap/app.php`:

``` php
    $app->register(Ly\Zookeeper\ZookeeperServiceProvider::class);
```

If you need use Laravel Facades, add the `aliases` array in `config/app.php`:
``` php
    [
        'aliases' => [
                'Zk' => Ly\Zookeeper\Facades\Zk::class,
        ],
    ]
```

## Using
```$xslt
//example
<?php
use Ly\Zookeeper\Facades\Zk;

class TesxtController extends controller {

    public function test ()
    {
        $nodeValue = ZK::getNodeData('usercenter/userhost');
    }
}

```

## Version 1.1.0 

- zk_config add cache path

    `cache => storage_path('zookeeper')`

- add the Commands

| Command | Description |
| ------- | --------- |
| start |Start Zookeeper Server, watch the node and nodeValue |
| cache |Start Zookeeper, Cache the node and nodeValue to the file |

Now,you can run the following command to start Zookeeper

`$ php artisan zookeeper:server start`

`$ php artisan zookeeper:server cache`

Notice: The zookeeper data cached `storage/zookeeper/config.php`;


