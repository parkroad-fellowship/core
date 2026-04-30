@php
    $record = $getRecord();
    $transcripts = $record->missionSessionTranscripts
        ->filter(fn ($t) => filled($t->transcription_content));
@endphp

<div class="max-h-[50vh] overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
    @foreach ($transcripts as $transcript)
        @if ($transcripts->count() > 1)
            <div class="mb-2 flex items-center gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Transcript {{ $loop->iteration }}
                </span>
                @if ($transcript->status)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $transcript->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' }}">
                        {{ ucfirst($transcript->status) }}
                    </span>
                @endif
            </div>
        @endif

        <div class="prose dark:prose-invert max-w-none text-sm leading-relaxed">
            {!! nl2br(e($transcript->transcription_content)) !!}
        </div>

        @if (! $loop->last)
            <hr class="border-gray-200 dark:border-gray-700">
        @endif
    @endforeach
</div>
