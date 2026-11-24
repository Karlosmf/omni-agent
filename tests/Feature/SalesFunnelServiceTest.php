<?php

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Pages\AnalyticsDashboard;
use App\Models\Booking;
use App\Models\Lead;
use App\Models\User;
use App\Services\SalesFunnelService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('counts inquiries, quotes and sales with their rates', function () {
    Lead::factory()->count(2)->create();
    $lead = Lead::factory()->create();
    Booking::factory()->create(['lead_id' => $lead->id, 'status' => BookingStatus::Presupuesto]);
    Booking::factory()->create(['lead_id' => $lead->id, 'status' => BookingStatus::Senado]);
    Booking::factory()->create(['lead_id' => $lead->id, 'status' => BookingStatus::Emitido]);
    Booking::factory()->create(['lead_id' => $lead->id, 'status' => BookingStatus::Cancelado]);

    expect(app(SalesFunnelService::class)->totals())->toMatchArray([
        'quotes' => 4,
        'sales' => 2,
        'sale_rate' => 50.0,
    ])->and(app(SalesFunnelService::class)->totals()['leads'])->toBe(Lead::count());
});

it('adds up confirmed usd sales of a period only', function () {
    $this->travelTo(now()->setDate(2026, 11, 20));
    Booking::factory()->create(['status' => BookingStatus::Senado, 'currency' => 'USD', 'total_sell' => 1000, 'total_cost' => 800]);
    Booking::factory()->create(['status' => BookingStatus::Presupuesto, 'currency' => 'USD', 'total_sell' => 5000, 'total_cost' => 4000]);
    Booking::factory()->create(['status' => BookingStatus::Emitido, 'currency' => 'USD', 'total_sell' => 300, 'total_cost' => 200])
        ->forceFill(['created_at' => '2026-10-10'])->saveQuietly();

    $service = app(SalesFunnelService::class);

    expect($service->usdSales(now()->startOfMonth()))->toBe(['sell' => 1000.0, 'cost' => 800.0])
        ->and($service->usdSales())->toBe(['sell' => 1300.0, 'cost' => 1000.0]);
});

it('shows the metrics page with its widgets', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

    $this->get(AnalyticsDashboard::getUrl())->assertOk()->assertSeeLivewire('app.filament.admin.widgets.lead-sources-chart');
});
