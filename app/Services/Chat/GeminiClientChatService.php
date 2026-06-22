<?php

namespace App\Services\Chat;

use Gemini\Data\GenerationConfig;
use Gemini\Data\Content;
use Gemini\Data\Part;
use Gemini\Enums\Role;

class GeminiClientChatService implements ChatServiceInterface
{
    protected \Gemini\Client $client;
    protected string $model;

    public function __construct(string $model = 'gemini-2.5-flash-lite')
    {
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
        $this->client = \Gemini::client($apiKey);
        $this->model = $model;
    }

    public function stream(string $prompt, array $history, string $model): \Generator
    {
        $this->model = $model;
        
        $generativeModel = $this->client->generativeModel($this->model)
            ->withGenerationConfig(new GenerationConfig(
                temperature: 0.7,
                maxOutputTokens: 2048,
                topP: 0.95,
                topK: 40,
            ));

        $chatHistory = $this->formatHistory($history);
        
        if (!empty($chatHistory)) {
            $chat = $generativeModel->startChat($chatHistory);
        } else {
            $chat = $generativeModel->startChat();
        }

        $response = $chat->streamSendMessage($prompt);
        
        foreach ($response as $chunk) {
            try {
                $text = $chunk->text();
                yield $text;
            } catch (\ValueError $e) {
                if (!empty($chunk->candidates)) {
                    $text = $chunk->candidates[0]->content->parts[0]->text ?? '';
                    if (!empty($text)) {
                        yield $text;
                    }
                }
            }
        }
    }

    public function complete(string $prompt, array $history, string $model): string
    {
        $this->model = $model;
        
        $generativeModel = $this->client->generativeModel($this->model)
            ->withGenerationConfig(new GenerationConfig(
                temperature: 0.7,
                maxOutputTokens: 2048,
                topP: 0.95,
                topK: 40,
            ));

        $chatHistory = $this->formatHistory($history);
        
        if (!empty($chatHistory)) {
            $chat = $generativeModel->startChat($chatHistory);
        } else {
            $chat = $generativeModel->startChat();
        }

        $response = $chat->sendMessage($prompt);
        
        return $response->text();
    }

    public function getAvailableModels(): array
    {
        return [
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (Fast, Free Tier)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash (Balanced)',
            'gemini-2.5-pro' => 'Gemini 2.5 Pro (Advanced, Paid)',
        ];
    }

    protected function formatHistory(array $history): array
    {
        $formatted = [];
        foreach ($history as $msg) {
            if (in_array($msg['role'], ['user', 'model'])) {
                $role = $msg['role'] === 'user' ? Role::USER : Role::MODEL;
                $formatted[] = new Content(
                    parts: [new Part(text: $msg['content'])],
                    role: $role,
                );
            } elseif (in_array($msg['role'], ['user', 'assistant'])) {
                $role = $msg['role'] === 'assistant' ? Role::MODEL : Role::USER;
                $formatted[] = new Content(
                    parts: [new Part(text: $msg['content'])],
                    role: $role,
                );
            }
        }
        return $formatted;
    }
}