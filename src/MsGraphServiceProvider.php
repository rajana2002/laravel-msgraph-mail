<?php

namespace Mail\MsGraphMail;

use Illuminate\Support\ServiceProvider;

class MsGraphServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/msgraph.php', 'msgraph');
        $this->app->singleton(GraphMailService::class, fn () => new GraphMailService());
        $this->app->alias(GraphMailService::class, 'msgraph.mailer');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/msgraph.php' => config_path('msgraph.php'),
        ], 'msgraph-config');
    }
}
