<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_packages', function (Blueprint $table) {
            // ── Suplemento Single ─────────────────────────────────────────────
            // Type: 'fixed' (flat amount) or 'percent' (% over base price)
            // Amount: the value (e.g. 300.00 or 50 for 50%)
            $table->string('single_supplement_type')->nullable()->after('price_basis_min'); // 'fixed' | 'percent'
            $table->decimal('single_supplement_amount', 10, 2)->nullable()->after('single_supplement_type');

            // ── Reducción Triple ──────────────────────────────────────────────
            // Percentage discount applied to the per-person price when occupying
            // a triple room. Negative semantics: a value of 10 means -10%.
            $table->decimal('triple_reduction_percent', 5, 2)->nullable()->after('single_supplement_amount');

            // ── Menores ───────────────────────────────────────────────────────
            // Each element: { max_age: int, type: 'free'|'percent'|'fixed', value: float }
            // e.g. [{"max_age": 2, "type": "free", "value": 0},
            //       {"max_age": 12, "type": "percent", "value": 75}]
            $table->json('children_policies')->nullable()->after('triple_reduction_percent');

            // ── Temporadas ────────────────────────────────────────────────────
            // Each element: { name: string, from: date, to: date, price_from: float }
            // When a travel_date falls within a season, that season's price_from is used.
            $table->json('seasons')->nullable()->after('children_policies');
        });
    }

    public function down(): void
    {
        Schema::table('travel_packages', function (Blueprint $table) {
            $table->dropColumn([
                'single_supplement_type',
                'single_supplement_amount',
                'triple_reduction_percent',
                'children_policies',
                'seasons',
            ]);
        });
    }
};
