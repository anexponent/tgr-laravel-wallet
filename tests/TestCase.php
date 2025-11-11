<?php

namespace Anexponent\Wallet\Tests;

use Anexponent\Wallet\WalletServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        // Run the migrations for wallet and transactions
        $this->setUpDatabase($this->app);
    }

    /**
     * Load the package service provider.
     */
    protected function getPackageProviders($app)
    {
        return [
            WalletServiceProvider::class,
        ];
    }

    /**
     * Environment setup
     */
    protected function getEnvironmentSetUp($app)
    {
        // Use in-memory SQLite for tests
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Optionally set wallet config
        $app['config']->set('wallet.user_model', User::class);
        $app['config']->set('wallet.wallet_model', \Anexponent\Wallet\Wallet::class);
        $app['config']->set('wallet.transaction_model', \Anexponent\Wallet\Transaction::class);
    }

    /**
     * Create tables for testing.
     */
    protected function setUpDatabase($app)
    {
        // Users table
        $app['db']->connection()->getSchemaBuilder()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        // Wallets table
        $app['db']->connection()->getSchemaBuilder()->create('wallets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->unique();
            $table->float('balance')->default(0);
            $table->timestamps();
        });

        // Wallet transactions table
        $app['db']->connection()->getSchemaBuilder()->create('wallet_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('wallet_id');
            $table->float('amount');
            $table->string('type');
            $table->boolean('accepted')->default(true);
            $table->json('meta')->nullable();
            $table->float('balance');
            $table->string('hash')->unique();
            $table->timestamps();
        });
    }
}
