<?php

namespace App\Models;

use App\Enums\TransactionType;
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

    protected $appends = [
        'balance_usd',
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

    protected function balanceUsd(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->bookingItems()->sum('cost') -
            $this->transactions()->where('type', TransactionType::Pago)->sum('amount_usd_fixed')
        );
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
