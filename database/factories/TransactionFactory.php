<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Booking;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = fake()->randomElement(Currency::cases());
        $amount = fake()->randomFloat(2, 100, 2000);
        $rate = $currency === Currency::ARS ? 1200 : 1;
        $amountUsd = $currency === Currency::ARS ? $amount / $rate : $amount;

        return [
            'booking_id' => Booking::factory(),
            'financial_account_id' => FinancialAccount::inRandomOrder()->first()?->id,
            'transaction_category_id' => TransactionCategory::inRandomOrder()->first()?->id,
            'type' => fake()->randomElement(TransactionType::cases()),
            'currency' => $currency, // Eloquent handles Enum casting if defined in model
            'amount' => $amount,
            'exchange_rate' => $rate,
            'amount_usd_fixed' => $amountUsd,
            'method' => fake()->randomElement(['Cash', 'Bank Transfer', 'Credit Card']),
            'date' => fake()->date(),
            'notes' => fake()->sentence(),
        ];
    }
}
