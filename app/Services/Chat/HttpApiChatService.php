<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpApiChatService implements ChatServiceInterface
{
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
    }

    public function stream(string $prompt, array $history, string $model, array $files = []): \Generator
    {
        $messages = $this->formatHistory($history);
        $userParts = [['text' => $prompt]];
        foreach ($files as $file) {
            $userParts[] = ['inlineData' => ['mimeType' => $file['mime_type'], 'data' => $file['data']]];
        }
        $messages[] = ['role' => 'user', 'parts' => $userParts];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ])->timeout(120)->post("{$this->baseUrl}/models/{$model}:streamGenerateContent?alt=sse", [
            'contents' => $messages,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'topP' => 0.95,
                'topK' => 40,
            ],
        ]);

        if ($response->failed()) {
            $error = $response->json();
            $message = $error['error']['message'] ?? ($error['error']['status'] ?? 'Unknown error');
            Log::error('Gemini API Error', ['error' => $error, 'status' => $response->status()]);
            throw new \Exception('Gemini API Error: ' . $message);
        }

        $body = $response->body();
        
        foreach (explode("\n", $body) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if ($line === '[DONE]') break;

            if (str_starts_with($line, 'data: ')) {
                $data = trim(substr($line, 6));
                if ($data === '[DONE]') break;
            } else {
                $data = $line;
            }

            $parsed = json_decode($data, true);
            if (!$parsed) continue;

            if (isset($parsed['candidates'][0]['content']['parts'][0]['text'])) {
                yield $parsed['candidates'][0]['content']['parts'][0]['text'];
            } elseif (isset($parsed['candidates'][0]['finishReason'])) {
                Log::debug('Gemini response finishReason: ' . $parsed['candidates'][0]['finishReason']);
            } elseif (isset($parsed['error'])) {
                Log::error('Gemini streaming error', ['error' => $parsed['error']]);
            }
        }
    }

    public function complete(string $prompt, array $history, string $model, array $files = []): string
    {
        $messages = $this->formatHistory($history);
        $messages[] = ['role' => 'user', 'parts' => [['text' => $prompt]]];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ])->timeout(120)->post("{$this->baseUrl}/models/{$model}:generateContent", [
            'contents' => $messages,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'topP' => 0.95,
                'topK' => 40,
            ],
        ]);

        if ($response->failed()) {
            $error = $response->json();
            Log::error('Gemini API Error', ['error' => $error, 'status' => $response->status()]);
            throw new \Exception('Gemini API Error: ' . ($error['error']['message'] ?? 'Unknown error'));
        }

        $data = $response->json();
        
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
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
            if (in_array($msg['role'], ['user', 'assistant'])) {
                $role = $msg['role'] === 'assistant' ? 'model' : 'user';
                $parts = [['text' => $msg['content']]];
                if (!empty($msg['files'])) {
                    foreach ($msg['files'] as $file) {
                        $parts[] = ['text' => "[File: {$file['name']}]"];
                    }
                }
                $formatted[] = [
                    'role' => $role,
                    'parts' => $parts,
                ];
            }
        }
        return $formatted;
    }
}