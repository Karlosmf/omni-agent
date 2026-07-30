<?php

use App\Enums\PriceBasis;
use App\Enums\UserRole;
use App\Models\ServiceType;
use App\Models\TravelPackage;
use App\Models\User;
use App\Services\BudgetGenerationService;
use Database\Seeders\ServiceTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Package-level basis (fallback when no service-level basis is defined)
// ─────────────────────────────────────────────────────────────────────────────

it('base doble: 1 pasajero paga por 2 slots (una base completa)', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $package = TravelPackage::factory()->create([
        'services' => [],
        'price_from' => 1000,
        'price_basis' => PriceBasis::BaseDoble,
        'price_basis_min' => 2,
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 1);

    // ceil(1/2)=1 unit × 2 slots → 1000×2 = 2000
    expect((float) $booking->total_sell)->toBe(2000.0)
        ->and($booking->passengers)->toBe(1);
});

it('base doble: 2 pasajeros pagan exactamente 1 base (2 slots)', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $package = TravelPackage::factory()->create([
        'services' => [],
        'price_from' => 1000,
        'price_basis' => PriceBasis::BaseDoble,
        'price_basis_min' => 2,
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 2);

    // ceil(2/2)=1 unit × 2 slots → 1000×2 = 2000
    expect((float) $booking->total_sell)->toBe(2000.0);
});

it('base doble: 3 pasajeros pagan 2 bases (4 slots) porque necesitan 2 cuartos', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $package = TravelPackage::factory()->create([
        'services' => [],
        'price_from' => 1000,
        'price_basis' => PriceBasis::BaseDoble,
        'price_basis_min' => 2,
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 3);

    // ceil(3/2)=2 units × 2 slots → 1000×4 = 4000
    expect((float) $booking->total_sell)->toBe(4000.0);
});

it('base doble: 4 pasajeros pagan exactamente 2 bases (4 slots)', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $package = TravelPackage::factory()->create([
        'services' => [],
        'price_from' => 1000,
        'price_basis' => PriceBasis::BaseDoble,
        'price_basis_min' => 2,
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 4);

    // ceil(4/2)=2 units × 2 slots → 1000×4 = 4000
    expect((float) $booking->total_sell)->toBe(4000.0);
});

it('base triple: 4 pasajeros pagan 2 bases (6 slots)', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $package = TravelPackage::factory()->create([
        'services' => [],
        'price_from' => 500,
        'price_basis' => PriceBasis::BaseTriple,
        'price_basis_min' => 3,
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 4);

    // ceil(4/3)=2 units × 3 slots → 500×6 = 3000
    expect((float) $booking->total_sell)->toBe(3000.0);
});

it('por persona: el total escala linealmente', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $package = TravelPackage::factory()->create([
        'services' => [],
        'price_from' => 800,
        'price_basis' => PriceBasis::PorPersona,
        'price_basis_min' => 1,
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 3);

    // 800×3 = 2400
    expect((float) $booking->total_sell)->toBe(2400.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Per-service basis (each service can override the package basis)
// ─────────────────────────────────────────────────────────────────────────────

it('servicios mixtos: vuelo (por_persona) + hotel (base_doble) + lancha (precio_fijo) con 3 pasajeros', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    // Package-level basis doesn't matter here because each service has its own
    $package = TravelPackage::factory()->create([
        'price_from' => 999, // indicative only
        'price_basis' => PriceBasis::PorPersona,
        'price_basis_min' => 1,
        'services' => [
            [
                'service_type_id' => ServiceType::where('key', 'flight')->value('id'),
                'description' => 'Vuelos ida y vuelta',
                'price_basis' => PriceBasis::PorPersona->value, // ×pax
                'currency' => 'USD',
                'cost' => 400.00,
                'sell' => 600.00,
            ],
            [
                'service_type_id' => ServiceType::where('key', 'hotel')->value('id'),
                'description' => 'Hotel 4 estrellas (doble)',
                'price_basis' => PriceBasis::BaseDoble->value, // ceil(3/2)=2 rooms × 2 = 4 slots
                'currency' => 'USD',
                'cost' => 200.00,
                'sell' => 300.00,
            ],
            [
                'service_type_id' => ServiceType::where('key', 'other')->value('id'),
                'description' => 'Día de pesca — lancha con guía',
                'price_basis' => PriceBasis::PrecioFijo->value, // multiplier=1, always fixed
                'currency' => 'USD',
                'cost' => 500.00,
                'sell' => 700.00,
            ],
        ],
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 3);

    // Vuelo:  600×3 = 1800  |  Hotel: 300×4 = 1200  |  Lancha: 700×1 = 700  → total sell = 3700
    // Cost:   400×3 = 1200  |  Hotel: 200×4 =  800  |  Lancha: 500×1 = 500  → total cost = 2500
    expect($booking->items)->toHaveCount(3)
        ->and((float) $booking->total_sell)->toBe(3700.0)
        ->and((float) $booking->total_cost)->toBe(2500.0);
});

it('precio_fijo no escala sin importar la cantidad de pasajeros', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $package = TravelPackage::factory()->create([
        'services' => [],
        'price_from' => 2000,
        'price_basis' => PriceBasis::PrecioFijo,
        'price_basis_min' => 1,
    ]);

    $booking1 = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 1);
    $booking2 = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 5);

    // Fixed price: same regardless of passengers
    expect((float) $booking1->total_sell)->toBe(2000.0)
        ->and((float) $booking2->total_sell)->toBe(2000.0);
});

it('servicio sin price_basis hereda la base del paquete', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    // Package is base_doble; service has no price_basis → inherits
    $package = TravelPackage::factory()->create([
        'price_from' => 500,
        'price_basis' => PriceBasis::BaseDoble,
        'price_basis_min' => 2,
        'services' => [
            [
                'service_type_id' => ServiceType::where('key', 'hotel')->value('id'),
                'description' => 'Hotel (sin base propia)',
                // no 'price_basis' key → should fallback to package basis (base_doble)
                'currency' => 'USD',
                'cost' => 100.00,
                'sell' => 150.00,
            ],
        ],
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 3);

    // ceil(3/2)=2 units × 2 = 4 slots → 150×4 = 600 sell, 100×4 = 400 cost
    expect((float) $booking->total_sell)->toBe(600.0)
        ->and((float) $booking->total_cost)->toBe(400.0);
});

it('clones package info into a summary item when no services are defined', function () {
    $this->seed(ServiceTypeSeeder::class);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $package = TravelPackage::factory()->create([
        'services' => [],
        'price_from' => 2000,
        'price_basis' => PriceBasis::PorPersona,
        'price_basis_min' => 1,
    ]);

    $booking = (new BudgetGenerationService)->clonePackageToBudget($package, $customer, passengers: 1);

    expect($booking->items)->toHaveCount(1)
        ->and((float) $booking->total_sell)->toBe(2000.0)
        ->and($booking->items->first()->service_type_id)->toBe(ServiceType::where('key', 'other')->value('id') ?? 1);
});
