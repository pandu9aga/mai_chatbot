<?php

namespace App\Services\Chat;

use Gemini\Data\Blob;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Content;
use Gemini\Data\Part;
use Gemini\Enums\MimeType;
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

    public function stream(string $prompt, array $history, string $model, array $files = []): \Generator
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

        $parts = [$prompt];
        foreach ($files as $file) {
            $mimeType = $this->resolveMimeType($file['mime_type']);
            $parts[] = new Blob(mimeType: $mimeType, data: $file['data']);
        }

        $response = $chat->streamSendMessage(...$parts);

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

    public function complete(string $prompt, array $history, string $model, array $files = []): string
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

        $parts = [$prompt];
        foreach ($files as $file) {
            $mimeType = $this->resolveMimeType($file['mime_type']);
            $parts[] = new Blob(mimeType: $mimeType, data: $file['data']);
        }

        $response = $chat->sendMessage(...$parts);

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
            $role = $msg['role'] === 'assistant' || $msg['role'] === 'model'
                ? Role::MODEL : Role::USER;

            $parts = [new Part(text: $msg['content'] ?? '')];

            if (!empty($msg['files'])) {
                foreach ($msg['files'] as $file) {
                    $parts[] = new Part(text: "[File: {$file['name']}]");
                }
            }

            $formatted[] = new Content(parts: $parts, role: $role);
        }
        return $formatted;
    }

    protected function resolveMimeType(string $mimeType): MimeType
    {
        try {
            return MimeType::from($mimeType);
        } catch (\ValueError $e) {
            return MimeType::TEXT_PLAIN;
        }
    }
}