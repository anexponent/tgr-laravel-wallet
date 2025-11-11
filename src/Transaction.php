<?php

namespace Anexponent\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Transaction extends Model
{
    protected $table;

    protected $fillable = [
        'wallet_id',
        'amount',
        'hash',
        'type',
        'accepted',
        'meta',
        'balance',
    ];

    protected $casts = [
        'amount' => 'float',
        'balance' => 'float',
        'accepted' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($txn) {
            if (empty($txn->hash)) {
                $txn->hash = (string) Str::uuid();
            }

            // Validate amount and type on creation
            $txn->validateAttributes();
        });
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('wallet.table_transactions', 'wallet_transactions');
    }

    /**
     * Validate model attributes
     */
    protected function validateAttributes(): void
    {
        if (!is_numeric($this->amount) || $this->amount <= 0) {
            throw new InvalidArgumentException('Amount must be a positive number.');
        }

        $allowedTypes = array_merge(
            config('wallet.credit_types', ['deposit', 'refund']),
            config('wallet.debit_types', ['withdraw', 'payout', 'reverse'])
        );

        if (!in_array($this->type, $allowedTypes)) {
            throw new InvalidArgumentException('Invalid transaction type.');
        }

        // Optional: Limit meta size (e.g., JSON encoded length)
        if (strlen(json_encode($this->meta)) > 65535) { // Example limit
            throw new InvalidArgumentException('Meta data too large.');
        }
    }

    /**
     * Relationship: the wallet this transaction belongs to
     */
    public function wallet()
    {
        return $this->belongsTo(config('wallet.wallet_model', Wallet::class));
    }

    /**
     * Get signed amount (+ for credits, - for debits)
     */
    public function getSignedAmountAttribute(): float
    {
        $creditTypes = config('wallet.credit_types', ['deposit', 'refund']);
        return in_array($this->type, $creditTypes) ? $this->amount : -$this->amount;
    }

    /**
     * Recalculate wallet balance up to a given date
     */
    public function actualBalance(string $date = null): float
    {
        $date = $date ?: now()->toDateTimeString(); // Use full datetime for precision

        $wallet = $this->wallet;

        if (! $wallet) {
            return 0.0;
        }

        // For consistency in concurrent reads, optionally wrap in transaction
        return DB::transaction(function () use ($wallet, $date) {
            $credits = $wallet->transactions()
                ->whereIn('type', config('wallet.credit_types', ['deposit', 'refund']))
                ->where('accepted', true)
                ->where('created_at', '<=', $date)
                ->sum('amount');

            $debits = $wallet->transactions()
                ->whereIn('type', config('wallet.debit_types', ['withdraw', 'payout', 'reverse']))
                ->where('accepted', true)
                ->where('created_at', '<=', $date)
                ->sum('amount');

            return (float) ($credits - $debits);
        });
    }
}