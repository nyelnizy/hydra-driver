<?php

namespace Hardcorp\HydraClient;

use Hardcorp\HydraClient\Repository\MessageRepository;
use Hardcorp\HydraClient\Repository\HydraMessageRepository;
use Illuminate\Support\ServiceProvider;

class HydraClientServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/hydra-client.php', 'hydra-client');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishConfig();

        $config =  \Config::get('hydra-client::hydra-client.hydra_db');
        \Config::set('database.connections.hydra',$config??\Config::get('hydra-client.hydra_db'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register facade
        $this->app->bind(MessageRepository::class,HydraMessageRepository::class);
        $this->app->singleton('hydra-client', function ($app) {
            return $app->make(HydraClient::class);
        });
    }

    /**
     * Publish Config
     *
     * @return void
     */
    public function publishConfig()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/hydra-client.php' => config_path('hydra-client.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../src/Commands/ConnectToHydraServer.php' => app_path('Console/Commands/ConnectToHydraServer.php'),
            ]);
            $this->publishes([
                __DIR__ . '/../database/migrations/2021_10_16_062214_create_sms_chats_table.php' => database_path('migrations/2021_10_16_062214_create_sms_chats_table.php'),
            ]);
        }
    }
}
