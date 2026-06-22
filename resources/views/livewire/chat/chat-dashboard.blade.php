<div class="flex h-screen bg-gray-900 text-gray-100 overflow-hidden">
    {{-- Sidebar --}}
    <div class="w-72 bg-gray-800 border-r border-gray-700 flex flex-col {{ $sidebarCollapsed ? 'hidden' : '' }}">
        <div class="p-4 border-b border-gray-700">
            <button
                wire:click="createNewSession"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Chat
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            @forelse($groupedSessions as $group => $groupSessions)
                <div class="px-3 pt-3 pb-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider px-2">{{ $group }}</p>
                </div>
                <div class="space-y-0.5 px-2">
                    @foreach($groupSessions as $session)
                        <div
                            wire:click="switchSession({{ $session['id'] }})"
                            class="group flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-colors text-sm {{ $activeSessionId === $session['id'] ? 'bg-gray-700 text-white' : 'text-gray-400 hover:bg-gray-700/50 hover:text-gray-200' }}"
                        >
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            <span class="truncate flex-1">{{ $session['title'] ?? 'New Chat' }}</span>
                            <button
                                x-on:click.stop="if(confirm('Delete this chat session?')) $wire.deleteSession({{ $session['id'] }})"
                                class="opacity-0 group-hover:opacity-100 p-1 text-gray-500 hover:text-red-400 rounded transition-all"
                                title="Delete"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
                    <svg class="w-10 h-10 text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-sm text-gray-500">No chat history yet</p>
                </div>
            @endforelse
        </div>

        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-sm font-medium text-white">
                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-200 truncate">{{ auth()->user()->name ?? 'User' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors" title="Sign out">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="flex items-center justify-between px-4 py-3 bg-gray-800 border-b border-gray-700">
            <div class="flex items-center gap-3">
                <button
                    wire:click="toggleSidebar"
                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <div class="flex items-center gap-2 bg-gray-700 rounded-lg p-1">
                    <button
                        wire:click="updateMode('gemini_client')"
                        class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $activeMode === 'gemini_client' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white' }}"
                    >
                        AI SDK
                    </button>
                    <button
                        wire:click="updateMode('http_api')"
                        class="px-3 py-1.5 text-sm rounded-md transition-colors {{ $activeMode === 'http_api' ? 'bg-blue-600 text-white' : 'text-gray-400 hover:text-white' }}"
                    >
                        HTTP API
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div x-data="{ open: false }" class="relative">
                    <button
                        @click="open = !open"
                        class="flex items-center gap-2 px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $availableModels[$activeModel] ?? $activeModel }}</span>
                    </button>

                    <div
                        x-show="open"
                        @click.outside="open = false"
                        class="absolute right-0 mt-2 w-64 bg-gray-800 border border-gray-700 rounded-lg shadow-xl z-50"
                        x-cloak
                    >
                        <div class="p-2">
                            @foreach($availableModels as $key => $label)
                                <button
                                    wire:click="updateModel('{{ $key }}')"
                                    @click="open = false"
                                    class="w-full text-left px-3 py-2 text-sm rounded-md transition-colors {{ $activeModel === $key ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button
                    wire:click="$dispatch('clearAllHistory')"
                    class="p-2 text-gray-400 hover:text-red-400 hover:bg-gray-700 rounded-lg transition-colors"
                    title="Clear all history"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </header>

        <main class="flex-1 flex flex-col min-h-0">
            @if($activeSession)
                @livewire('chat.chat-interface', ['session' => $activeSession], key($activeSession->id))
            @else
                <div class="flex-1 flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        <h2 class="text-xl font-semibold mb-2">Welcome to MAI Chatbot</h2>
                        <p class="text-sm">Select a chat or create a new one to get started.</p>
                        <button
                            wire:click="createNewSession"
                            class="mt-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white text-sm transition-colors"
                        >
                            New Chat
                        </button>
                    </div>
                </div>
            @endif
        </main>
    </div>
</div>
