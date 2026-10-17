<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\RateSource;
use App\Enums\TransactionType;
use App\Exceptions\MissingExchangeRateException;
use App\Observers\TransactionObserver;
use App\Services\CurrencyService;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy(TransactionObserver::class)]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->date)) {
                $transaction->date = now();
            }
        });

        static::saving(function (Transaction $transaction) {
            $transaction->syncUsdAmount();
        });
    }

    protected $fillable = [
        'booking_id',
        'installment_id',
        'supplier_id',
        'supplier_account_id',
        'financial_account_id',
        'transaction_category_id',
        'type',
        'currency',
        'amount',
        'exchange_rate',
        'rate_source',
        'amount_usd_fixed',
        'method',
        'notes',
        'reference',
        'attachment_path',
        'payable_type',
        'payable_id',
        'date',
        'tax_details',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'currency' => Currency::class,
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'rate_source' => RateSource::class,
        'amount_usd_fixed' => 'decimal:2',
        'tax_details' => 'array',
    ];

    /**
     * Keep `amount_usd_fixed` consistent no matter how the transaction was created
     * (form, import, API). Non-USD amounts are converted with the stored rate
     * (units of currency per 1 USD) and are never assumed 1:1.
     */
    public function syncUsdAmount(): void
    {
        $currency = $this->currency instanceof Currency ? $this->currency : Currency::tryFrom((string) $this->currency);

        if ($currency === null || $currency === Currency::USD) {
            $this->amount_usd_fixed = round((float) $this->amount, 2);

            return;
        }

        $rate = (float) $this->exchange_rate;

        if ($rate <= 0 && $this->rate_source !== RateSource::Custom) {
            $source = $this->rate_source instanceof RateSource ? $this->rate_source : RateSource::Oficial;
            $rate = (float) app(CurrencyService::class)->unitsPerUsd($currency, $source);

            if ($rate > 0) {
                $this->exchange_rate = $rate;
                $this->rate_source ??= $source;
            }
        }

        $usd = CurrencyService::toUsd($this->amount, $currency, $rate);

        if ($usd === null) {
            throw new MissingExchangeRateException("La transacción en {$currency->value} necesita una cotización.");
        }

        $this->amount_usd_fixed = $usd;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(PaymentInstallment::class, 'installment_id');
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
