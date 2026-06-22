<div class="flex-1 flex flex-col min-h-0" x-data="chatInterface()" x-cloak>
    <div x-ref="messageContainer" class="flex-1 overflow-y-auto px-4 py-6 space-y-4">
        @foreach($messages as $msg)
            <div class="flex items-start gap-3 {{ $msg->role === 'user' ? 'flex-row-reverse' : '' }}" wire:key="msg-{{ $msg->id }}">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium text-white {{ $msg->role === 'user' ? 'bg-blue-600' : 'bg-purple-600' }}">
                    {{ $msg->role === 'user' ? 'U' : 'AI' }}
                </div>
                <div class="max-w-[80%]">
                    <div class="rounded-2xl px-3 py-0 text-sm leading-relaxed whitespace-pre-wrap break-words max-w-none {{ $msg->role === 'user' ? 'bg-blue-700 rounded-tr-sm' : 'bg-gray-800 rounded-tl-sm' }}">
                        {{ $msg->content }}
                    </div>
                    @if($msg->role === 'assistant' && !$msg->is_streaming)
                        <div class="flex items-center gap-1 mt-1 ml-2 opacity-0 hover:opacity-100 transition-opacity">
                            <button x-on:click="copyText(@js($msg->content))"
                                    class="p-1 text-gray-500 hover:text-gray-300 rounded transition-colors" title="Copy">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                            <button wire:click="regenerateMessage({{ $msg->id }})"
                                    class="p-1 text-gray-500 hover:text-gray-300 rounded transition-colors" title="Regenerate">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                            <button wire:click="deleteMessage({{ $msg->id }})"
                                    class="p-1 text-gray-500 hover:text-red-400 rounded transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        @if($isStreaming)
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-purple-600 flex items-center justify-center text-sm font-medium text-white">AI</div>
            <div class="max-w-[80%]">
                <div class="rounded-2xl rounded-tl-sm px-4 py-3 bg-gray-800 text-sm leading-relaxed whitespace-pre-wrap max-w-none">
                    <div wire:stream="streamedContent">{{ $streamedContent }}</div>
                    @if($isStreaming)
                    <div class="flex items-center gap-1 mt-2 text-green-500">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-xs">Streaming...</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if($messages->isEmpty() && !$isStreaming)
            <div class="flex items-center justify-center py-16">
                <div class="text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
                    </svg>
                    <h3 class="text-lg font-medium mb-1">Start a conversation</h3>
                    <p class="text-sm">Type a message below to begin chatting with AI</p>
                </div>
            </div>
        @endif
    </div>

    <div class="border-t border-gray-700 bg-gray-800 p-4">
        <form wire:submit="sendMessage" class="flex gap-3 items-end">
            <div class="flex-1 relative">
                <textarea
                    wire:model="newMessage"
                    x-on:keydown.enter.prevent="if(!$event.shiftKey) $wire.sendMessage()"
                    placeholder="Type your message... (Shift+Enter for new line)"
                    rows="1"
                    class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-xl text-gray-100 placeholder-gray-500 focus:outline-none focus:border-blue-500 resize-none transition-colors"
                    style="min-height: 56px; max-height: 200px;"
                    x-init="$nextTick(() => { $el.style.height = $el.scrollHeight + 'px' })"
                    @input="if ($el) { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' }"
                ></textarea>
            </div>
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="px-5 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white rounded-xl transition-colors flex items-center gap-2">
                <span wire:loading.remove wire:target="sendMessage">Send</span>
                <span wire:loading wire:target="sendMessage" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    Sending
                </span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 0l-7 7m7-7l7 7"/>
                </svg>
            </button>
        </form>

        <p class="text-xs text-gray-500 mt-2 text-center">
            Using <strong>{{ $session->mode === 'gemini_client' ? 'Gemini PHP Client' : 'HTTP API' }}</strong>
            &mdash; Model: <strong>{{ $session->model }}</strong>
        </p>
    </div>
</div>

@push('scripts')
<script>
function chatInterface() {
    return {
        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messageContainer;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },
        copyText(text) {
            navigator.clipboard.writeText(text);
        }
    };
}
</script>
@endpush
