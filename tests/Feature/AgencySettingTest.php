<?php

use App\Models\AgencySetting;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can store agency settings with extended colors', function () {
    $settings = AgencySetting::updateOrCreate(
        ['id' => 1],
        [
            'company_name' => 'Test Agency',
            'contact_email' => 'test@example.com',
            'contact_phone' => '123456789',
            'primary_color' => '#ff0000',
            'success_color' => '#00ff00',
            'social_links' => [['platform' => 'FB', 'url' => 'fb.com', 'icon' => 'ph-facebook']],
        ]
    );

    $saved = AgencySetting::first();
    expect($saved->company_name)->toBe('Test Agency');
    expect($saved->success_color)->toBe('#00ff00');
});

it('converts hex to oklch format and handles null', function () {
    $hex = '#1a56db';
    $oklch = hex_to_oklch($hex);
    expect($oklch)->toMatch('/^\d+\.\d+ \d+\.\d+ \d+\.\d+$/');

    // Test null/empty handling
    expect(hex_to_oklch(null))->toBe("1.00 0.000 0.0");
    expect(hex_to_oklch(''))->toBe("1.00 0.000 0.0");
});

it('allows admin to access ManageAgencySettings Filament page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin);
    
    $canAccess = \App\Filament\Admin\Pages\ManageAgencySettings::canAccess();
    expect($canAccess)->toBeTrue();
});

it('denies non-admin access to ManageAgencySettings Filament page', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $this->actingAs($staff);
    
    $canAccess = \App\Filament\Admin\Pages\ManageAgencySettings::canAccess();
    expect($canAccess)->toBeFalse();
});
