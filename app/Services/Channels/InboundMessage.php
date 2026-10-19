<?php

namespace App\Services\Channels;

use App\Enums\Channel;

/**
 * A customer message from a social channel, normalised from Meta's webhook payload.
 */
final readonly class InboundMessage
{
    /**
     * @param  array<string, mixed>|null  $referral  Ad / campaign info Meta attaches to the first message.
     */
    public function __construct(
        public Channel $channel,
        public string $accountExternalId,
        public string $senderId,
        public string $externalId,
        public string $text,
        public bool $isText = true,
        public ?string $profileName = null,
        public ?array $referral = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'channel' => $this->channel->value,
            'accountExternalId' => $this->accountExternalId,
            'senderId' => $this->senderId,
            'externalId' => $this->externalId,
            'text' => $this->text,
            'isText' => $this->isText,
            'profileName' => $this->profileName,
            'referral' => $this->referral,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Channel::from($data['channel']),
            $data['accountExternalId'],
            $data['senderId'],
            $data['externalId'],
            $data['text'],
            $data['isText'] ?? true,
            $data['profileName'] ?? null,
            $data['referral'] ?? null,
        );
    }
}
