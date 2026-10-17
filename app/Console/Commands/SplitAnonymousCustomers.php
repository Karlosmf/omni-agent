<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SplitAnonymousCustomers extends Command
{
    protected $signature = 'omni:split-anonymous-customers {--apply : Write the changes. Without it nothing is modified (dry run)}';

    protected $description = 'Separa los leads anónimos que quedaron juntos en un solo cliente ("Sin teléfono" / "Web-Form" / "No provisto").';

    /** @var list<string> */
    private const PLACEHOLDERS = ['Sin teléfono', 'Sin telefono', 'Web-Form', 'No provisto'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $merged = User::where('role', UserRole::Customer)->whereIn('phone', self::PLACEHOLDERS)->get();
        $moved = 0;

        foreach ($merged as $user) {
            $leads = Lead::where('customer_id', $user->id)->get();
            $this->line("Cliente #{$user->id} ({$user->name}): {$leads->count()} lead(s) con teléfono «{$user->phone}».");

            if (! $apply) {
                $moved += max(0, $leads->count() - 1);

                continue;
            }

            DB::transaction(function () use ($leads, $user, &$moved) {
                foreach ($leads as $lead) {
                    $email = $lead->ai_data['email'] ?? null;

                    $guest = User::forceCreate([
                        'name' => $lead->customer_name ?: 'Web Guest',
                        'email' => $email && ! User::where('email', $email)->exists() ? $email : null,
                        'phone' => null,
                        'role' => UserRole::Customer,
                        'is_guest' => true,
                        'password' => Hash::make(Str::random(16)),
                    ]);
                    $lead->update(['customer_id' => $guest->id]);
                    $moved++;
                }

                $user->update(['phone' => null]);
            });
        }

        $this->info(($apply ? 'Aplicado' : 'Simulación (nada se modificó)').": {$merged->count()} cliente(s) compartido(s), {$moved} lead(s) separados.");

        if (! $apply && $merged->isNotEmpty()) {
            $this->line('Para aplicar: php artisan omni:split-anonymous-customers --apply');
        }

        return self::SUCCESS;
    }
}
