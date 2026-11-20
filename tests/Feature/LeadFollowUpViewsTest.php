<?php

use App\Enums\BookingStatus;
use App\Enums\InstallmentStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Filament\Admin\Pages\LeadBoard;
use App\Filament\Admin\Pages\WhatsAppInbox;
use App\Filament\Admin\Resources\Bookings\Pages\ListBookings;
use App\Filament\Admin\Resources\Leads\Pages\EditLead;
use App\Filament\Admin\Resources\Leads\Pages\ListLeads;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\PaymentInstallment;
use App\Models\User;
use Livewire\Livewire;

function followUpLead(array $attributes = []): Lead
{
    return Lead::factory()->create(array_merge([
        'status' => LeadStatus::Contacted,
        'temperature' => LeadTemperature::Cool,
        'needs_human_attention' => false,
        'next_follow_up_at' => null,
        'agent_id' => null,
    ], $attributes));
}

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

// --- Priority order ---

test('leads are ordered by urgency: attention, overdue follow-up, temperature, then the nearest follow-up', function () {
    $later = followUpLead(['next_follow_up_at' => now()->addDays(5)]);
    $sooner = followUpLead(['next_follow_up_at' => now()->addDay()]);
    $hot = followUpLead(['temperature' => LeadTemperature::Hot]);
    $overdue = followUpLead(['next_follow_up_at' => now()->subDay()]);
    $attention = followUpLead(['needs_human_attention' => true]);
    $none = followUpLead();

    expect(Lead::orderByPriority()->pluck('id')->all())
        ->toBe([$attention->id, $overdue->id, $hot->id, $sooner->id, $later->id, $none->id]);
});

test('the leads list opens on today\'s work, most urgent first', function () {
    $dueToday = followUpLead(['next_follow_up_at' => now()->subHour()]);
    $attention = followUpLead(['needs_human_attention' => true]);
    $nextWeek = followUpLead(['next_follow_up_at' => now()->addWeek()]);
    $closed = followUpLead(['status' => LeadStatus::Closed, 'needs_human_attention' => true]);

    Livewire::test(ListLeads::class)
        ->assertSet('activeTab', 'hoy')
        ->assertCanSeeTableRecords([$attention, $dueToday], inOrder: true)
        ->assertCanNotSeeTableRecords([$nextWeek, $closed]);
});

test('a sales agent only counts and sees their own and unassigned leads', function () {
    $sales = User::factory()->create(['role' => UserRole::Sales]);
    $mine = followUpLead(['needs_human_attention' => true, 'agent_id' => $sales->id]);
    $unassigned = followUpLead(['needs_human_attention' => true]);
    $someoneElse = followUpLead(['needs_human_attention' => true, 'agent_id' => $this->admin->id]);

    $this->actingAs($sales);

    Livewire::test(ListLeads::class)
        ->assertCanSeeTableRecords([$mine, $unassigned])
        ->assertCanNotSeeTableRecords([$someoneElse]);
});

test('the inbox shows a sales agent the same chats as their inquiries list, and the inquiry links to its chat', function () {
    $sales = User::factory()->create(['role' => UserRole::Sales]);
    $mine = followUpLead(['agent_id' => $sales->id, 'customer_name' => 'Chat Propio']);
    $someoneElse = followUpLead(['agent_id' => $this->admin->id, 'customer_name' => 'Chat Ajeno']);

    foreach ([$mine, $someoneElse] as $lead) {
        $lead->messages()->create(['role' => 'user', 'content' => 'hola', 'channel' => 'whatsapp', 'direction' => 'in', 'external_id' => 'wamid.'.$lead->id, 'status' => 'received']);
    }

    $this->actingAs($sales);

    Livewire::test(WhatsAppInbox::class)
        ->assertSee('Chat Propio')
        ->assertDontSee('Chat Ajeno')
        ->call('open', $someoneElse->id)
        ->assertSet('lead', null);

    Livewire::test(EditLead::class, ['record' => $mine->id])
        ->assertActionVisible('open_chat');
});

// --- Bookings queues ---

test('booking queues pick trips by travel date, unpaid installments, missing vouchers and expiring quotes', function () {
    $soon = Booking::factory()->create(['status' => BookingStatus::Senado, 'travel_date' => today()->addDays(10), 'vouchers' => ['v.pdf']]);
    $soonNoVouchers = Booking::factory()->create(['status' => BookingStatus::Emitido, 'travel_date' => today()->addDays(20), 'vouchers' => null]);
    $far = Booking::factory()->create(['status' => BookingStatus::Senado, 'travel_date' => today()->addDays(90), 'vouchers' => null]);
    $quote = Booking::factory()->create(['status' => BookingStatus::Presupuesto, 'valid_until' => today()->addDays(2), 'travel_date' => today()->addMonths(3)]);
    PaymentInstallment::create(['booking_id' => $far->id, 'number' => 1, 'label' => 'Cuota 1', 'due_date' => today()->subDays(3), 'amount' => 100, 'paid_amount' => 0, 'status' => InstallmentStatus::Pending]);
    PaymentInstallment::create(['booking_id' => $soon->id, 'number' => 1, 'label' => 'Cuota 1', 'due_date' => today()->subDays(3), 'amount' => 100, 'paid_amount' => 100, 'status' => InstallmentStatus::Paid]);

    expect(Booking::travellingSoon()->pluck('id')->sort()->values()->all())->toBe(collect([$soon->id, $soonNoVouchers->id])->sort()->values()->all())
        ->and(Booking::withOverdueInstallments()->pluck('id')->all())->toBe([$far->id])
        ->and(Booking::missingVouchers()->pluck('id')->all())->toBe([$soonNoVouchers->id])
        ->and(Booking::quoteExpiringSoon()->pluck('id')->all())->toBe([$quote->id]);

    Livewire::test(ListBookings::class)
        ->assertSet('activeTab', 'viajan_pronto')
        ->assertCanSeeTableRecords([$soon, $soonNoVouchers], inOrder: true)
        ->assertCanNotSeeTableRecords([$far, $quote]);
});

// --- Board ---

test('the board shows each stage with its leads and moves a dropped card to the new stage', function () {
    $lead = followUpLead(['status' => LeadStatus::New, 'customer_name' => 'Lucía Pérez', 'last_contacted_at' => null]);

    Livewire::test(LeadBoard::class)
        ->assertSee(['Nueva', 'En curso', 'Cotizada', 'Reservada', 'Lucía Pérez'])
        ->assertDontSee('Por verificar')
        ->call('moveLead', $lead->id, LeadStatus::Quoted->value)
        ->assertNotified();

    expect($lead->fresh())
        ->status->toBe(LeadStatus::Quoted)
        ->last_contacted_at->not->toBeNull();
});

test('the board never moves a lead into review, nor one the user cannot see', function () {
    $sales = User::factory()->create(['role' => UserRole::Sales]);
    $someoneElse = followUpLead(['status' => LeadStatus::New, 'agent_id' => $this->admin->id]);
    $mine = followUpLead(['status' => LeadStatus::New, 'agent_id' => $sales->id]);

    $this->actingAs($sales);

    Livewire::test(LeadBoard::class)
        ->call('moveLead', $someoneElse->id, LeadStatus::Contacted->value)
        ->call('moveLead', $mine->id, LeadStatus::PendingReview->value)
        ->call('moveLead', $mine->id, 'not-a-status');

    expect($someoneElse->fresh()->status)->toBe(LeadStatus::New)
        ->and($mine->fresh()->status)->toBe(LeadStatus::New);
});

test('the board is only for users who manage leads', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Staff, 'permissions' => []]));

    $this->get(LeadBoard::getUrl())->assertForbidden();
});

// --- The inquiry follows its booking ---

test('the inquiry moves on its own as its booking advances, and goes back if the booking is cancelled or removed', function () {
    $lead = followUpLead(['status' => LeadStatus::Contacted]);

    $booking = Booking::factory()->create(['lead_id' => $lead->id, 'customer_id' => $lead->customer_id, 'status' => BookingStatus::Borrador]);
    expect($lead->fresh()->status)->toBe(LeadStatus::Quoted);

    $booking->update(['status' => BookingStatus::Senado]);
    expect($lead->fresh()->status)->toBe(LeadStatus::Booked);

    $booking->update(['status' => BookingStatus::Cancelado]);
    expect($lead->fresh()->status)->toBe(LeadStatus::Closed);

    $booking->update(['status' => BookingStatus::Presupuesto]);
    expect($lead->fresh()->status)->toBe(LeadStatus::Quoted);
});

test('with several bookings the most advanced one decides the stage', function () {
    $lead = followUpLead(['status' => LeadStatus::Contacted]);
    $sold = Booking::factory()->create(['lead_id' => $lead->id, 'customer_id' => $lead->customer_id, 'status' => BookingStatus::Emitido]);
    Booking::factory()->create(['lead_id' => $lead->id, 'customer_id' => $lead->customer_id, 'status' => BookingStatus::Presupuesto]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Booked);

    $sold->delete();
    expect($lead->fresh()->status)->toBe(LeadStatus::Quoted);
});

test('paying the deposit of the payment plan books the inquiry', function () {
    $lead = followUpLead(['status' => LeadStatus::Contacted]);
    $booking = Booking::factory()->create(['lead_id' => $lead->id, 'customer_id' => $lead->customer_id, 'status' => BookingStatus::Presupuesto]);
    expect($lead->fresh()->status)->toBe(LeadStatus::Quoted);

    // The installment service moves the booking to "Señado" with a regular update when the deposit is paid.
    $booking->update(['status' => BookingStatus::Senado]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Booked)
        ->and($lead->fresh()->last_contacted_at)->not->toBeNull();
});

test('a lead without bookings keeps the stage it was given by hand', function () {
    $lead = followUpLead(['status' => LeadStatus::Contacted]);

    $lead->syncStageWithBookings();

    expect($lead->fresh()->status)->toBe(LeadStatus::Contacted);
});
