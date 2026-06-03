<?php

use App\Actions\Leads\CaptureLeadAction;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it creates a new lead and links to a new user if email provided', function () {
    $action = app(CaptureLeadAction::class);

    $lead = $action->execute([
        'customer_name' => 'Juan Perez',
        'customer_phone' => '123456789',
        'customer_email' => 'juan@example.com',
        'source' => 'web_widget',
        'raw_message' => 'Test message',
    ]);

    expect($lead)->toBeInstanceOf(Lead::class)
        ->and($lead->customer_name)->toBe('Juan Perez')
        ->and($lead->customer_id)->not->toBeNull();

    $user = User::find($lead->customer_id);
    expect($user->email)->toBe('juan@example.com')
        ->and($user->role)->toBe(UserRole::Customer);
});

test('it links to existing user if email matches', function () {
    $existingUser = User::factory()->create([
        'email' => 'juan@example.com',
        'role' => UserRole::Customer,
    ]);

    $action = app(CaptureLeadAction::class);

    $lead = $action->execute([
        'customer_name' => 'Juan Perez',
        'customer_phone' => '123456789',
        'customer_email' => 'juan@example.com',
        'source' => 'web_widget',
        'raw_message' => 'Test message',
    ]);

    expect($lead->customer_id)->toBe($existingUser->id);
    expect(User::count())->toBe(1); // No new user created
});

test('it creates a lead and a user without email if no email is provided', function () {
    $action = app(CaptureLeadAction::class);

    $lead = $action->execute([
        'customer_name' => 'Web Guest',
        'customer_phone' => 'Sin teléfono',
        'source' => 'web_widget',
        'raw_message' => 'Test message',
    ]);

    expect($lead->customer_id)->not->toBeNull();
    $user = User::find($lead->customer_id);
    expect($user->email)->toBeNull()
        ->and($user->name)->toBe('Web Guest');
});
