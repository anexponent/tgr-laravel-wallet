<?php

return [

    /**
     * Which model is your User's (must use HasWallet trait or equivalent)
     */
    'user_model' => env('WALLET_USER_MODEL', 'App\Models\User'),

    /**
     * Change this if you extend the default Wallet Model
     */
    'wallet_model' => env('WALLET_MODEL', 'Anexponent\Wallet\Wallet'),

    /**
     * Change this if you extend the default Transaction Model
     */
    'transaction_model' => env('WALLET_TRANSACTION_MODEL', 'Anexponent\Wallet\Transaction'),

    /**
     * Table names (override if needed for custom schema)
     */
    'table_wallets' => 'wallets',
    'table_transactions' => 'wallet_transactions',

    /**
     * Transaction credit types (used in actualBalance and signed amounts)
     */
    'credit_types' => ['deposit', 'refund'],

    /**
     * Transaction debit types (used in actualBalance and signed amounts)
     */
    'debit_types' => ['withdraw', 'payout', 'reverse'],

    /**
     * Automatically load package migrations
     */
    'load_migrations' => true,

    /**
     * Currency precision (e.g., 2 for dollars, 0 for integers like cents)
     */
    'currency_precision' => 2,

    /**
     * Max size for transaction meta (bytes, to prevent DB bloat)
     */
    'max_meta_size' => 65535,

];