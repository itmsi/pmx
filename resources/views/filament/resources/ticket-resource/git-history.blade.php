@php
    $gitHistories = $getState();
    if (!$gitHistories) {
        $gitHistories = collect();
    }
@endphp

<div class="space-y-4">
    @if($gitHistories && $gitHistories->count() > 0)
        @foreach($gitHistories as $history)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    👤 {{ $history->user?->name ?? 'Unknown User' }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $history->pushed_at->format('M d, Y H:i') }}
                            </span>
                        </div>
                        
                        <div class="mb-2">
                            <p class="text-sm text-gray-800 dark:text-gray-200 font-medium">
                                {{ $history->commit_message }}
                            </p>
                        </div>
                        
                        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1">
                                <span>🌿</span>
                                <span>{{ $history->branch }}</span>
                            </div>
                            
                            <div class="flex items-center gap-1">
                                <span>🔗</span>
                                <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded text-xs">
                                    {{ substr($history->commit_hash, 0, 8) }}
                                </code>
                            </div>
                            
                            @if($history->repository_name)
                                <div class="flex items-center gap-1">
                                    <span>📁</span>
                                    <span>{{ $history->repository_name }}</span>
                                </div>
                            @endif
                        </div>
                        
                        @if($history->repository_url)
                            <div class="mt-2">
                                <a href="{{ $history->repository_url }}" 
                                   target="_blank" 
                                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                    🔗 View Repository
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center py-8">
            <div class="text-6xl mb-4">📝</div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                No Git History
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                No git commits have been linked to this ticket yet.
            </p>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                    How to link commits to this ticket:
                </h4>
                <ul class="text-xs text-blue-800 dark:text-blue-200 space-y-1">
                    <li>• Include ticket ID in your commit message (e.g., "Fix bug #{{ $record->id }}")</li>
                    <li>• Use formats like: #{{ $record->id }}, TICKET-{{ $record->id }}, or T-{{ $record->id }}</li>
                    <li>• Configure webhook URL: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{{ url('/api/git/webhook') }}</code></li>
                </ul>
            </div>
        </div>
    @endif
</div>
