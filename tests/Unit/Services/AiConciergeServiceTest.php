<?php

use App\Services\AiConciergeService;

test('it can instantiate the service', function () {
    $service = new AiConciergeService;
    expect($service)->toBeInstanceOf(AiConciergeService::class);
});

test('it returns a default message for now', function () {
    $service = new AiConciergeService;
    $response = $service->processMessage('Hola');

    expect($response)
        ->toBeString()
        ->toContain('via Gemini pronto');
});
