<?php

namespace App\Livewire\Chat;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\Chat\ChatServiceInterface;
use Livewire\Component;

class ChatInterface extends Component
{
    public ChatSession $session;
    public string $newMessage = '';
    public bool $isStreaming = false;
    public string $streamedContent = '';
    public string $streamingMessageContent = '';

    public function mount(ChatSession $session): void
    {
        $this->session = $session->load('messages');
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->newMessage)) || $this->isStreaming) {
            return;
        }

        $userText = trim($this->newMessage);
        $this->newMessage = '';
        $this->isStreaming = true;
        $this->streamedContent = '';
        $this->streamingMessageContent = '';

        ChatMessage::create([
            'chat_session_id' => $this->session->id,
            'role' => 'user',
            'content' => $userText,
        ]);

        $this->session->update(['updated_at' => now()]);
        $this->session->load('messages');

        if ($this->session->title === 'New Chat' && strlen($userText) > 0) {
            $words = explode(' ', strip_tags($userText));
            $title = implode(' ', array_slice($words, 0, 8));
            $this->session->update([
                'title' => strlen($title) > 50 ? substr($title, 0, 47) . '...' : $title,
            ]);
            $this->dispatch('sessionUpdated', session: $this->session->toArray());
        }

        $this->dispatch('sessionUpdated');

        $assistantMessage = ChatMessage::create([
            'chat_session_id' => $this->session->id,
            'role' => 'assistant',
            'content' => '',
            'is_streaming' => true,
        ]);

        $this->session->load('messages');

        $history = $this->session->messages
            ->filter(fn($m) => $m->role !== 'system' && $m->id !== $assistantMessage->id)
            ->values()
            ->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();

        try {
            $service = app(ChatServiceInterface::class, [
                'mode' => $this->session->mode,
                'model' => $this->session->model,
            ]);

            $fullContent = '';

            foreach ($service->stream($userText, $history, $this->session->model) as $chunk) {
                $fullContent .= $chunk;
                $this->streamingMessageContent = $fullContent;
                $this->stream('streamedContent', $fullContent);
            }

            $assistantMessage->update([
                'content' => $fullContent,
                'is_streaming' => false,
            ]);

            $this->session->update(['updated_at' => now()]);
        } catch (\Throwable $e) {
            $assistantMessage->update([
                'content' => 'Error: ' . $e->getMessage(),
                'is_streaming' => false,
                'metadata' => json_encode(['error' => true]),
            ]);
        }

        $this->isStreaming = false;
        $this->streamedContent = '';
        $this->streamingMessageContent = '';
        $this->session->load('messages');
    }

    public function regenerateMessage(int $messageId): void
    {
        $assistantMsg = ChatMessage::where('id', $messageId)
            ->where('chat_session_id', $this->session->id)
            ->where('role', 'assistant')
            ->firstOrFail();

        $index = $this->session->messages->search(fn($m) => $m->id === $messageId);
        $userMsg = null;

        if ($index !== false) {
            for ($i = $index - 1; $i >= 0; $i--) {
                if ($this->session->messages[$i]->role === 'user') {
                    $userMsg = $this->session->messages[$i];
                    break;
                }
            }
        }

        if (!$userMsg) return;

        $assistantMsg->delete();
        $this->session->load('messages');

        $this->isStreaming = true;
        $this->streamedContent = '';

        $history = $this->session->messages
            ->filter(fn($m) => $m->role !== 'system')
            ->values()
            ->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();

        $newAssistantMsg = ChatMessage::create([
            'chat_session_id' => $this->session->id,
            'role' => 'assistant',
            'content' => '',
            'is_streaming' => true,
        ]);

        $this->session->load('messages');

        try {
            $service = app(ChatServiceInterface::class, [
                'mode' => $this->session->mode,
                'model' => $this->session->model,
            ]);

            $fullContent = '';

            foreach ($service->stream($userMsg->content, $history, $this->session->model) as $chunk) {
                $fullContent .= $chunk;
                $this->streamingMessageContent = $fullContent;
                $this->stream('streamedContent', $fullContent);
            }

            $newAssistantMsg->update([
                'content' => $fullContent,
                'is_streaming' => false,
            ]);

            $this->session->update(['updated_at' => now()]);
        } catch (\Throwable $e) {
            $newAssistantMsg->update([
                'content' => 'Error: ' . $e->getMessage(),
                'is_streaming' => false,
                'metadata' => json_encode(['error' => true]),
            ]);
        }

        $this->isStreaming = false;
        $this->streamedContent = '';
        $this->streamingMessageContent = '';
        $this->session->load('messages');
    }

    public function deleteMessage(int $messageId): void
    {
        ChatMessage::where('id', $messageId)
            ->where('chat_session_id', $this->session->id)
            ->delete();

        $this->session->load('messages');
    }

    public function render()
    {
        return view('livewire.chat.chat-interface', [
            'messages' => $this->session->messages,
        ]);
    }
}