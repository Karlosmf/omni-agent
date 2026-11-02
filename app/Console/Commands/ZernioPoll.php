<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\ZernioEventHandler;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Local testing without a public URL: asks Zernio for new WhatsApp messages instead of waiting for its webhook.
 * Only conversations of the test numbers (ZERNIO_TEST_PHONES) are read, and only messages newer than the start.
 */
class ZernioPoll extends Command
{
    protected $signature = 'zernio:poll {--interval=5 : Seconds between checks} {--once : Check once and exit}';

    /**
     * Ids already handed over, so each message is processed once per run.
     *
     * @var array<string, true>
     */
    private array $seen = [];

    protected $description = 'Trae de Zernio los mensajes nuevos de los números de prueba (para probar en local sin webhook)';

    public function handle(ZernioEventHandler $events): int
    {
        $phones = $events->testPhones();

        if ($phones === [] || blank(config('services.zernio.api_key')) || blank(config('services.zernio.whatsapp_account_id'))) {
            $this->error('Completá ZERNIO_API_KEY, ZERNIO_WHATSAPP_ACCOUNT_ID y ZERNIO_TEST_PHONES en el .env.');

            return self::FAILURE;
        }

        $since = now()->subMinute();
        $this->info('Esperando mensajes de '.implode(', ', $phones).'…');

        do {
            try {
                $this->check($events, $phones, $since);
            } catch (ConnectionException $e) {
                $this->warn('Zernio no respondió: '.$e->getMessage());
            }

            if (! $this->option('once')) {
                sleep(max(1, (int) $this->option('interval')));
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $phones
     */
    private function check(ZernioEventHandler $events, array $phones, Carbon $since): void
    {
        $accountId = (string) config('services.zernio.whatsapp_account_id');
        $conversations = $this->get('inbox/conversations', ['accountId' => $accountId, 'limit' => 30])['data'] ?? [];

        foreach ($conversations as $conversation) {
            $phone = '+'.ltrim((string) ($conversation['participantId'] ?? ''), '+');

            if (! $events->isAllowed($phone) || Carbon::parse($conversation['updatedTime'] ?? '2000-01-01')->lt($since)) {
                continue;
            }

            $messages = $this->get("inbox/conversations/{$conversation['id']}/messages", ['accountId' => $accountId, 'limit' => 20, 'sortOrder' => 'desc'])['messages'] ?? [];

            foreach (array_reverse($messages) as $message) {
                if (isset($this->seen[$message['id']]) || Carbon::parse($message['createdAt'] ?? '2000-01-01')->lt($since)) {
                    continue;
                }

                $this->seen[$message['id']] = true;

                $incoming = ($message['direction'] ?? '') === 'incoming';

                $events->handle([
                    'event' => $incoming ? 'message.received' : 'message.sent',
                    'account' => ['accountId' => $accountId],
                    'conversation' => ['participantId' => $conversation['participantId'] ?? null],
                    'message' => [
                        'id' => $message['id'],
                        'conversationId' => $conversation['id'],
                        'direction' => $message['direction'] ?? null,
                        'text' => $message['message'] ?? null,
                        'source' => $message['metadata']['source'] ?? null,
                        'createdAt' => $message['createdAt'] ?? null,
                        'attachments' => $message['attachments'] ?? [],
                        'sender' => ['phoneNumber' => $message['senderPhoneNumber'] ?? $phone, 'name' => $message['senderName'] ?? ($conversation['participantName'] ?? null)],
                    ],
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query): array
    {
        return (array) Http::timeout(15)
            ->withToken((string) config('services.zernio.api_key'))
            ->get(rtrim((string) config('services.zernio.base_url'), '/').'/'.$path, $query)
            ->json();
    }
}
