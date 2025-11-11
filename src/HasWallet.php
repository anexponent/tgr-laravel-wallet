<?php

namespace Anexponent\Wallet;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Support\Facades\DB;

trait HasWallet
{
    /**
     * Accessor for the wallet balance
     */
    public function getBalanceAttribute(): float
    {
        return $this->wallet?->balance ?? 0.0;
    }

    /**
     * Wallet relationship
     */
    public function wallet(): HasOne
    {
        $walletModel = config('wallet.wallet_model', Wallet::class);

        return $this->hasOne($walletModel)->withDefault([
            'balance' => 0.0,
        ]);
    }

    /**
     * Transactions relationship
     */
    public function transactions(): HasManyThrough
    {
        $walletModel = config('wallet.wallet_model', Wallet::class);
        $transactionModel = config('wallet.transaction_model', Transaction::class);

        return $this->hasManyThrough($transactionModel, $walletModel)->latest();
    }

    /**
     * Validate amount
     */
    protected function validateAmount(float|int $amount): void
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException('Amount must be a positive number.');
        }
    }

    /**
     * Check if user can withdraw
     */
    public function canWithdraw(float|int $amount): bool
    {
        $this->validateAmount($amount);

        return $this->balance >= $amount;
    }

    /**
     * Deposit funds into wallet
     */
    public function deposit(float|int $amount, string $type = 'deposit', array $meta = [], bool $accepted = true)
    {
        $this->validateAmount($amount);

        DB::transaction(function () use ($amount, $type, $meta, $accepted) {
            // Load wallet with lock to prevent race conditions
            $wallet = $this->wallet()->lockForUpdate()->first();

            if (!$wallet) {
                // Create and save the wallet if it doesn't exist
                $wallet = $this->wallet()->create(['balance' => 0.0]);
                // Re-lock after creation (though in transaction, it's safe)
                $wallet = $this->wallet()->lockForUpdate()->first();
            }

            if ($accepted) {
                $wallet->increment('balance', $amount);
            }

            $wallet->transactions()->create([
                'amount' => $amount,
                'hash' => (string) \Illuminate\Support\Str::uuid(),
                'type' => $type,
                'accepted' => $accepted,
                'meta' => $meta,
                'balance' => $wallet->balance,
            ]);
        });
    }

    /**
     * Fail a deposit
     */
    public function failDeposit(float|int $amount, string $type = 'deposit', array $meta = []): void
    {
        $this->deposit($amount, $type, $meta, false);
    }

    /**
     * Withdraw funds
     */
    public function withdraw(float|int $amount, string $type = 'withdraw', array $meta = [], bool $accepted = true, bool $force = false)
    {
        $this->validateAmount($amount);

        DB::transaction(function () use ($amount, $type, $meta, $accepted, $force) {
            // Load wallet with lock to prevent race conditions
            $wallet = $this->wallet()->lockForUpdate()->first();

            if (!$wallet) {
                // Create and save the wallet if it doesn't exist
                $wallet = $this->wallet()->create(['balance' => 0.0]);
                // Re-lock after creation
                $wallet = $this->wallet()->lockForUpdate()->first();
            }

            if ($accepted) {
                if (!$force && !$this->canWithdraw($amount)) {
                    throw new RuntimeException('Insufficient balance for withdrawal.');
                }
                $wallet->decrement('balance', $amount);
            }

            $wallet->transactions()->create([
                'amount' => $amount,
                'hash' => (string) \Illuminate\Support\Str::uuid(),
                'type' => $type,
                'accepted' => $accepted,
                'meta' => $meta,
                'balance' => $wallet->balance,
            ]);
        });
    }

    /**
     * Force withdrawal (ignore balance check, allow negative)
     */
    public function forceWithdraw(float|int $amount, string $type = 'withdraw', array $meta = []): void
    {
        $this->withdraw($amount, $type, $meta, true, true);
    }

    /**
     * Log failed withdrawal (without affecting balance)
     */
    public function failWithdraw(float|int $amount, string $type = 'withdraw', array $meta = []): void
    {
        $this->withdraw($amount, $type, $meta, false);
    }

    /**
     * Compute actual balance from transactions
     */
    public function actualBalance(): float
    {
        $wallet = $this->wallet;

        // Optionally lock for read consistency, but not necessary for computation
        $credits = $wallet->transactions()
            ->whereIn('type', config('wallet.credit_types', ['deposit', 'refund']))
            ->where('accepted', true)
            ->sum('amount');

        $debits = $wallet->transactions()
            ->whereIn('type', config('wallet.debit_types', ['withdraw', 'payout', 'reverse']))
            ->where('accepted', true)
            ->sum('amount');

        return (float) ($credits - $debits);
    }
}