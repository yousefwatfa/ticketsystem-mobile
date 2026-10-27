<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;

/**
 * Webhook entry point, kept for deployments where Telegram can reach the
 * server. On the internal network use `php artisan telegram:poll` instead;
 * both paths share TelegramBotService.
 */
class TelegramController extends Controller
{
    public function handleWebhook(Request $request, TelegramBotService $bot)
    {
        $bot->handle($request->all());

        return response()->json(['status' => 'ok']);
    }
}
