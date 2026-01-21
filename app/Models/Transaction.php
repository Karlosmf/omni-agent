<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->date)) {
                $transaction->date = now();
            }
        });
    }

    protected $fillable = [
        'booking_id',
        'supplier_id',
        'supplier_account_id',
        'financial_account_id',
        'transaction_category_id',
        'type',
        'currency',
        'amount',
        'exchange_rate',
        'amount_usd_fixed',
        'method',
        'notes',
        'reference',
        'attachment_path',
        'payable_type',
        'payable_id',
        'date',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'currency' => Currency::class,
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_usd_fixed' => 'decimal:2',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierAccount(): BelongsTo
    {
        return $this->belongsTo(SupplierAccount::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
