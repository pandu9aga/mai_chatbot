<?php

namespace App\Services\Chat;

interface ChatServiceInterface
{
    public function stream(string $prompt, array $history, string $model): \Generator;
    
    public function complete(string $prompt, array $history, string $model): string;
    
    public function getAvailableModels(): array;
}