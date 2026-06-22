<?php

namespace App\Livewire\Chat;

use App\Models\ChatSession;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('layouts.chat')]

class ChatDashboard extends Component
{
    public ?int $activeSessionId = null;
    public string $activeMode = 'gemini_client';
    public string $activeModel = 'gemini-2.5-flash-lite';
    public array $availableModels = [];
    public bool $sidebarCollapsed = false;

    public function mount(): void
    {
        $this->availableModels = [
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (Fast, Free Tier)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash (Balanced)',
            'gemini-2.5-pro' => 'Gemini 2.5 Pro (Advanced, Paid)',
        ];

        $lastSession = ChatSession::where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->first();

        if ($lastSession) {
            $this->activeSessionId = $lastSession->id;
            $this->activeMode = $lastSession->mode;
            $this->activeModel = $lastSession->model;
        } else {
            $this->createNewSession();
        }
    }

    public function getGroupedSessionsProperty(): array
    {
        $sessions = ChatSession::where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->get()
            ->toArray();

        $grouped = [];

        foreach ($sessions as $session) {
            $date = \Carbon\Carbon::parse($session['updated_at']);

            if ($date->isToday()) {
                $group = 'Today';
            } elseif ($date->isYesterday()) {
                $group = 'Yesterday';
            } elseif ($date->isThisWeek()) {
                $group = 'This Week';
            } else {
                $group = 'Older';
            }

            $grouped[$group][] = $session;
        }

        return $grouped;
    }

    public function createNewSession(): void
    {
        $session = ChatSession::create([
            'user_id' => auth()->id(),
            'title' => 'New Chat',
            'mode' => $this->activeMode,
            'model' => $this->activeModel,
        ]);

        $this->activeSessionId = $session->id;
    }

    public function switchSession(int $sessionId): void
    {
        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->activeSessionId = $session->id;
        $this->activeMode = $session->mode;
        $this->activeModel = $session->model;
    }

    public function deleteSession(int $sessionId): void
    {
        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $session->delete();

        if ($this->activeSessionId === $sessionId) {
            $lastSession = ChatSession::where('user_id', auth()->id())
                ->orderByDesc('updated_at')
                ->first();

            if ($lastSession) {
                $this->activeSessionId = $lastSession->id;
                $this->activeMode = $lastSession->mode;
                $this->activeModel = $lastSession->model;
            } else {
                $this->createNewSession();
            }
        }
    }

    public function updateMode(string $mode): void
    {
        $this->activeMode = $mode;

        if ($this->activeSessionId) {
            ChatSession::where('id', $this->activeSessionId)
                ->where('user_id', auth()->id())
                ->update(['mode' => $mode]);
        }
    }

    public function updateModel(string $model): void
    {
        $this->activeModel = $model;

        if ($this->activeSessionId) {
            ChatSession::where('id', $this->activeSessionId)
                ->where('user_id', auth()->id())
                ->update(['model' => $model]);
        }
    }

    public function toggleSidebar(): void
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;
    }

    #[On('sessionUpdated')]
    public function handleSessionUpdated(): void
    {
    }

    #[On('clearAllHistory')]
    public function clearAllHistory(): void
    {
        ChatSession::where('user_id', auth()->id())->delete();
        $this->createNewSession();
    }

    public function render()
    {
        return view('livewire.chat.chat-dashboard', [
            'activeSession' => $this->activeSessionId
                ? ChatSession::with('messages')->find($this->activeSessionId)
                : null,
            'groupedSessions' => $this->groupedSessions,
        ]);
    }
}