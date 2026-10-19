<?php

use App\Actions\Leads\ProcessChatbotInteractionAction;
use App\Models\Lead;
use App\Services\AiConciergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it answers through the AI service in a single call', function () {
    $lead = Lead::factory()->create(['ai_data' => []]);

    $mockAiService = Mockery::mock(AiConciergeService::class);
    $mockAiService->shouldReceive('processMessage')
        ->once()
        ->with('Hola quiero ir a paris', $lead, true)
        ->andReturn('¡Qué lindo destino!');
    $mockAiService->shouldNotReceive('extractLeadData');

    $action = new ProcessChatbotInteractionAction($mockAiService);

    expect($action->execute('Hola quiero ir a paris', $lead, []))->toBe('¡Qué lindo destino!');
});

test('it passes along that the message was already stored', function () {
    $lead = Lead::factory()->create();

    $mockAiService = Mockery::mock(AiConciergeService::class);
    $mockAiService->shouldReceive('processMessage')->once()->with('Hola', $lead, false)->andReturn('Hola');

    expect((new ProcessChatbotInteractionAction($mockAiService))->execute('Hola', $lead, [], recordUserMessage: false))->toBe('Hola');
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
