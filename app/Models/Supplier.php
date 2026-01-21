<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'cuit',
        'contact_email',
        'contact_phone',
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
            get: fn() => $this->bookingItems()->sum('cost_usd') -
            $this->transactions()->where('type', TransactionType::Pago)->sum('amount_usd_fixed')
        );
    }
}
