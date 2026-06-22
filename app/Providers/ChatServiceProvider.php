<?php

namespace App\Providers;

use App\Services\Chat\ChatServiceInterface;
use App\Services\Chat\GeminiClientChatService;
use App\Services\Chat\HttpApiChatService;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChatServiceInterface::class, function ($app, $parameters) {
            $mode = $parameters['mode'] ?? 'http_api';
            $model = $parameters['model'] ?? 'gemini-2.5-flash-lite';

            return match ($mode) {
                'ai_sdk' => new GeminiClientChatService($model),
                'http_api' => new HttpApiChatService(),
                default => new HttpApiChatService(),
            };
        });
    }

    public function boot(): void
    {
        //
    }
}