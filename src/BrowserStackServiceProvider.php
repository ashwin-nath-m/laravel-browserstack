<?php

namespace ChinLeung\BrowserStack;

use Illuminate\Support\ServiceProvider;

class BrowserStackServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('browserstack.php'),
            ], 'config');
        }
    }
}
