<?php

namespace App\Console\Commands;

use App\Services\SlaService;
use Illuminate\Console\Command;

class CheckLeadSla extends Command
{
    protected $signature = 'omni:check-sla';

    protected $description = 'Avisa al equipo cuando un lead urgente espera demasiado la primera respuesta de una persona.';

    public function handle(SlaService $sla): int
    {
        $this->info('Leads avisados: '.$sla->alertOverdue());

        return self::SUCCESS;
    }
}
