<?php

use App\Enums\LeadTemperature;
use App\Models\Lead;
use App\Services\AiConciergeService;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('it processes message successfully and saves history', function () {
    // Create Lead
    $lead = Lead::factory()->create();

    // Mock Gemini
    $mockResponse = Mockery::mock();
    $mockResponse->shouldReceive('text')->andReturn('Respuesta simulada de Gemini');

    $mockModel = Mockery::mock();
    $mockModel->shouldReceive('generateContent')->once()->andReturn($mockResponse);

    $mockClient = Mockery::mock();
    $mockClient->shouldReceive('generativeModel')->with('models/gemini-flash-latest')->once()->andReturn($mockModel);

    Gemini::swap($mockClient);

    $service = new AiConciergeService;
    $response = $service->processMessage('Hola', $lead);

    expect($response)->toBe('Respuesta simulada de Gemini');

    // Assert Messages Saved
    $this->assertDatabaseHas('messages', [
        'lead_id' => $lead->id,
        'role' => 'user',
        'content' => 'Hola',
    ]);

    $this->assertDatabaseHas('messages', [
        'lead_id' => $lead->id,
        'role' => 'assistant',
        'content' => 'Respuesta simulada de Gemini',
    ]);
});

test('it updates temperature to warm on keywords', function () {
    $lead = Lead::factory()->create(['temperature' => LeadTemperature::Cool]);

    // Mock Gemini (simplified)
    $mockResponse = Mockery::mock();
    $mockResponse->shouldReceive('text')->andReturn('Ok');
    $mockModel = Mockery::mock();
    $mockModel->shouldReceive('generateContent')->andReturn($mockResponse);
    $mockClient = Mockery::mock();
    $mockClient->shouldReceive('generativeModel')->andReturn($mockModel);
    Gemini::swap($mockClient);

    $service = new AiConciergeService;
    $service->processMessage('Tengo flexibilidad en la fecha', $lead);

    expect($lead->fresh()->temperature)->toBe(LeadTemperature::Warm);
});

test('it updates temperature to hot on human request', function () {
    $lead = Lead::factory()->create(['temperature' => LeadTemperature::Cool]);

    // Mock Gemini
    $mockResponse = Mockery::mock();
    $mockResponse->shouldReceive('text')->andReturn('Ok');
    $mockModel = Mockery::mock();
    $mockModel->shouldReceive('generateContent')->andReturn($mockResponse);
    $mockClient = Mockery::mock();
    $mockClient->shouldReceive('generativeModel')->andReturn($mockModel);
    Gemini::swap($mockClient);

    $service = new AiConciergeService;
    $service->processMessage('Quiero hablar con un humano', $lead);

    $lead->refresh();
    expect($lead->temperature)->toBe(LeadTemperature::Hot)
        ->and($lead->needs_human_attention)->toBeTrue();
});

test('it handles errors gracefully', function () {
    $lead = Lead::factory()->create();
    Log::shouldReceive('error')->once();

    $mockClient = Mockery::mock();
    $mockClient->shouldReceive('generativeModel')->andThrow(new Exception('API Error'));

    Gemini::swap($mockClient);

    $service = new AiConciergeService;
    $response = $service->processMessage('Hola', $lead);

    expect($response)->toBe('Disculpá, estoy teniendo un pequeño problema técnico. ¿Podés intentar de nuevo en unos segundos? 🙏');
});
