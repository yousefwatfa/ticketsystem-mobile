<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Minimal Telegram Bot API client used by the long-polling bot.
 * Outbound HTTPS only: the hospital server never has to accept inbound traffic.
 */
class TelegramApi
{
    public function __construct(
        private ?string $token = null,
        private ?string $baseUri = null,
    ) {
        $this->token ??= config('services.telegram-bot-api.token');
        $this->baseUri = rtrim($this->baseUri ?? config('services.telegram-bot-api.base_uri') ?? 'https://api.telegram.org', '/');
    }

    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    public function call(string $method, array $params = [], int $timeout = 15): array
    {
        $response = $this->http($timeout)->post("{$this->baseUri}/bot{$this->token}/{$method}", $params);

        $body = $response->json() ?? [];
        if (!($body['ok'] ?? false)) {
            throw new \RuntimeException("Telegram {$method} failed: " . ($body['description'] ?? $response->status()));
        }

        return is_array($body['result'] ?? null) ? $body['result'] : ['value' => $body['result'] ?? null];
    }

    public function getUpdates(int $offset, int $timeout = 25): array
    {
        return $this->call('getUpdates', [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message', 'callback_query'],
        ], $timeout + 10);
    }

    public function sendMessage(int|string $chatId, string $text, ?array $keyboard = null): array
    {
        $params = ['chat_id' => $chatId, 'text' => $text];
        if ($keyboard !== null) {
            $params['reply_markup'] = $keyboard;
        }

        return $this->call('sendMessage', $params);
    }

    public function answerCallback(string $callbackId, ?string $text = null): void
    {
        $this->call('answerCallbackQuery', array_filter(['callback_query_id' => $callbackId, 'text' => $text]));
    }

    /**
     * Download a file sent to the bot (photo/document). Returns null if it is larger than $maxBytes.
     */
    public function downloadFile(string $fileId, int $maxBytes): ?string
    {
        $file = $this->call('getFile', ['file_id' => $fileId]);
        if (($file['file_size'] ?? 0) > $maxBytes || empty($file['file_path'])) {
            return null;
        }

        $response = $this->http(30)->get("{$this->baseUri}/file/bot{$this->token}/{$file['file_path']}");

        return $response->successful() ? $response->body() : null;
    }

    private function http(int $timeout): PendingRequest
    {
        $options = ['verify' => (bool) config('services.telegram-bot-api.guzzle.verify', true)];
        if ($proxy = config('services.telegram-bot-api.proxy')) {
            $options['proxy'] = $proxy;
        }

        return Http::timeout($timeout)->withOptions($options)->asJson();
    }
}
