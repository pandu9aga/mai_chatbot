<?php

namespace App\Services\Chat;

interface ChatServiceInterface
{
    public function stream(string $prompt, array $history, string $model, array $files = []): \Generator;

    public function complete(string $prompt, array $history, string $model, array $files = []): string;

    public function getAvailableModels(): array;
}