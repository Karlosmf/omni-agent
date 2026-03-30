<?php

use App\Models\AgencySetting;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can store agency settings', function () {
    $settings = AgencySetting::updateOrCreate(
        ['id' => 1],
        [
            'company_name' => 'Test Agency',
            'contact_email' => 'test@example.com',
            'contact_phone' => '123456789',
            'primary_color' => '#ff0000',
            'social_links' => [['platform' => 'FB', 'url' => 'fb.com']],
        ]
    );

    expect(AgencySetting::first()->company_name)->toBe('Test Agency');
    expect(AgencySetting::first()->social_links)->toBeArray()->toHaveCount(1);
});

it('converts hex to oklch format', function () {
    $hex = '#1a56db';
    $oklch = hex_to_oklch($hex);
    
    // L C H format (approx)
    expect($oklch)->toMatch('/^\d+\.\d+ \d+\.\d+ \d+\.\d+$/');
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
