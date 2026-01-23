<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    /** @use HasFactory<\Database\Factories\QuotationFactory> */
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($quotation) {
            if (empty($quotation->quotation_number)) {
                $year = now()->year;
                $prefix = "COT-{$year}-";

                $lastQuotation = self::where('quotation_number', 'like', "{$prefix}%")
                    ->orderByRaw('CAST(SUBSTR(quotation_number, LENGTH(?) + 1) AS UNSIGNED) DESC', [$prefix])
                    ->first();

                $number = 1;
                if ($lastQuotation) {
                    $parts = explode('-', $lastQuotation->quotation_number);
                    $number = intval(end($parts)) + 1;
                }

                $quotation->quotation_number = "{$prefix}{$number}";
            }

            if (empty($quotation->valid_until)) {
                $quotation->valid_until = now()->addDays(7);
            }
        });
    }

    protected $fillable = [
        'quotation_number',
        'customer_id',
        'lead_id',
        'destination',
        'travel_date',
        'nights',
        'passengers',
        'items',
        'total_cost',
        'total_sell',
        'profit',
        'currency',
        'status',
        'valid_until',
        'notes',
    ];

    protected $casts = [
        'status' => QuotationStatus::class,
        'travel_date' => 'date',
        'valid_until' => 'date',
        'items' => 'array',
        'total_cost' => 'decimal:2',
        'total_sell' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }
}
