<?php

namespace App\Console\Commands;

use App\Models\BookingItem;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToSuppliers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-to-suppliers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert string supplier_names to Supplier entities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Migrating Suppliers...');

        // Get unique supplier names from items that don't have an ID yet
        $items = BookingItem::whereNull('supplier_id')->whereNotNull('supplier_name')->get();
        $count = 0;

        foreach ($items as $item) {
            $name = trim($item->supplier_name);
            if (empty($name)) {
                continue;
            }

            DB::transaction(function () use ($item, $name, &$count) {
                $supplier = Supplier::firstOrCreate(
                    ['name' => $name],
                    ['category' => 'General']
                );

                $item->supplier_id = $supplier->id;
                $item->save();
                $count++;
            });
        }

        $this->info("Linked {$count} items to Suppliers.");
    }
}
