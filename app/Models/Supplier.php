<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
        'service_type_id',
        'category',
        'location',
        'cuit',
        'bank_name',
        'cbu',
        'alias',
        'account_number',
        'notes',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(SupplierAccount::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Amount owed to the supplier in USD: item costs converted with each item's rate, minus payments made.
     */
    protected function balanceUsd(): Attribute
    {
        return Attribute::make(
            get: function () {
                $costUsd = $this->bookingItems()
                    ->get(['currency', 'exchange_rate', 'cost'])
                    ->sum(fn (BookingItem $item) => CurrencyService::toUsd($item->cost, $item->currency, $item->exchange_rate) ?? 0);

                return round($costUsd - (float) $this->transactions()->where('type', TransactionType::Pago)->sum('amount_usd_fixed'), 2);
            }
        );
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
