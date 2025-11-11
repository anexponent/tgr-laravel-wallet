<?php

namespace Anexponent\Wallet;

use Illuminate\Support\ServiceProvider;

class WalletServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge package config
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'config');

        // Bind wallet manager as singleton for shared instance
        $this->app->singleton('wallet', function () {
            return new WalletManager();
        });
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('config.php'),
        ], ['wallet-config', 'config']);

        // Publish migrations with timestamp
        $this->publishes([
            __DIR__ . '/../database/migrations/create_wallet_tables.php.stub' => database_path('migrations/' . date('Y_m_d_His') . '_create_wallet_tables.php'),
            __DIR__ . '/../database/migrations/create_wallet_transactions_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', strtotime('+1 second')) . '_create_wallet_transactions_table.php'),
        ], ['wallet-migrations', 'migrations']);

        // Load migrations automatically if enabled in config
        if (config('wallet.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Publish models for customization
        $this->publishes([
            __DIR__ . '/../src/Models/Wallet.php' => app_path('Models/Wallet.php'),
            __DIR__ . '/../src/Models/Transaction.php' => app_path('Models/Transaction.php'),
        ], ['wallet-models', 'models']);
    }
}