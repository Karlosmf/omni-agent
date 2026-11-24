<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\InstallmentStatus;
use App\Observers\BookingObserver;
use Carbon\Carbon;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[ObservedBy(BookingObserver::class)]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->file_number)) {
                $year = now()->year;
                $agencyPrefix = Str::upper(trim((string) (get_agency_settings()?->file_number_prefix ?: 'LPN')));
                $prefix = "{$agencyPrefix}-{$year}-";

                // Find max number for current year
                $lastBooking = self::where('file_number', 'like', "{$prefix}%")
                    ->orderByRaw('CAST(SUBSTR(file_number, LENGTH(?) + 1) AS UNSIGNED) DESC', [$prefix])
                    ->first();

                $number = 1;
                if ($lastBooking) {
                    $parts = explode('-', $lastBooking->file_number);
                    $number = intval(end($parts)) + 1;
                }

                $booking->file_number = $prefix.str_pad($number, 5, '0', STR_PAD_LEFT);
            }

            if (empty($booking->public_token)) {
                $booking->public_token = Str::random(48);
            }
        });
    }

    protected $fillable = [
        'lead_id',
        'customer_id',
        'agent_id',
        'file_number',
        'public_token',
        'holder_name',
        'destination',
        'nights',
        'passengers',
        'currency',
        'exchange_rate',
        'total_cost',
        'total_sell',
        'profit',
        'status',
        'travel_date',
        'valid_until',
        'internal_notes',
        'notes',
        'vouchers',
        'first_viewed_at',
        'last_viewed_at',
        'view_count',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'travel_date' => 'date',
        'valid_until' => 'date',
        'total_cost' => 'decimal:2',
        'total_sell' => 'decimal:2',
        'profit' => 'decimal:2',
        'vouchers' => 'array',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function bookingPassengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class);
    }

    public function itineraryDays()
    {
        return $this->hasMany(ItineraryDay::class)->orderBy('day_number');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class)->orderBy('number');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BookingActivity::class)->latest();
    }

    /**
     * Sold files: señados or emitidos.
     *
     * @param  Builder<self>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->whereIn('status', BookingStatus::confirmed());
    }

    /**
     * Sold files (señados o emitidos) that travel within the next days.
     *
     * @param  Builder<self>  $query
     */
    public function scopeTravellingSoon(Builder $query, int $days = 30): void
    {
        $query->confirmed()
            ->whereDate('travel_date', '>=', today())
            ->whereDate('travel_date', '<=', today()->addDays($days));
    }

    /**
     * Files with an unpaid installment past its due date.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithOverdueInstallments(Builder $query): void
    {
        $query->where('status', '!=', BookingStatus::Cancelado)
            ->whereHas('installments', fn (Builder $installments) => $installments
                ->whereIn('status', [InstallmentStatus::Pending, InstallmentStatus::Partial, InstallmentStatus::Overdue])
                ->whereDate('due_date', '<', today()));
    }

    /**
     * Sold files travelling within the next days that have no voucher uploaded yet.
     *
     * @param  Builder<self>  $query
     */
    public function scopeMissingVouchers(Builder $query, int $days = 45): void
    {
        $query->travellingSoon($days)->where(fn (Builder $q) => $q->whereNull('vouchers')->orWhere('vouchers', '[]')->orWhere('vouchers', ''));
    }

    /**
     * Quotes still waiting for the customer whose validity ends within the next days.
     *
     * @param  Builder<self>  $query
     */
    public function scopeQuoteExpiringSoon(Builder $query, int $days = 3): void
    {
        $query->whereIn('status', [BookingStatus::Borrador, BookingStatus::Presupuesto])
            ->whereDate('valid_until', '>=', today())
            ->whereDate('valid_until', '<=', today()->addDays($days));
    }

    /**
     * The travel idea the proposal was built from: the lead's, or the one named in the notes by the budget generator.
     */
    public function proposalTravelPackage(): ?TravelPackage
    {
        if ($this->lead?->travelPackage) {
            return $this->lead->travelPackage;
        }

        $prefix = 'Presupuesto generado a partir de Idea de Viaje: ';

        if (str_starts_with((string) $this->internal_notes, $prefix)) {
            return TravelPackage::where('title', substr((string) $this->internal_notes, strlen($prefix)))->first();
        }

        return null;
    }

    /**
     * Public link to the proposal as a PDF (what gets attached on WhatsApp).
     */
    public function publicPdfUrl(string $format = 'budget_only'): string
    {
        $this->publicUrl();

        return route('booking.public.pdf', ['token' => $this->public_token, 'format' => $format]);
    }

    public function isExpired(): bool
    {
        return $this->valid_until && Carbon::parse($this->valid_until)->isPast();
    }

    /**
     * Extend the validity of the quote from today and log it.
     */
    public function renewValidity(int $days = 7): void
    {
        $this->update(['valid_until' => today()->addDays($days)]);

        BookingActivity::log($this, 'updated', "Validez renovada hasta el {$this->valid_until->format('d/m/Y')}");
    }

    public function calculateProfit(): float
    {
        return (float) ($this->total_sell - $this->total_cost);
    }

    /**
     * Get the public shareable URL for this booking proposal.
     */
    public function publicUrl(): string
    {
        if (empty($this->public_token)) {
            $this->update(['public_token' => Str::random(48)]);
        }

        return route('booking.public', $this->public_token);
    }
}
