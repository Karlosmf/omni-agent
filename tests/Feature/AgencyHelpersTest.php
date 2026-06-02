<?php

use App\Models\AgencySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns default logo when no setting exists', function () {
    AgencySetting::truncate();
    
    expect(get_agency_logotipo_url())->toContain('images/branding/logo-full.png');
});

it('returns user uploaded logo when setting exists', function () {
    AgencySetting::factory()->create([
        'logotipo_path' => 'custom-logotipo.png'
    ]);
    
    expect(get_agency_logotipo_url())->toContain('images/branding/custom-logotipo.png');
});

it('returns absolute path for PDF generation', function () {
    AgencySetting::factory()->create([
        'logotipo_path' => 'custom-logotipo.png'
    ]);
    
    expect(get_agency_logotipo_path())->toContain('images/branding/custom-logotipo.png');
});

it('returns default isotipo when no setting exists', function () {
    AgencySetting::truncate();
    
    expect(get_agency_isotipo_url())->toContain('images/branding/logo-icon.png');
});
