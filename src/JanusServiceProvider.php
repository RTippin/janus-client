<?php

namespace RTippin\Janus;

use Illuminate\Support\ServiceProvider;

class JanusServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/janus.php' => config_path('janus.php'),
            ], 'janus');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/janus.php', 'janus');

        $this->app->bind(Janus::class, Janus::class);
        $this->app->alias(Janus::class, 'janus');
    }
}
