<?php

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadTemperature;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use App\Models\AgencySetting;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\FollowUpRule;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\FollowUpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    Cache::forget('agency_settings');
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->agent = User::factory()->create(['role' => UserRole::Sales]);
});

function newLead(array $attributes = []): Lead
{
    return Lead::factory()->create($attributes + [
        'status' => LeadStatus::New,
        'agent_id' => null,
        'needs_human_attention' => false,
        'temperature' => LeadTemperature::Cool,
        'last_contacted_at' => null,
    ]);
}

function rule(string $trigger): FollowUpRule
{
    return FollowUpRule::where('trigger', $trigger)->firstOrFail();
}

test('the default rules are installed', function () {
    expect(FollowUpRule::count())->toBe(6)->and(FollowUpRule::where('is_active', true)->count())->toBe(6);
});

test('an uncontacted lead creates one task after 24 hours and never repeats', function () {
    $lead = newLead(['agent_id' => $this->agent->id]);

    app(FollowUpService::class)->run();
    expect(Task::count())->toBe(0);

    $this->travel(25)->hours();
    app(FollowUpService::class)->run();
    app(FollowUpService::class)->run();

    $task = Task::first();
    expect(Task::count())->toBe(1)
        ->and($task->agent_id)->toBe($this->agent->id)
        ->and($task->related_id)->toBe($lead->id)
        ->and($lead->fresh()->next_follow_up_at)->not->toBeNull();
});

test('a contacted lead is left alone', function () {
    newLead(['agent_id' => $this->agent->id, 'status' => LeadStatus::Contacted, 'last_contacted_at' => now()]);

    $this->travel(48)->hours();
    app(FollowUpService::class)->run();

    expect(Task::count())->toBe(0);
});

test('a lead without agent notifies the admins after 2 hours', function () {
    newLead();

    $this->travel(3)->hours();
    app(FollowUpService::class)->run();

    expect($this->admin->notifications()->count())->toBe(1)
        ->and($this->agent->notifications()->count())->toBe(0);
});

test('a lead asking for a human creates an urgent task right away', function () {
    $lead = newLead(['agent_id' => $this->agent->id, 'needs_human_attention' => true]);

    app(FollowUpService::class)->run();

    expect(Task::where('related_id', $lead->id)->where('title', 'like', 'Urgente%')->count())->toBe(1);
});

test('a quote that was emailed and never opened creates a task after 48 hours', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Presupuesto, 'agent_id' => $this->agent->id, 'view_count' => 0, 'valid_until' => today()->addMonth()]);
    BookingActivity::create(['booking_id' => $booking->id, 'type' => 'email_sent', 'description' => 'Enviado']);

    app(FollowUpService::class)->run();
    expect(Task::where('title', 'like', 'Llamar%')->count())->toBe(0);

    $this->travel(49)->hours();
    app(FollowUpService::class)->run();

    expect(Task::where('title', 'like', 'Llamar%')->count())->toBe(1);
});

test('a quote opened three times without a deposit creates a "close the sale" task', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Presupuesto, 'agent_id' => $this->agent->id, 'view_count' => 3, 'last_viewed_at' => now(), 'valid_until' => today()->addMonth()]);

    app(FollowUpService::class)->run();
    expect(Task::where('title', 'like', 'Cerrar%')->count())->toBe(0);

    $this->travel(25)->hours();
    app(FollowUpService::class)->run();

    expect(Task::where('title', 'like', 'Cerrar%')->where('related_id', $booking->id)->count())->toBe(1);
});

test('an expired quote notifies its agent and can be renewed', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Presupuesto, 'agent_id' => $this->agent->id, 'valid_until' => today()->subDay()]);

    app(FollowUpService::class)->run();
    expect($this->agent->notifications()->count())->toBe(1);

    $booking->renewValidity();
    expect($booking->fresh()->isExpired())->toBeFalse()
        ->and($booking->activities()->where('description', 'like', 'Validez renovada%')->count())->toBe(1);
});

test('inactive rules do nothing', function () {
    FollowUpRule::query()->update(['is_active' => false]);
    newLead(['agent_id' => $this->agent->id]);

    $this->travel(48)->hours();

    expect(array_sum(app(FollowUpService::class)->run()))->toBe(0)->and(Task::count())->toBe(0);
});

test('the artisan command runs the rules', function () {
    newLead(['agent_id' => $this->agent->id, 'needs_human_attention' => true]);

    $this->artisan('omni:run-follow-ups')->assertSuccessful();

    expect(Task::count())->toBe(1);
});

test('customers opening the public link are counted, staff and bots are not', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Presupuesto, 'view_count' => 0]);
    $url = route('booking.public', $booking->public_token);

    $this->get($url)->assertOk();
    $this->get($url)->assertOk();

    expect($booking->fresh()->view_count)->toBe(2)
        ->and($booking->fresh()->first_viewed_at)->not->toBeNull()
        ->and($booking->activities()->where('type', 'viewed')->count())->toBe(1);

    $this->withHeader('User-Agent', 'WhatsApp/2.23 bot')->get($url)->assertOk();
    $this->actingAs($this->admin)->get($url)->assertOk();

    expect($booking->fresh()->view_count)->toBe(2);
});

test('leads are assigned round-robin to the sales agent with fewest open leads when enabled', function () {
    $other = User::factory()->create(['role' => UserRole::Sales]);
    AgencySetting::factory()->create(['auto_assign_leads' => true]);
    Cache::forget('agency_settings');

    $first = newLead();
    $second = newLead();
    $third = newLead();

    expect(collect([$first, $second, $third])->map(fn ($l) => $l->fresh()->agent_id)->filter()->count())->toBe(3)
        ->and($first->fresh()->agent_id)->not->toBe($second->fresh()->agent_id)
        ->and(Lead::where('agent_id', $this->agent->id)->count() + Lead::where('agent_id', $other->id)->count())->toBe(3);
});

test('leads stay unassigned when auto assignment is off', function () {
    AgencySetting::factory()->create(['auto_assign_leads' => false]);
    Cache::forget('agency_settings');

    expect(newLead()->fresh()->agent_id)->toBeNull();
});

test('moving a lead out of new counts as contacting it', function () {
    $lead = newLead();

    $lead->update(['status' => LeadStatus::Contacted]);

    expect($lead->fresh()->last_contacted_at)->not->toBeNull();
});

test('sending the quote by email marks the lead as contacted', function () {
    $lead = newLead();
    $booking = Booking::factory()->create(['lead_id' => $lead->id]);

    BookingActivity::log($booking, 'email_sent', 'Enviado');

    expect($lead->fresh()->last_contacted_at)->not->toBeNull()->and(BookingsTable::class)->toBeString();
});

test('baseline marks old matches as handled without creating tasks or notifications', function () {
    newLead(['agent_id' => $this->agent->id, 'created_at' => now()->subDays(10), 'needs_human_attention' => true]);
    Booking::factory()->create(['status' => BookingStatus::Presupuesto, 'valid_until' => today()->subWeek()]);

    $this->artisan('omni:run-follow-ups --baseline')->assertSuccessful();

    expect(Task::count())->toBe(0)
        ->and($this->agent->notifications()->count() + $this->admin->notifications()->count())->toBe(0);

    $this->artisan('omni:run-follow-ups')->assertSuccessful();

    expect(Task::count())->toBe(0);
});
