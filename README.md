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
        $nodeValue = ZK::getNode('usercenter/userhost');
    }
}

```