<?php

namespace Ly\Zookeeper;

use Illuminate\Support\ServiceProvider;


class ZookeeperServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/zk_config.php' => config_path('zk_config.php')
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('zk', function ($app) {
            return new Zk($app['config']);
        });
    }
}
