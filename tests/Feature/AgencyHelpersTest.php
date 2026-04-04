<?php

use App\Models\AgencySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns default logo when no setting exists', function () {
    AgencySetting::truncate();
    
    expect(get_agency_logo())->toContain('images/branding/logo-full.png');
});

it('returns user uploaded logo when setting exists', function () {
    AgencySetting::factory()->create([
        'logo_path' => 'agency/custom-logo.png'
    ]);
    
    expect(get_agency_logo())->toContain('storage/agency/custom-logo.png');
});

it('returns absolute path for PDF generation', function () {
    AgencySetting::factory()->create([
        'logo_path' => 'agency/custom-logo.png'
    ]);
    
    expect(get_agency_logo_path())->toContain('storage/app/public/agency/custom-logo.png');
});

it('returns default favicon when no setting exists', function () {
    AgencySetting::truncate();
    
    expect(get_agency_favicon())->toContain('favicon.ico');
});
