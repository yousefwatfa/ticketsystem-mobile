<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Client as HttpClient;
use NotificationChannels\Telegram\Telegram as TelegramClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Guzzle client with optional Telegram-specific options from config
        $this->app->singleton(GuzzleClient::class, function ($app) {
            $guzzleOptions = config('services.telegram-bot-api.guzzle', []);
            return new GuzzleClient($guzzleOptions);
        });

        // Ensure Telegram client is bound with configured token and HTTP client
        $this->app->bind(TelegramClient::class, function ($app) {
            return new TelegramClient(
                config('services.telegram-bot-api.token'),
                new HttpClient(['verify' => false]),
                config('services.telegram-bot-api.base_uri')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('is_root', function ($user) {
            return $user->is_root;
        });

        Gate::define('access_finished_tickets', function ($user) {
            return $user->is_root || $user->can_access_finished_tickets;
        });
    }
}
