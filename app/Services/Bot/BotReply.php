<?php

namespace App\Services\Bot;

use App\Models\Lead;

/**
 * What the bot decided to answer, and on which inquiry.
 */
class BotReply
{
    /**
     * @param  list<string>  $buttons  Reply buttons under the text, when the question has closed answers.
     */
    public function __construct(
        public Lead $lead,
        public string $text,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?string $model = null,
        public array $buttons = [],
    ) {}
}
