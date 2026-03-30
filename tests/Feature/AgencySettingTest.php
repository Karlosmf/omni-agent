<?php

use App\Models\AgencySetting;
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

it('has the ManageAgencySettings Filament page', function () {
    $this->actingAs(\App\Models\User::factory()->create());
    
    // Check if the class exists and can be resolved
    $page = new \App\Filament\Admin\Pages\ManageAgencySettings();
    expect($page)->toBeInstanceOf(\Filament\Pages\Page::class);
});
