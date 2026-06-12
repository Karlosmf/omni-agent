<?php

use App\Actions\Leads\ProcessChatbotInteractionAction;
use App\Models\Lead;
use App\Models\User;
use App\Services\AiConciergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it processes chat and extracts data', function () {
    $customer = User::factory()->create(['name' => 'Web Guest', 'role' => \App\Enums\UserRole::Customer]);
    $lead = Lead::factory()->create([
        'customer_id' => $customer->id,
        'ai_data' => [],
    ]);

    $mockAiService = Mockery::mock(AiConciergeService::class);
    $mockAiService->shouldReceive('processMessage')
        ->once()
        ->with('Hola quiero ir a paris', $lead)
        ->andReturn('¡Qué lindo destino!');

    $mockAiService->shouldReceive('extractLeadData')
        ->once()
        ->andReturn([
            'destino' => 'Paris',
            'presupuesto' => '$1000',
            'nombre' => 'Juan',
            'requiere_atencion' => true,
        ]);

    $action = new ProcessChatbotInteractionAction($mockAiService);

    $reply = $action->execute('Hola quiero ir a paris', $lead, []);

    expect($reply)->toBe('¡Qué lindo destino!');

    $lead->refresh();
    expect($lead->customer->name)->toBe('Juan')
        ->and($lead->needs_human_attention)->toBeTrue()
        ->and($lead->ai_data)->toHaveKey('destino', 'Paris');
});

test('it handles exceptions gracefully', function () {
    $lead = Lead::factory()->create();

    $mockAiService = Mockery::mock(AiConciergeService::class);
    $mockAiService->shouldReceive('processMessage')
        ->andThrow(new Exception('Service Error'));

    Log::shouldReceive('error')->once();

    $action = new ProcessChatbotInteractionAction($mockAiService);

    $reply = $action->execute('Hola', $lead, []);

    expect($reply)->toBe('Disculpá, tuve una pequeña desconexión. ¿Me lo repetís?');
});
