<?php

namespace Anexponent\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'float',
    ];

    protected static function booted()
    {
        static::creating(function ($wallet) {
            // Ensure default balance
            if (is_null($wallet->balance)) {
                $wallet->balance = 0;
            }
        });
    }

    /**
     * Relationship: All transactions belonging to the wallet.
     */
    public function transactions()
    {
        return $this->hasMany(
            config('wallet.transaction_model', Transaction::class)
        );
    }

    /**
     * Relationship: Wallet owner.
     */
    public function user()
    {
        $model = config('wallet.user_model', \App\Models\User::class);

        return $this->belongsTo($model, 'user_id');
    }

    /**
     * Current wallet balance.
     */
    public function balance(): float
    {
        return $this->balance ?? 0;
    }

    /**
     * Validate amount
     */
    protected function validateAmount(float $amount): void
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new InvalidArgumentException('Amount must be a positive number.');
        }
    }

    /**
     * Credit wallet (add money).
     */
    public function credit(float $amount, string $type = 'credit'): self
    {
        $this->validateAmount($amount);

        DB::transaction(function () use ($amount, $type) {
            // Lock the wallet row for update
            $wallet = self::lockForUpdate()->find($this->id);

            if (!$wallet) {
                throw new \Exception('Wallet not found.');
            }

            $wallet->balance += $amount;
            $wallet->save();

            $wallet->transactions()->create([
                'type' => $type,
                'amount' => $amount,
            ]);
        });

        return $this;
    }

    /**
     * Debit wallet (deduct money).
     *
     * @throws \Exception
     */
    public function debit(float $amount, string $type = 'debit', bool $force = false): self
    {
        $this->validateAmount($amount);

        DB::transaction(function () use ($amount, $type, $force) {
            // Lock the wallet row for update
            $wallet = self::lockForUpdate()->find($this->id);

            if (!$wallet) {
                throw new \Exception('Wallet not found.');
            }

            if (!$force && $wallet->balance < $amount) {
                throw new \Exception('Insufficient balance in wallet.');
            }

            $wallet->balance -= $amount;
            $wallet->save();

            $wallet->transactions()->create([
                'type' => $type,
                'amount' => $amount,
            ]);
        });

        return $this;
    }

    /**
     * Compute actual balance from transactions
     */
    public function actualBalance(): float
    {
        $credits = $this->transactions()
            ->whereIn('type', config('wallet.credit_types', ['credit']))
            ->sum('amount');

        $debits = $this->transactions()
            ->whereIn('type', config('wallet.debit_types', ['debit']))
            ->sum('amount');

        return (float) ($credits - $debits);
    }
}