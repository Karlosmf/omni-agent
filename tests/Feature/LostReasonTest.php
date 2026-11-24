<?php

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\LostReason;
use App\Enums\UserRole;
use App\Filament\Admin\Pages\LeadBoard;
use App\Filament\Admin\Resources\Leads\Pages\EditLead;
use App\Filament\Admin\Resources\Leads\Pages\ListLeads;
use App\Filament\Admin\Widgets\Analytics\LeadOutcomesWidget;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\User;
use App\Support\LeadOutcomeStats;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

function unsoldLead(LeadStatus $status = LeadStatus::Contacted): Lead
{
    return Lead::factory()->create(['status' => $status, 'agent_id' => null]);
}

it('marks an inquiry as lost with its reason, and forgets the reason when it is reopened', function () {
    $lead = unsoldLead();

    $lead->markAsLost(LostReason::Price, 'Le pareció caro');

    expect($lead->fresh())
        ->status->toBe(LeadStatus::Closed)
        ->lost_reason->toBe(LostReason::Price)
        ->lost_note->toBe('Le pareció caro')
        ->lost_at->not->toBeNull();

    $lead->update(['status' => LeadStatus::Contacted]);

    expect($lead->fresh())
        ->lost_reason->toBeNull()
        ->lost_note->toBeNull()
        ->lost_at->toBeNull();
});

it('marks an inquiry as lost from its page, and asks for a detail when the reason is "Otro"', function () {
    $lead = unsoldLead();

    Livewire::test(EditLead::class, ['record' => $lead->id])
        ->callAction('mark_lost', data: ['lost_reason' => LostReason::Other->value, 'lost_note' => null])
        ->assertHasFormErrors(['lost_note' => 'required']);

    expect($lead->fresh()->status)->toBe(LeadStatus::Contacted);

    Livewire::test(EditLead::class, ['record' => $lead->id])
        ->callAction('mark_lost', data: ['lost_reason' => LostReason::OtherAgency->value])
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($lead->fresh())
        ->status->toBe(LeadStatus::Closed)
        ->lost_reason->toBe(LostReason::OtherAgency);
});

it('does not offer to lose a sold inquiry', function () {
    $sold = unsoldLead(LeadStatus::Quoted);
    Booking::factory()->create(['lead_id' => $sold->id, 'status' => BookingStatus::Senado]);
    $open = unsoldLead();

    Livewire::test(ListLeads::class, ['activeTab' => 'todas'])
        ->assertActionHidden(TestAction::make('mark_lost')->table($sold))
        ->assertActionVisible(TestAction::make('mark_lost')->table($open));
});

it('asks why when an unsold card is dropped on "Cerrada" in the board', function () {
    $lead = unsoldLead();

    Livewire::test(LeadBoard::class)
        ->call('moveLead', $lead->id, LeadStatus::Closed->value)
        ->assertActionMounted('markLost')
        ->tap(fn () => expect($lead->fresh()->status)->toBe(LeadStatus::Contacted))
        ->fillForm(['lost_reason' => LostReason::NoReply->value])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    expect($lead->fresh())
        ->status->toBe(LeadStatus::Closed)
        ->lost_reason->toBe(LostReason::NoReply);
});

it('closes a sold card in the board without asking', function () {
    $lead = unsoldLead(LeadStatus::Quoted);
    Booking::factory()->create(['lead_id' => $lead->id, 'status' => BookingStatus::Emitido]);

    Livewire::test(LeadBoard::class)
        ->call('moveLead', $lead->id, LeadStatus::Closed->value)
        ->assertActionNotMounted('markLost');

    expect($lead->fresh())
        ->status->toBe(LeadStatus::Closed)
        ->lost_reason->toBeNull();
});

it('counts the lost inquiries of the period by reason and lists them filtered', function () {
    unsoldLead()->markAsLost(LostReason::Price);
    unsoldLead()->markAsLost(LostReason::Price);
    $dates = unsoldLead();
    $dates->markAsLost(LostReason::Dates);
    $withoutReason = unsoldLead(LeadStatus::Closed);
    $old = unsoldLead();
    $old->markAsLost(LostReason::Dates);
    $old->forceFill(['created_at' => now()->subYear()])->saveQuietly();

    expect(LeadOutcomeStats::forPeriod('this_month', viewer: $this->admin)->byLostReason())->toBe([
        ['lost_reason' => 'price', 'label' => 'Precio', 'lost' => 2, 'share' => 50.0],
        ['lost_reason' => 'dates', 'label' => 'Fechas o disponibilidad', 'lost' => 1, 'share' => 25.0],
        ['lost_reason' => null, 'label' => 'Sin motivo', 'lost' => 1, 'share' => 25.0],
    ]);

    Livewire::test(LeadOutcomesWidget::class)
        ->assertSee('Motivos de pérdida')
        ->assertSee('filters%5Blost_reason%5D%5Bvalue%5D=dates', escape: false);

    Livewire::test(ListLeads::class, ['activeTab' => 'perdidas'])
        ->filterTable('lost_reason', LostReason::Dates->value)
        ->assertCanSeeTableRecords([$dates, $old])
        ->assertCanNotSeeTableRecords([$withoutReason]);
});
