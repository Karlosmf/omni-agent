<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * One-off prompts with the agency's provider: the trip importer (text or PDF) and the "test connection" button.
 */
#[MaxTokens(8000)]
#[Temperature(0.2)]
#[Timeout(120)]
class TextAgent implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'Seguí exactamente lo que pide el usuario y respondé solo con eso, sin texto adicional.';
    }
}
