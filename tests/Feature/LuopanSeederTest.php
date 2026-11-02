<?php

use App\Models\WhatsAppTemplate;
use Database\Seeders\LuopanSeeder;

test('the Luopan templates are saved inactive and keep their switch when seeded again', function () {
    $seeder = new LuopanSeeder;
    $seedTemplates = fn () => (new ReflectionMethod($seeder, 'whatsAppTemplates'))->invoke($seeder);

    $seedTemplates();

    expect(WhatsAppTemplate::count())->toBe(3)
        ->and(WhatsAppTemplate::where('is_active', true)->count())->toBe(0);

    WhatsAppTemplate::where('purpose', 'quote_ready')->update(['is_active' => true]);
    $seedTemplates();

    expect(WhatsAppTemplate::count())->toBe(3)
        ->and(WhatsAppTemplate::forPurpose('quote_ready'))->not->toBeNull()
        ->and(WhatsAppTemplate::forPurpose('installment_reminder'))->toBeNull();
});

test('each Luopan template body uses exactly the variables it declares', function () {
    $seeder = new LuopanSeeder;
    (new ReflectionMethod($seeder, 'whatsAppTemplates'))->invoke($seeder);

    WhatsAppTemplate::all()->each(function (WhatsAppTemplate $template) {
        preg_match_all('/\{\{(\d+)\}\}/', $template->body, $matches);

        expect(array_unique($matches[1]))->toHaveCount($template->variables_count)
            ->and($template->body)->not->toStartWith('{{')
            ->and($template->body)->not->toEndWith('}}');
    });
});
