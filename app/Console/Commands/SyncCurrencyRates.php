<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncCurrencyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-currency-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs currency exchange rates from BNA to local JSON storage.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\CurrencyService $service)
    {
        $this->info('Fetching currency rates from BNA...');

        if ($service->updateLocalRates()) {
            $data = $service->getAllData();
            $rows = [];

            foreach ($data['currencies'] as $key => $rate) {
                if (in_array($key, ['USD', 'EUR', 'BRL'])) {
                    $rows[] = [$key, $rate['buy'], $rate['sell']];
                }
            }

            $this->table(['Currency', 'Buy', 'Sell'], $rows);
            $this->info('Rates synced successfully. Updated at: '.$data['updated_at']);
        } else {
            $this->error('Failed to sync rates from BNA. Check logs for details.');

            return 1;
        }

        return 0;
    }
}
