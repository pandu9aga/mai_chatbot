<?php

namespace App\Livewire\Chat;

use Livewire\Component;

class MessageBubble extends Component
{
    public array $message;
    public bool $isStreaming = false;
    public bool $showActions = false;

    protected $listeners = [
        'messageUpdated' => 'handleMessageUpdated',
    ];

    public function mount(): void
    {
        $this->isStreaming = $this->message['is_streaming'] ?? false;
    }

    public function handleMessageUpdated(array $data): void
    {
        if ($data['id'] === $this->message['id']) {
            $this->message['content'] = $data['content'];
        }
    }

    public function copy(): void
    {
        $this->dispatch('copyToClipboard', text: $this->message['content']);
        $this->dispatch('showToast', message: 'Copied to clipboard!');
    }

    public function regenerate(): void
    {
        $this->dispatch('regenerateMessage', messageId: $this->message['id']);
    }

    public function delete(): void
    {
        if (confirm('Delete this message?')) {
            $this->dispatch('deleteMessage', messageId: $this->message['id']);
        }
    }

    public function render()
    {
        return view('livewire.chat.message-bubble');
    }
}