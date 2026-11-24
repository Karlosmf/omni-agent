<?php

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Leads\Pages\ListLeads;
use App\Filament\Admin\Widgets\Analytics\LeadOutcomesWidget;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\User;
use App\Support\LeadOutcomeStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 11, 20)->setTime(12, 0));

    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->seller = User::factory()->create(['role' => UserRole::Sales, 'name' => 'Vendedora']);

    $this->lead = function (LeadStatus $status, string $createdAt, array $attributes = []): Lead {
        $lead = Lead::factory()->create([
            'status' => $status,
            'source' => 'whatsapp',
            'agent_id' => $this->seller->id,
            ...$attributes,
        ]);
        $lead->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $lead;
    };
});

/**
 * This month: 2 won (one by status, one by a confirmed booking that was later closed), 1 lost, 2 open.
 * Last month: 1 lost, 1 open.
 */
function seedOutcomes($test): array
{
    $won = ($test->lead)(LeadStatus::Booked, '2026-11-02 10:00:00');

    $soldThenClosed = ($test->lead)(LeadStatus::Quoted, '2026-11-05 10:00:00', ['source' => 'instagram']);
    Booking::factory()->create(['lead_id' => $soldThenClosed->id, 'status' => BookingStatus::Emitido]);
    $soldThenClosed->refresh()->update(['status' => LeadStatus::Closed]);

    $lost = ($test->lead)(LeadStatus::Closed, '2026-11-10 10:00:00', ['source' => 'instagram']);
    $open = ($test->lead)(LeadStatus::Contacted, '2026-11-15 10:00:00', ['agent_id' => null]);
    $quoted = ($test->lead)(LeadStatus::Quoted, '2026-11-20 09:00:00');
    Booking::factory()->create(['lead_id' => $quoted->id, 'status' => BookingStatus::Presupuesto]);

    $lostLastMonth = ($test->lead)(LeadStatus::Closed, '2026-10-31 23:00:00');
    $openLastMonth = ($test->lead)(LeadStatus::New, '2026-10-01 00:30:00');

    return compact('won', 'soldThenClosed', 'lost', 'open', 'quoted', 'lostLastMonth', 'openLastMonth');
}

it('counts received, won, lost and open inquiries of this month', function () {
    seedOutcomes($this);

    expect(LeadOutcomeStats::forPeriod('this_month', viewer: $this->admin)->totals())->toBe([
        'received' => 5,
        'won' => 2,
        'lost' => 1,
        'open' => 2,
        'conversion' => 40.0,
    ]);
});

it('limits the counts to the chosen period', function () {
    seedOutcomes($this);

    expect(LeadOutcomeStats::forPeriod('last_month', viewer: $this->admin)->totals())
        ->toMatchArray(['received' => 2, 'won' => 0, 'lost' => 1, 'open' => 1, 'conversion' => 0.0]);

    expect(LeadOutcomeStats::forPeriod('custom', '2026-11-05', '2026-11-10', $this->admin)->totals())
        ->toMatchArray(['received' => 2, 'won' => 1, 'lost' => 1, 'open' => 0]);
});

it('swaps a custom range given backwards', function () {
    seedOutcomes($this);

    $stats = LeadOutcomeStats::forPeriod('custom', '2026-11-10', '2026-11-05', $this->admin);

    expect($stats->from->toDateString())->toBe('2026-11-05')
        ->and($stats->totals()['received'])->toBe(2);
});

it('breaks the counts down by source and by seller', function () {
    seedOutcomes($this);

    $stats = LeadOutcomeStats::forPeriod('this_month', viewer: $this->admin);

    expect(collect($stats->bySource())->keyBy('source')->map(fn (array $row) => [$row['received'], $row['won'], $row['lost'], $row['open']])->all())
        ->toBe([
            'whatsapp' => [3, 1, 0, 2],
            'instagram' => [2, 1, 1, 0],
        ]);

    expect(collect($stats->byAgent())->map(fn (array $row) => [$row['label'], $row['received'], $row['won']])->all())
        ->toBe([
            ['Vendedora', 4, 2],
            ['Sin asignar', 1, 0],
        ]);
});

it('only counts what a seller may see', function () {
    seedOutcomes($this);
    ($this->lead)(LeadStatus::Closed, '2026-11-12 10:00:00', ['agent_id' => User::factory()->create(['role' => UserRole::Sales])->id]);

    expect(LeadOutcomeStats::forPeriod('this_month', viewer: $this->seller)->totals()['received'])->toBe(5)
        ->and(LeadOutcomeStats::forPeriod('this_month', viewer: $this->admin)->totals()['received'])->toBe(6);
});

it('shows the stats and links each figure to the filtered lead list', function () {
    seedOutcomes($this);
    $this->actingAs($this->admin);

    Livewire::test(LeadOutcomesWidget::class)
        ->assertSee('Seguimiento de consultas')
        ->assertSee('40,0%')
        ->assertSee('tab=perdidas', escape: false)
        ->set('period', 'last_month')
        ->assertSee('01/10/2026')
        ->assertSee('filters%5Bcreated_at%5D%5Bcreated_from%5D=2026-10-01', escape: false);
});

it('lists the lost inquiries of the period on the lost tab', function () {
    $leads = seedOutcomes($this);
    $this->actingAs($this->admin);

    Livewire::withQueryParams(['tab' => 'perdidas'])
        ->test(ListLeads::class)
        ->filterTable('created_at', ['created_from' => '2026-11-01', 'created_until' => '2026-11-30'])
        ->assertCanSeeTableRecords([$leads['lost']])
        ->assertCanNotSeeTableRecords([$leads['soldThenClosed'], $leads['lostLastMonth'], $leads['open'], $leads['won']]);
});

it('lists the open inquiries of a seller on the open tab', function () {
    $leads = seedOutcomes($this);
    $this->actingAs($this->admin);

    Livewire::withQueryParams(['tab' => 'abiertas'])
        ->test(ListLeads::class)
        ->filterTable('agent_id', $this->seller->id)
        ->assertCanSeeTableRecords([$leads['quoted'], $leads['openLastMonth']])
        ->assertCanNotSeeTableRecords([$leads['open'], $leads['soldThenClosed'], $leads['lost']]);
});
