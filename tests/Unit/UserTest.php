<?php

use App\Models\User;
use App\Enums\UserRole;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;


uses(Tests\TestCase::class, RefreshDatabase::class);

test('admin can access admin panel', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('getId')->andReturn('admin');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('customer cannot access admin panel', function () {
    $user = User::factory()->create(['role' => UserRole::Customer]);
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('getId')->andReturn('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('customer can access customer panel', function () {
    $user = User::factory()->create(['role' => UserRole::Customer]);
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('getId')->andReturn('customer');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

test('admin cannot access customer panel', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('getId')->andReturn('customer');

    expect($user->canAccessPanel($panel))->toBeFalse();
});
