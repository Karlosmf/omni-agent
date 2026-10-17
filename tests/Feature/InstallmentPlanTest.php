<?php

use App\Enums\BookingStatus;
use App\Enums\Currency;
use App\Enums\InstallmentStatus;
use App\Enums\RateSource;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Bookings\Pages\EditBooking;
use App\Filament\Admin\Resources\Bookings\RelationManagers\InstallmentsRelationManager;
use App\Mail\InstallmentReminderMail;
use App\Models\AgencySetting;
use App\Models\Booking;
use App\Models\PaymentInstallment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InstallmentService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::put('currency_rates.json', json_encode([
        'currencies' => ['USD' => ['name' => 'Dólar Oficial', 'buy' => 1400, 'sell' => 1500]],
        'dollars' => ['oficial' => ['name' => 'Dólar Oficial', 'buy' => 1400, 'sell' => 1500]],
        'updated_at' => now()->toDateTimeString(),
    ]));
});

function planBooking(float $total = 1000, array $attributes = []): Booking
{
    return Booking::factory()->create($attributes + [
        'total_sell' => $total,
        'total_cost' => $total / 2,
        'status' => BookingStatus::Presupuesto,
        'travel_date' => today()->addMonths(8),
    ]);
}

function cobro(Booking $booking, float $usd, array $attributes = []): Transaction
{
    return Transaction::factory()->create($attributes + [
        'booking_id' => $booking->id,
        'type' => TransactionType::Cobro,
        'currency' => Currency::USD,
        'amount' => $usd,
        'exchange_rate' => null,
    ]);
}

test('the plan splits the total into deposit and installments and fixes rounding on the last one', function () {
    $booking = planBooking(1000);

    app(InstallmentService::class)->generate($booking, 30, 3, today()->addMonth(), 'monthly');

    $rows = $booking->installments()->get();

    expect($rows->pluck('label')->all())->toBe(['Seña', 'Cuota 1/3', 'Cuota 2/3', 'Cuota 3/3'])
        ->and($rows->pluck('amount')->map(fn ($a) => (float) $a)->all())->toBe([300.0, 233.33, 233.33, 233.34])
        ->and(round($rows->sum('amount'), 2))->toBe(1000.0)
        ->and($rows->first()->due_date->isToday())->toBeTrue();
});

test('without installments the rest becomes a single balance', function () {
    $booking = planBooking(900);

    app(InstallmentService::class)->generate($booking, 50, 1, today()->addMonth());

    expect($booking->installments()->pluck('label')->all())->toBe(['Seña', 'Saldo']);
});

test('the last due date cannot be too close to the trip', function () {
    $booking = planBooking(1000, ['travel_date' => today()->addDays(60)]);

    app(InstallmentService::class)->generate($booking, 30, 4, today()->addMonth(), 'monthly');
})->throws(InvalidArgumentException::class, 'días antes del viaje');

test('a plan with payments cannot be regenerated', function () {
    $booking = planBooking();
    $service = app(InstallmentService::class);
    $service->generate($booking, 30, 2, today()->addMonth());
    cobro($booking, 300);

    $service->generate($booking, 20, 2, today()->addMonth());
})->throws(InvalidArgumentException::class, 'pagos registrados');

test('a payment without installment pays the oldest one and the deposit makes the booking reserved', function () {
    $booking = planBooking(1000);
    app(InstallmentService::class)->generate($booking, 30, 2, today()->addMonth());

    cobro($booking, 100);

    $rows = $booking->installments()->get();
    expect($rows[0]->status)->toBe(InstallmentStatus::Partial)
        ->and((float) $rows[0]->paid_amount)->toBe(100.0)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Presupuesto);

    cobro($booking, 250);

    $rows = $booking->installments()->get();
    expect($rows[0]->status)->toBe(InstallmentStatus::Paid)
        ->and((float) $rows[1]->paid_amount)->toBe(50.0)
        ->and($rows[1]->status)->toBe(InstallmentStatus::Partial)
        ->and($booking->fresh()->status)->toBe(BookingStatus::Senado);
});

test('a payment assigned to a specific installment counts for it', function () {
    $booking = planBooking(1000);
    app(InstallmentService::class)->generate($booking, 30, 2, today()->addMonth());
    $second = $booking->installments()->where('number', 2)->first();

    cobro($booking, 350, ['installment_id' => $second->id]);

    expect($second->fresh()->status)->toBe(InstallmentStatus::Paid)
        ->and((float) $booking->installments()->where('number', 0)->first()->paid_amount)->toBe(0.0);
});

test('ARS payments count with the USD amount of the day they were paid', function () {
    $booking = planBooking(1000);
    app(InstallmentService::class)->generate($booking, 30, 2, today()->addMonth());

    Transaction::factory()->create([
        'booking_id' => $booking->id,
        'type' => TransactionType::Cobro,
        'currency' => Currency::ARS,
        'amount' => 450000,
        'exchange_rate' => 1500,
        'rate_source' => RateSource::Custom,
    ]);

    expect((float) $booking->installments()->first()->paid_amount)->toBe(300.0)
        ->and($booking->installments()->first()->status)->toBe(InstallmentStatus::Paid);
});

test('deleting a payment reverts the installment', function () {
    $booking = planBooking(1000);
    app(InstallmentService::class)->generate($booking, 30, 2, today()->addMonth());
    $payment = cobro($booking, 300);

    expect($booking->fresh()->status)->toBe(BookingStatus::Senado);

    $payment->delete();

    expect($booking->installments()->first()->status)->toBe(InstallmentStatus::Pending)
        ->and((float) $booking->installments()->first()->paid_amount)->toBe(0.0);
});

test('unpaid installments past their due date become overdue', function () {
    $booking = planBooking(1000);
    app(InstallmentService::class)->generate($booking, 30, 2, today()->addMonth());
    $late = $booking->installments()->where('number', 1)->first();
    $late->update(['due_date' => today()->subDays(3)]);

    expect(app(InstallmentService::class)->markOverdue())->toBe(1)
        ->and($late->fresh()->status)->toBe(InstallmentStatus::Overdue);

    cobro($booking, 300 + 350);
    expect($late->fresh()->status)->toBe(InstallmentStatus::Paid);
});

test('reminders go out once per stage: 3 days before, the due day and 2 days after', function () {
    Mail::fake();

    $customer = User::factory()->create(['email' => 'cliente@example.com']);
    $booking = planBooking(1000, ['customer_id' => $customer->id, 'holder_name' => 'Lucía']);
    foreach ([['Cuota A', 3], ['Cuota B', 0], ['Cuota C', -2], ['Cuota D', 10]] as $i => [$label, $offset]) {
        PaymentInstallment::create(['booking_id' => $booking->id, 'number' => $i + 1, 'label' => $label, 'due_date' => today()->addDays($offset), 'amount' => 100]);
    }

    $this->artisan('omni:installment-reminders')->assertSuccessful();
    $this->artisan('omni:installment-reminders')->assertSuccessful();

    Mail::assertQueued(InstallmentReminderMail::class, 3);
    Mail::assertQueued(InstallmentReminderMail::class, fn ($mail) => $mail->stage === 'overdue' && $mail->hasTo('cliente@example.com'));
});

test('reminder text uses the agency wording when set', function () {
    $booking = planBooking();
    $installment = PaymentInstallment::create(['booking_id' => $booking->id, 'number' => 1, 'label' => 'Cuota 1/2', 'due_date' => today()->addDays(3), 'amount' => 250]);

    $default = InstallmentReminderMail::bodyText($installment->load('booking'), 'before');
    expect($default)->toContain('Cuota 1/2')->toContain($booking->file_number)->toContain('250,00');

    $settings = new AgencySetting(['reminder_before_text' => 'Hola [NOMBRE]: [CUOTA] vence el [VENCIMIENTO]']);
    expect(InstallmentReminderMail::bodyText($installment, 'before', $settings))->toStartWith('Hola '.$booking->holder_name.': Cuota 1/2 vence el');
});

test('the public page shows the payment plan without costs or internal data', function () {
    $booking = planBooking(1000, ['status' => BookingStatus::Presupuesto, 'total_cost' => 654.32, 'internal_notes' => 'nota interna secreta']);
    app(InstallmentService::class)->generate($booking, 30, 2, today()->addMonth());

    $this->get(route('booking.public', $booking->public_token))
        ->assertOk()
        ->assertSee('Tu plan de pagos')
        ->assertSee('Cuota 1/2')
        ->assertSee('Saldo pendiente')
        ->assertDontSee('654')
        ->assertDontSee('nota interna secreta');
});

test('a booking without a plan shows no payment section', function () {
    $booking = planBooking();

    $this->get(route('booking.public', $booking->public_token))->assertOk()->assertDontSee('Tu plan de pagos');
});

test('the admin can generate a plan and register a payment from the booking screen', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    $booking = planBooking(1000);

    $component = Livewire\Livewire::test(InstallmentsRelationManager::class, [
        'ownerRecord' => $booking,
        'pageClass' => EditBooking::class,
    ]);

    $component
        ->callAction(TestAction::make('generatePlan')->table(), [
            'deposit_percent' => 30, 'installments' => 2, 'first_due_date' => today()->addMonth()->toDateString(), 'frequency' => 'monthly',
        ])
        ->assertHasNoErrors();

    expect($booking->installments()->count())->toBe(3);

    $deposit = $booking->installments()->first();

    $component
        ->callAction(TestAction::make('registerPayment')->table($deposit), [
            'currency' => 'ARS', 'amount' => 450000, 'rate_source' => 'oficial', 'exchange_rate' => 1500, 'method' => 'Transferencia', 'date' => today()->toDateString(),
        ])
        ->assertHasNoErrors();

    expect($deposit->fresh()->status)->toBe(InstallmentStatus::Paid)
        ->and($booking->transactions()->first()->installment_id)->toBe($deposit->id);
});
