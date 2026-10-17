<?php

namespace App\Console\Commands;

use App\Enums\Currency;
use App\Enums\RateSource;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Services\CurrencyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecalculateBookings extends Command
{
    protected $signature = 'omni:recalculate-bookings
        {--apply : Write the changes. Without it nothing is modified (dry run)}
        {--force : Skip the backup confirmation (for cron or panels without a terminal)}
        {--estimate-rates= : Fill missing ARS/other rates with today\'s dollar (oficial, mep or tarjeta), marked as estimated}
        {--csv= : Path of the CSV report, relative to storage/app}';

    protected $description = 'Recalculate booking totals in USD using per-item and per-payment exchange rates. Dry run by default.';

    /** @var list<array<string, mixed>> */
    protected array $rows = [];

    public function handle(CurrencyService $currencies): int
    {
        $apply = (bool) $this->option('apply');
        $estimate = $this->option('estimate-rates') ? RateSource::tryFrom((string) $this->option('estimate-rates')) : null;

        if ($this->option('estimate-rates') && ($estimate === null || $estimate === RateSource::Custom)) {
            $this->error('--estimate-rates must be one of: oficial, mep, tarjeta.');

            return self::FAILURE;
        }

        if ($apply && ! $this->option('force') && ! $this->confirm('¿Hiciste un backup de la base de datos antes de aplicar los cambios?')) {
            $this->warn('Cancelado. Hacé el backup y volvé a correr el comando.');

            return self::FAILURE;
        }

        $skipped = 0;
        $changed = 0;

        Booking::with(['items', 'transactions'])->orderBy('id')->each(function (Booking $booking) use ($currencies, $estimate, $apply, &$skipped, &$changed) {
            $plan = $this->planBooking($booking, $currencies, $estimate);
            $this->rows[] = $plan['row'];

            if ($plan['row']['status'] === 'missing_rate') {
                $skipped++;

                return;
            }

            if ($plan['row']['status'] === 'changes') {
                $changed++;

                if ($apply) {
                    $this->applyPlan($booking, $plan);
                }
            }
        });

        $this->writeCsv();

        $this->table(
            ['Expediente', 'Estado', 'Venta antes', 'Venta después', 'Costo antes', 'Costo después', 'Ítems/cobros sin cotización'],
            array_map(fn (array $r) => [$r['file_number'], $r['status'], $r['sell_before'], $r['sell_after'], $r['cost_before'], $r['cost_after'], $r['missing']], array_filter($this->rows, fn (array $r) => in_array($r['status'], ['changes', 'missing_rate'], true)))
        );

        $this->info(($apply ? 'Aplicado' : 'Simulación (nada se modificó)').": {$changed} con cambios, {$skipped} sin cotización, ".(count($this->rows) - $changed - $skipped).' sin diferencias o sin ítems (no se tocan).');

        if ($skipped > 0) {
            $this->warn('Los expedientes "missing_rate" no se tocan: cargá la cotización a mano o usá --estimate-rates=oficial|mep|tarjeta (quedan marcadas como estimadas).');
        }

        if (! $apply && $changed > 0) {
            $this->line('Para aplicar: php artisan omni:recalculate-bookings --apply');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{row: array<string, mixed>, itemRates: array<int, array{rate: float, source: string}>, txs: array<int, array{rate: float|null, source: string|null, usd: float}>, totals: array<string, float>}
     */
    protected function planBooking(Booking $booking, CurrencyService $currencies, ?RateSource $estimate): array
    {
        if ($booking->items->isEmpty()) {
            // Nothing to recalculate from: keep the totals that were typed in by hand.
            return [
                'row' => [
                    'file_number' => $booking->file_number,
                    'status' => 'no_items',
                    'sell_before' => (float) $booking->total_sell,
                    'sell_after' => (float) $booking->total_sell,
                    'cost_before' => (float) $booking->total_cost,
                    'cost_after' => (float) $booking->total_cost,
                    'missing' => '',
                ],
                'itemRates' => [],
                'txs' => [],
                'totals' => [],
            ];
        }

        $missing = [];
        $itemRates = [];
        $itemsForTotals = [];

        foreach ($booking->items as $item) {
            $currency = $item->currency instanceof Currency ? $item->currency : Currency::tryFrom((string) $item->currency);
            $rate = (float) $item->exchange_rate;

            if ($currency !== null && $currency !== Currency::USD && ($rate <= 0 || $rate === 1.0)) {
                $estimated = $estimate ? $currencies->unitsPerUsd($currency, $estimate) : null;

                if (! $estimated) {
                    $missing[] = "item#{$item->id} ({$currency->value})";

                    continue;
                }

                $itemRates[$item->id] = ['rate' => $estimated, 'source' => $estimate->value.' (estimada)'];
                $rate = $estimated;
            }

            $itemsForTotals[] = ['currency' => $currency, 'exchange_rate' => $rate, 'cost' => $item->cost, 'sell' => $item->sell];
        }

        $txs = [];
        foreach ($booking->transactions as $transaction) {
            $currency = $transaction->currency instanceof Currency ? $transaction->currency : Currency::tryFrom((string) $transaction->currency);
            $rate = (float) $transaction->exchange_rate;
            $source = null;

            if ($currency !== null && $currency !== Currency::USD && ($rate <= 0 || $rate === 1.0)) {
                $rate = (float) ($estimate ? $currencies->unitsPerUsd($currency, $estimate) : 0);
                $source = $estimate?->value.' (estimada)';
            }

            $usd = CurrencyService::toUsd($transaction->amount, $currency, $rate);

            if ($usd === null) {
                $missing[] = "cobro/pago#{$transaction->id} ({$currency?->value})";

                continue;
            }

            if ($usd !== round((float) $transaction->amount_usd_fixed, 2)) {
                $txs[$transaction->id] = ['rate' => $rate ?: null, 'source' => $source, 'usd' => $usd];
            }
        }

        $totalCost = round(collect($itemsForTotals)->sum(fn ($i) => CurrencyService::toUsd($i['cost'], $i['currency'], $i['exchange_rate']) ?? 0), 2);
        $totalSell = round(collect($itemsForTotals)->sum(fn ($i) => CurrencyService::toUsd($i['sell'], $i['currency'], $i['exchange_rate']) ?? 0), 2);

        $changed = $missing === [] && (
            round((float) $booking->total_sell, 2) !== $totalSell
            || round((float) $booking->total_cost, 2) !== $totalCost
            || (string) ($booking->currency instanceof Currency ? $booking->currency->value : $booking->currency) !== 'USD'
            || $txs !== []
            || $itemRates !== []
        );

        return [
            'row' => [
                'file_number' => $booking->file_number,
                'status' => $missing !== [] ? 'missing_rate' : ($changed ? 'changes' : 'ok'),
                'sell_before' => (float) $booking->total_sell,
                'sell_after' => $totalSell,
                'cost_before' => (float) $booking->total_cost,
                'cost_after' => $totalCost,
                'missing' => implode(', ', $missing),
            ],
            'itemRates' => $itemRates,
            'txs' => $txs,
            'totals' => ['total_cost' => $totalCost, 'total_sell' => $totalSell, 'profit' => round($totalSell - $totalCost, 2)],
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    protected function applyPlan(Booking $booking, array $plan): void
    {
        DB::transaction(function () use ($booking, $plan) {
            $before = ['total_cost' => (float) $booking->total_cost, 'total_sell' => (float) $booking->total_sell, 'profit' => (float) $booking->profit];

            foreach ($plan['itemRates'] as $itemId => $info) {
                $booking->items->firstWhere('id', $itemId)->forceFill(['exchange_rate' => $info['rate'], 'rate_source' => RateSource::Custom])->saveQuietly();
            }

            foreach ($plan['txs'] as $transactionId => $info) {
                $transaction = $booking->transactions->firstWhere('id', $transactionId);
                $transaction->forceFill(array_filter([
                    'exchange_rate' => $info['rate'],
                    'rate_source' => $info['source'] ? RateSource::Custom : null,
                    'amount_usd_fixed' => $info['usd'],
                ], fn ($v) => $v !== null))->saveQuietly();
            }

            $booking->updateQuietly(['currency' => Currency::USD->value] + $plan['totals']);

            BookingActivity::create([
                'booking_id' => $booking->id,
                'type' => 'recalculated',
                'description' => 'Totales recalculados en USD',
                'properties' => ['before' => $before, 'after' => $plan['totals'], 'estimated_rates' => array_values(array_column($plan['itemRates'], 'source'))],
            ]);
        });
    }

    protected function writeCsv(): void
    {
        $path = $this->option('csv') ?: 'reports/recalculate-bookings-'.now()->format('Ymd-His').'.csv';
        $lines = [implode(',', array_keys($this->rows[0] ?? ['file_number' => '']))];

        foreach ($this->rows as $row) {
            $lines[] = implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row));
        }

        Storage::disk('local')->put($path, implode("\n", $lines));
        $this->line('Reporte CSV: storage/app/private/'.$path);
    }
}
