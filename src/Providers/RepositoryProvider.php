<?php

namespace Thavam\Repositories\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(CorsService::class, function ($app) {
            return new CorsService($this->corsOptions(), $app);
        });
    }
}
