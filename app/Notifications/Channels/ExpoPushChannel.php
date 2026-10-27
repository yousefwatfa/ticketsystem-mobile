<?php

namespace App\Notifications\Channels;

use App\Models\DeviceToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends notifications to the native app through the Expo Push Service
 * (which relays to FCM on Android and APNs on iOS). Outbound HTTPS only.
 *
 * Notifications implement toExpoPush($notifiable): array{title, body, data?}
 */
class ExpoPushChannel
{
    public const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public function send($notifiable, Notification $notification): void
    {
        $tokens = $notifiable->deviceTokens()->pluck('token')->all();
        if (empty($tokens) || !method_exists($notification, 'toExpoPush')) {
            return;
        }

        $payload = $notification->toExpoPush($notifiable);

        $messages = array_map(fn ($token) => [
            'to' => $token,
            'title' => $payload['title'],
            'body' => $payload['body'],
            'data' => $payload['data'] ?? [],
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'tickets',
        ], $tokens);

        try {
            $request = Http::timeout(10)->acceptJson();
            if ($accessToken = config('services.expo.access_token')) {
                $request = $request->withToken($accessToken);
            }
            $response = $request->post(self::ENDPOINT, $messages);
        } catch (\Throwable $e) {
            Log::warning('Expo push failed', ['error' => $e->getMessage()]);
            return;
        }

        // Drop tokens Expo says are no longer valid (app uninstalled / token rotated)
        foreach ($response->json('data', []) as $index => $ticket) {
            if (($ticket['status'] ?? null) === 'error'
                && ($ticket['details']['error'] ?? null) === 'DeviceNotRegistered'
                && isset($tokens[$index])) {
                DeviceToken::where('token', $tokens[$index])->delete();
            }
        }
    }
}
