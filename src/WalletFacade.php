<?php

namespace Anexponent\Wallet;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Anexponent\Wallet\WalletManager
 *
 * @method static float balance($owner = null)
 * @method static bool canWithdraw(float|int $amount, $owner = null)
 * @method static \Anexponent\Wallet\Transaction deposit(float|int $amount, string $type = 'deposit', array $meta = [], bool $accepted = true, $owner = null)
 * @method static void failDeposit(float|int $amount, string $type = 'deposit', array $meta = [], $owner = null)
 * @method static \Anexponent\Wallet\Transaction withdraw(float|int $amount, string $type = 'withdraw', array $meta = [], bool $accepted = true, bool $force = false, $owner = null)
 * @method static void forceWithdraw(float|int $amount, string $type = 'withdraw', array $meta = [], $owner = null)
 * @method static void failWithdraw(float|int $amount, string $type = 'withdraw', array $meta = [], $owner = null)
 * @method static \Anexponent\Wallet\Transaction transfer($from, $to, float|int $amount, array $meta = [], bool $force = false)
 * @method static float actualBalance($owner = null)
 */
class WalletFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'wallet';
    }
}