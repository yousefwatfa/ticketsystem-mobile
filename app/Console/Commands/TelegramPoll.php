<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramApi;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Long-polls Telegram for bot updates. The server only makes outbound
 * requests, so it works on the hospital's internal network.
 *
 * Run as a systemd service (see Documantation/mobile-deployment.md):
 *   php artisan telegram:poll
 */
class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll {--once : Fetch one batch of updates and exit}';

    protected $description = 'Receive Telegram bot updates via long polling';

    private const OFFSET_KEY = 'telegram-bot:offset';

    public function handle(TelegramApi $api, TelegramBotService $bot): int
    {
        if (!$api->isConfigured()) {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');
            return self::FAILURE;
        }

        // getUpdates does not work while a webhook is registered
        $api->call('deleteWebhook', ['drop_pending_updates' => false]);
        $this->info('Telegram polling started.');

        $backoff = 1;
        do {
            try {
                $offset = (int) Cache::get(self::OFFSET_KEY, 0);
                $updates = $api->getUpdates($offset, $this->option('once') ? 0 : 25);

                foreach ($updates as $update) {
                    // advance first so a crashing update is never processed twice
                    Cache::forever(self::OFFSET_KEY, $update['update_id'] + 1);
                    $bot->handle($update);
                }
                $backoff = 1;
            } catch (\Throwable $e) {
                $this->warn('Polling error: ' . $e->getMessage());
                logger()->warning('Telegram polling error', ['error' => $e->getMessage()]);
                sleep($backoff);
                $backoff = min($backoff * 2, 60);
            }
        } while (!$this->option('once'));

        return self::SUCCESS;
    }
}
