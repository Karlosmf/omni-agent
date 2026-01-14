<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-to-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing Leads data to Customers table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration to Customers...');

        $leads = Lead::whereNull('customer_id')->get();
        $count = 0;

        foreach ($leads as $lead) {
            DB::transaction(function () use ($lead, &$count) {
                // Find or create customer by phone (primary key for us)
                $customer = Customer::firstOrCreate(
                    ['phone' => $lead->customer_phone],
                    [
                        'name' => $lead->customer_name,
                        // We don't have email in Lead model currently, only phone/name
                    ]
                );

                // Update Lead
                $lead->customer_id = $customer->id;
                $lead->save();

                // Update associated Booking if any
                if ($lead->booking) {
                    $lead->booking->customer_id = $customer->id;
                    $lead->booking->save();
                }

                $count++;
            });
        }

        $this->info("Migrated {$count} leads/bookings to Customers.");
    }
}
