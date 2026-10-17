<?php

use App\Enums\UserRole;
use App\Filament\Admin\Widgets\CronStatusWidget;
use App\Models\User;
use App\Support\CronHeartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;

test('the scheduler runs the heartbeat and drains the queue every minute', function () {
    $events = collect(app(Schedule::class)->events());

    $queue = $events->first(fn ($event) => str_contains((string) $event->command, 'queue:work'));
    $heartbeat = $events->first(fn ($event) => $event->description === 'cron-heartbeat');

    expect($queue)->not->toBeNull()
        ->and($queue->expression)->toBe('* * * * *')
        ->and($queue->command)->toContain('--stop-when-empty')
        ->and($queue->withoutOverlapping)->toBeTrue()
        ->and($heartbeat)->not->toBeNull()
        ->and($heartbeat->expression)->toBe('* * * * *');
});

test('the cron heartbeat goes stale after five minutes', function () {
    Cache::forget(CronHeartbeat::KEY);
    expect(CronHeartbeat::isStale())->toBeTrue();

    CronHeartbeat::beat();
    expect(CronHeartbeat::isStale())->toBeFalse();

    $this->travel(6)->minutes();
    expect(CronHeartbeat::isStale())->toBeTrue();
});

test('only admins see the cron warning and only when the heartbeat is stale', function () {
    config(['queue.default' => 'database']);
    Cache::forget(CronHeartbeat::KEY);

    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    expect(CronStatusWidget::canView())->toBeTrue();

    CronHeartbeat::beat();
    expect(CronStatusWidget::canView())->toBeFalse();

    Cache::forget(CronHeartbeat::KEY);
    $this->actingAs(User::factory()->create(['role' => UserRole::Sales]));
    expect(CronStatusWidget::canView())->toBeFalse();
});

test('omni:doctor reports the missing cron with the command to create', function () {
    Cache::forget(CronHeartbeat::KEY);

    $this->artisan('omni:doctor')->expectsOutputToContain('schedule:run')->assertFailed();
});

test('make:deploy-zip rejects an unknown hosting profile', function () {
    $this->artisan('make:deploy-zip --host=nope --include-assets')->expectsOutputToContain('Perfil de hosting desconocido')->assertFailed();
});

test('every hosting profile has what the checklist needs', function () {
    foreach (config('omni-hosting.profiles') as $profile) {
        expect($profile)->toHaveKeys(['label', 'panel', 'cron_location', 'php_binary', 'public_dir']);
    }
});
