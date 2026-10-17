<?php

use App\Enums\BookingStatus;
use App\Enums\Currency;
use App\Enums\RateSource;
use App\Enums\TransactionType;
use App\Exceptions\MissingExchangeRateException;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\ServiceType;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function seedDollarRates(): void
{
    Storage::put('currency_rates.json', json_encode([
        'currencies' => ['USD' => ['name' => 'Dólar Oficial', 'buy' => 1400, 'sell' => 1500]],
        'dollars' => [
            'oficial' => ['name' => 'Dólar Oficial', 'buy' => 1400, 'sell' => 1500],
            'mep' => ['name' => 'Dólar MEP', 'buy' => 1500, 'sell' => 1550],
            'tarjeta' => ['name' => 'Dólar Tarjeta', 'buy' => 1900, 'sell' => 1950],
        ],
        'updated_at' => now()->toDateTimeString(),
    ]));
}

function newBookingItem(Booking $booking, array $attributes): BookingItem
{
    return BookingItem::create($attributes + [
        'booking_id' => $booking->id,
        'service_type_id' => ServiceType::firstOrCreate(['key' => 'hotel'], ['name' => 'Hotel'])->id,
        'description' => 'Servicio',
    ]);
}

beforeEach(function () {
    Storage::fake('local');
    seedDollarRates();
});

test('a mixed ARS and USD booking is totalled in USD using each item rate', function () {
    $booking = Booking::factory()->create();

    newBookingItem($booking, ['currency' => 'USD', 'cost' => 800, 'sell' => 1000]);
    newBookingItem($booking, ['currency' => 'ARS', 'exchange_rate' => 1500, 'cost' => 1500000, 'sell' => 2250000]);

    $booking->refresh();

    expect((float) $booking->total_sell)->toBe(2500.0)
        ->and((float) $booking->total_cost)->toBe(1800.0)
        ->and((float) $booking->profit)->toBe(700.0)
        ->and($booking->currency)->toBe('USD');
});

test('an ARS item without a rate gets the default dollar of the day', function () {
    $booking = Booking::factory()->create();

    $item = newBookingItem($booking, ['currency' => 'ARS', 'cost' => 1500000, 'sell' => 1500000]);

    expect((float) $item->exchange_rate)->toBe(1500.0)
        ->and($item->rate_source)->toBe(RateSource::Oficial)
        ->and((float) $booking->refresh()->total_sell)->toBe(1000.0);
});

test('a legacy 1.00 rate on an ARS item is never used to convert 1:1', function () {
    $booking = Booking::factory()->create();

    newBookingItem($booking, ['currency' => 'ARS', 'exchange_rate' => 1.00, 'cost' => 1500000, 'sell' => 1500000]);

    expect((float) $booking->refresh()->total_sell)->toBe(1000.0);
});

test('an ARS item cannot be saved when no rate is available', function () {
    Storage::delete('currency_rates.json');
    Http::fake(['*' => Http::response([], 500)]);

    $booking = Booking::factory()->create();

    newBookingItem($booking, ['currency' => 'ARS', 'cost' => 1000, 'sell' => 1200]);
})->throws(MissingExchangeRateException::class);

test('a transaction created outside the form always gets its USD amount', function () {
    $ars = Transaction::factory()->create([
        'type' => TransactionType::Cobro,
        'currency' => Currency::ARS,
        'amount' => 750000,
        'exchange_rate' => 1500,
        'rate_source' => RateSource::Custom,
        'amount_usd_fixed' => null,
    ]);
    $usd = Transaction::factory()->create([
        'type' => TransactionType::Cobro,
        'currency' => Currency::USD,
        'amount' => 300,
        'exchange_rate' => null,
        'amount_usd_fixed' => null,
    ]);

    expect((float) $ars->fresh()->amount_usd_fixed)->toBe(500.0)
        ->and((float) $usd->fresh()->amount_usd_fixed)->toBe(300.0);
});

test('a transaction in ARS without a rate uses the selected dollar', function () {
    $transaction = Transaction::factory()->create([
        'currency' => Currency::ARS,
        'amount' => 1550000,
        'exchange_rate' => null,
        'rate_source' => RateSource::Mep,
    ]);

    expect((float) $transaction->fresh()->exchange_rate)->toBe(1550.0)
        ->and((float) $transaction->fresh()->amount_usd_fixed)->toBe(1000.0);
});

test('the supplier balance converts item costs to USD', function () {
    ServiceType::firstOrCreate(['key' => 'hotel'], ['name' => 'Hotel']);
    $supplier = Supplier::factory()->create();
    $booking = Booking::factory()->create();

    newBookingItem($booking, ['supplier_id' => $supplier->id, 'currency' => 'USD', 'cost' => 500, 'sell' => 600]);
    newBookingItem($booking, ['supplier_id' => $supplier->id, 'currency' => 'ARS', 'exchange_rate' => 1500, 'cost' => 750000, 'sell' => 900000]);
    Transaction::factory()->create([
        'supplier_id' => $supplier->id,
        'type' => TransactionType::Pago,
        'currency' => Currency::USD,
        'amount' => 200,
    ]);

    expect($supplier->fresh()->balance_usd)->toBe(800.0);
});

test('the currency service exposes the three dollars and never returns a 1.0 fallback', function () {
    $service = app(CurrencyService::class);

    expect($service->getDollarRate(RateSource::Oficial))->toBe(1500.0)
        ->and($service->getDollarRate(RateSource::Mep))->toBe(1550.0)
        ->and($service->getDollarRate(RateSource::Tarjeta))->toBe(1950.0)
        ->and($service->getDollarRate(RateSource::Custom))->toBeNull()
        ->and($service->getRate('EUR'))->toBeNull()
        ->and(array_keys($service->rateSourceOptions()))->toBe(['oficial', 'mep', 'tarjeta', 'custom']);
});

test('recalculate command is a dry run by default and fixes totals with --apply', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Senado]);
    BookingItem::withoutEvents(fn () => newBookingItem($booking, ['currency' => 'ARS', 'exchange_rate' => 1500, 'cost' => 1500000, 'sell' => 1500000]));
    $booking->updateQuietly(['total_sell' => 1500000, 'total_cost' => 1500000, 'profit' => 0]);

    $this->artisan('omni:recalculate-bookings')->assertSuccessful();
    expect((float) $booking->fresh()->total_sell)->toBe(1500000.0);

    $this->artisan('omni:recalculate-bookings --apply')->expectsConfirmation('¿Hiciste un backup de la base de datos antes de aplicar los cambios?', 'yes')->assertSuccessful();
    expect((float) $booking->fresh()->total_sell)->toBe(1000.0)
        ->and($booking->activities()->where('type', 'recalculated')->count())->toBe(1);
});

test('recalculate command skips bookings whose ARS items have no real rate', function () {
    $booking = Booking::factory()->create();
    BookingItem::withoutEvents(fn () => newBookingItem($booking, ['currency' => 'ARS', 'exchange_rate' => 1, 'cost' => 1500000, 'sell' => 1500000]));
    $booking->updateQuietly(['total_sell' => 1500000]);

    $this->artisan('omni:recalculate-bookings --apply')->expectsConfirmation('¿Hiciste un backup de la base de datos antes de aplicar los cambios?', 'yes')->assertSuccessful();
    expect((float) $booking->fresh()->total_sell)->toBe(1500000.0);

    $this->artisan('omni:recalculate-bookings --apply --estimate-rates=oficial')->expectsConfirmation('¿Hiciste un backup de la base de datos antes de aplicar los cambios?', 'yes')->assertSuccessful();
    expect((float) $booking->fresh()->total_sell)->toBe(1000.0);
});

test('recalculate command leaves bookings without items untouched', function () {
    $booking = Booking::factory()->create(['total_sell' => 4000, 'total_cost' => 3000, 'profit' => 1000]);

    $this->artisan('omni:recalculate-bookings --apply')->expectsConfirmation('¿Hiciste un backup de la base de datos antes de aplicar los cambios?', 'yes')->assertSuccessful();

    expect((float) $booking->fresh()->total_sell)->toBe(4000.0);
});

test('recalculate command can be applied without the interactive confirmation using --force', function () {
    $booking = Booking::factory()->create();
    BookingItem::withoutEvents(fn () => newBookingItem($booking, ['currency' => 'ARS', 'exchange_rate' => 1500, 'cost' => 1500000, 'sell' => 1500000]));
    $booking->updateQuietly(['total_sell' => 1500000, 'total_cost' => 1500000, 'profit' => 0]);

    $this->artisan('omni:recalculate-bookings --apply --force')->assertSuccessful();

    expect((float) $booking->fresh()->total_sell)->toBe(1000.0);
});
