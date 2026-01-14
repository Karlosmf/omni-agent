<?php

use App\Services\FinanceService;

test('it converts ARS to USD correctly', function () {
    $service = new FinanceService;

    // 120000 ARS at 1200 rate should be 100 USD
    expect($service->convertToUsd(120000, 1200))->toBe(100.0);
});

test('it calculates profit correctly', function () {
    $service = new FinanceService;

    // Cost 1000, Sell 1250, Profit should be 250
    expect($service->calculateProfit(1000, 1250))->toBe(250.0);
});
