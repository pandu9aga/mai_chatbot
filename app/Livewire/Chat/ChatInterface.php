<?php

namespace App\Livewire\Chat;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\Chat\ChatServiceInterface;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatInterface extends Component
{
    use WithFileUploads;

    public ChatSession $session;
    public string $newMessage = '';
    public bool $isStreaming = false;
    public string $streamedContent = '';
    public string $streamingMessageContent = '';
    public array $uploadedFiles = [];

    protected function rules()
    {
        return [
            'uploadedFiles.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,heic,heif,pdf,mp3,wav,ogg,mp4,mov,avi,csv,json,txt,xml,html,js,ts,css,md,py,rtf',
        ];
    }

    public function mount(ChatSession $session): void
    {
        $this->session = $session->load('messages');
    }

    public function updatedUploadedFiles(): void
    {
        $this->validate();
    }

    public function removeFile(int $index): void
    {
        if (isset($this->uploadedFiles[$index])) {
            unset($this->uploadedFiles[$index]);
            $this->uploadedFiles = array_values($this->uploadedFiles);
        }
    }

    public function sendMessage(): void
    {
        if ((empty(trim($this->newMessage)) && empty($this->uploadedFiles)) || $this->isStreaming) {
            return;
        }

        $userText = trim($this->newMessage);
        $this->newMessage = '';

        $filesMetadata = [];
        $fileData = [];
        foreach ($this->uploadedFiles as $file) {
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            $storePath = 'uploads/chat/' . $this->session->id . '/' . uniqid() . '_' . $originalName;
            $file->storeAs(
                dirname($storePath),
                basename($storePath),
                ['disk' => 'public']
            );

            $filesMetadata[] = [
                'name' => $originalName,
                'size' => $size,
                'mime_type' => $mimeType,
                'path' => $storePath,
            ];

            $fullPath = Storage::disk('public')->path($storePath);
            $fileData[] = [
                'mime_type' => $mimeType,
                'data' => base64_encode(file_get_contents($fullPath)),
            ];
        }
        $this->uploadedFiles = [];

        $this->isStreaming = true;
        $this->streamedContent = '';
        $this->streamingMessageContent = '';

        ChatMessage::create([
            'chat_session_id' => $this->session->id,
            'role' => 'user',
            'content' => $userText,
            'files' => $filesMetadata,
        ]);

        $this->session->update(['updated_at' => now()]);
        $this->session->load('messages');

        if ($this->session->title === 'New Chat' && !empty($userText)) {
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
                'files' => $m->files ?? [],
            ])
            ->toArray();

        try {
            $service = app(ChatServiceInterface::class, [
                'mode' => $this->session->mode,
                'model' => $this->session->model,
            ]);

            $fullContent = '';

            foreach ($service->stream($userText, $history, $this->session->model, $fileData) as $chunk) {
                $fullContent .= $chunk;
                $this->streamingMessageContent = $fullContent;
                $this->stream('streamedContent', Str::markdown($fullContent));
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
                'files' => $m->files ?? [],
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
                $this->stream('streamedContent', Str::markdown($fullContent));
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