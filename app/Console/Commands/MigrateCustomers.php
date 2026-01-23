<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateCustomers extends Command
{
    protected $signature = 'crm:migrate-customers';

    protected $description = 'Migrate Users with customer role to Customer entity and re-link Bookings/Leads.';

    public function handle()
    {
        $this->info('Starting customer migration...');

        // Ensure we target the right role. Assuming 'customer' string or value.
        // In a real app we might inspect UserRole::Customer->value.
        // Based on seeder "role' => UserRole::Admin", I suspect it's an enum or string backed enum.
        // I will attempt to use the string 'customer' if it works, or check if I need to use Enum.
        // Ideally I should pull UserRole but I don't want to import it if I don't know the path/name exactly (Wait, I do know it: App\Enums\UserRole)

        $users = User::where('role', 'customer')->orWhere('role', \App\Enums\UserRole::Customer)->get();

        if ($users->isEmpty()) {
            // Fallback for string 'customer' if enum fails database query matching
            $users = User::where('role', 'customer')->get();
        }

        $this->info("Found {$users->count()} users to check/migrate.");

        DB::transaction(function () use ($users) {
            foreach ($users as $user) {
                $this->info("Migrating user: {$user->name} ({$user->email})");

                // Check if customer already exists (by email)
                $customer = Customer::firstOrCreate(
                    ['email' => $user->email],
                    [
                        'name' => $user->name,
                        'phone' => $user->phone ?? null, // Assuming User might have phone or we leave null
                    ]
                );

                // Update Bookings
                // customer_id on Bookings currently points to User ID. We switch it to Customer ID.
                $updatedBookings = Booking::where('customer_id', $user->id)->update(['customer_id' => $customer->id]);
                $this->info("  - Updated {$updatedBookings} bookings.");

                // Update Leads
                if (Schema::hasColumn('leads', 'customer_id')) {
                    // Ensure we only update if it pointed to the User ID.
                    // IMPORTANT: If customer_id was already pointing to User ID, we update it.
                    $updatedLeads = Lead::where('customer_id', $user->id)->update(['customer_id' => $customer->id]);
                    $this->info("  - Updated {$updatedLeads} leads.");
                }
            }
        });

        $this->info('Migration completed successfully.');
    }
}
