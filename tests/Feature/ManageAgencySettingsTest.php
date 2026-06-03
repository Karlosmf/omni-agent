<?php

use App\Enums\UserRole;
use App\Filament\Admin\Pages\ManageAgencySettings;
use App\Models\AgencySetting;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('can validate contact email in agency settings', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    actingAs($user);

    Livewire::test(ManageAgencySettings::class)
        ->set('data.contact_email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['data.contact_email']);
});

it('can save agency settings', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    actingAs($user);

    Livewire::test(ManageAgencySettings::class)
        ->set('data.company_name', 'Omni Agent Updated')
        ->set('data.ai_assistant_name', 'Brisa')
        ->set('data.contact_email', 'contact@omniagent.com')
        ->set('data.contact_phone', '123456789')
        ->set('data.fe_primary_color', '#ff0000')
        ->set('data.fe_secondary_color', '#00ff00')
        ->set('data.fe_accent_color', '#0000ff')
        ->set('data.fe_base_100_color', '#ffffff')
        ->set('data.fe_base_200_color', '#f2f2f2')
        ->set('data.fe_base_content_color', '#1f2937')
        ->set('data.fe_success_color', '#36d399')
        ->set('data.fe_error_color', '#f87272')
        ->set('data.fe_warning_color', '#fbbd23')
        ->set('data.fe_info_color', '#3abff8')
        ->set('data.be_primary_color', '#0000ff')
        ->set('data.be_gray_color', '#71717a')
        ->set('data.be_info_color', '#3b82f6')
        ->set('data.be_success_color', '#22c55e')
        ->set('data.be_warning_color', '#f59e0b')
        ->set('data.be_danger_color', '#ef4444')
        ->set('data.gemini_api_key', 'AIzaSyFakeKey...')
        ->call('save')
        ->assertHasNoErrors();

    $settings = AgencySetting::first();
    expect($settings->company_name)->toBe('Omni Agent Updated')
        ->and($settings->fe_primary_color)->toBe('#ff0000')
        ->and($settings->gemini_api_key)->toBe('AIzaSyFakeKey...');
});
