<?php

namespace App\Console\Commands;

use App\Models\FollowUpRule;
use App\Services\FollowUpService;
use Illuminate\Console\Command;

class RunFollowUps extends Command
{
    protected $signature = 'omni:run-follow-ups {--baseline : Only mark what already matches as handled, without creating tasks or notifications}';

    protected $description = 'Evalúa las reglas de seguimiento de leads y presupuestos (cada regla se dispara una sola vez por lead/presupuesto).';

    public function handle(FollowUpService $followUps): int
    {
        $fired = $followUps->run((bool) $this->option('baseline'));

        foreach (FollowUpRule::whereIn('id', array_keys($fired))->get() as $rule) {
            $this->line("  {$rule->name}: {$fired[$rule->id]}");
        }

        $this->info(($this->option('baseline') ? 'Registrados como ya atendidos (sin avisos): ' : 'Seguimientos generados: ').array_sum($fired));

        return self::SUCCESS;
    }
}
