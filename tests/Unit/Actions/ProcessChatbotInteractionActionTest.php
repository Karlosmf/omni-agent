<?php

use App\Actions\Leads\ProcessChatbotInteractionAction;
use App\Models\Lead;
use App\Services\AiConciergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('it processes chat and extracts data', function () {
    $lead = Lead::factory()->create([
        'ai_data' => [],
        'customer_name' => 'Web Guest',
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
    expect($lead->customer_name)->toBe('Juan')
        ->and($lead->needs_human_attention)->toBeTrue()
        ->and($lead->ai_data)->toHaveKey('destino', 'Paris');
});

test('it handles exceptions gracefully', function () {
    $lead = Lead::factory()->create();

    $mockAiService = Mockery::mock(AiConciergeService::class);
    $mockAiService->shouldReceive('processMessage')
        ->andThrow(new \Exception('Service Error'));

    \Illuminate\Support\Facades\Log::shouldReceive('error')->once();

    $action = new ProcessChatbotInteractionAction($mockAiService);
    
    $reply = $action->execute('Hola', $lead, []);

    expect($reply)->toBe('Disculpá, tuve una pequeña desconexión. ¿Me lo repetís?');
});
