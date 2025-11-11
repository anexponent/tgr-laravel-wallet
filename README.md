# Laravel Wallet

A modern, simple, and flexible virtual wallet system for Laravel.

This package allows you to attach a wallet to any model (usually a User), deposit and withdraw credits, and keep a full transaction history. Ideal for apps where users buy credits or virtual currency to spend on services or goods.

## Features
Assign a wallet to any model (User, Member, etc.)
Track deposits, withdrawals, refunds, and custom transaction types
Transaction metadata for payment references or descriptions
Safe balance updates using database transactions
Force withdrawals or failed deposits
Configurable models for Wallet, Transaction, and User
Supports Laravel 9, 10, and 11

## Installation

Install the package with composer:

```bash
composer require anexponent/laravel-wallet
```
The package automatically merges default configuration, so you can start using it immediately.

## Run Migrations

```bash
php artisan migrate

```
Publish the migrations with this artisan command:

Optional: You can publish the migrations if you want to customize them:

```bash
php artisan vendor:publish --provider="Anexponent\Wallet\WalletServiceProvider" --tag=migrations

```

## Configuration

Default configuration is already merged automatically. The defaults are:
```php
    return [
        'user_model' => App\Models\User::class,
        'wallet_model' => Anexponent\Wallet\Wallet::class,
        'transaction_model' => Anexponent\Wallet\Transaction::class,
    ];
```

Optional: You can publish the config to customize it:

```bash
php artisan vendor:publish --provider="Anexponent\Wallet\WalletServiceProvider" --tag=config

```

This will merge the `wallet.php` config file where you can specify the Users, Wallets & Transactions classes if you have custom ones.

## Usage

Add the `HasWallet` trait to your User model.

```php

use Anexponent\Wallet\HasWallet;

class User extends Model
{
    use HasWallet;

    ...
}
```

At some point before making transactions, create the user's wallet.

```php
$user->wallet()->firstOrCreate([]);
```

Then you can easily make transactions from your user model.

```php
$user = User::find(1);
$user->balance; // 0

$user->deposit(100);
$user->balance; // 100

$user->withdraw(50);
$user->balance; // 50

$user->forceWithdraw(200);
$user->balance; // -150
```

You can easily add meta information to the transactions to suit your needs.

```php
$user = User::find(1);
$user->deposit(100, 'deposit', ['stripe_source' => 'ch_BEV2Iih1yzbf4G3HNsfOQ07h', 'description' => 'Deposit of 100 credits from Stripe Payment']);
$user->withdraw(10, 'withdraw', ['description' => 'Purchase of Item #1234']);
```
## Transactions
Retrieve all transactions:

```php 
$transactions = $user->transactions;

```
Get signed amount 

```php
$transaction->signed_amount;
```
Each transaction includes:
    amount
    type
    accepted
    meta (array)
    balance (after transaction)
    hash (unique ID)

### Security

If you discover any security related issues, please email simon@webartisan.be instead of using the issue tracker.

## Credits
- [Azeez Adesina](https://github.com/anexponent)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
