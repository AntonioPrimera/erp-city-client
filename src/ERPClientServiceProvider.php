<?php

namespace ERPClient;

use Illuminate\Support\ServiceProvider;

class ERPClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/erp.php', 'erp');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'erp-city-client');
        $this->loadRoutesConditionally();

        $this->publishes([
            __DIR__ . '/../config/erp.php' => config_path('erp.php'),
        ], 'erp-city-client-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/erp-city-client'),
        ], 'erp-city-client-views');
    }

    protected function loadRoutesConditionally(): void
    {
        if (! config('erp.routes.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
