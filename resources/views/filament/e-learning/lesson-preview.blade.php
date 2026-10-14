@php
    use App\Enums\PRFLessonType;

    /** @var \App\Models\Lesson $lesson */
    $collection = $lesson->contentCollection();
    $media = $collection !== null ? $lesson->getFirstMedia($collection) : null;

    $fileURL = null;

    if ($media !== null) {
        try {
            $fileURL = $media->getTemporaryUrl(now()->addMinutes(30));
        } catch (\Throwable) {
            $fileURL = $media->getUrl();
        }
    }

    // Only web links are rendered: a stored javascript:/data: URL must never reach src or href.
    $link = $lesson->contentURL();

    if (filled($link) && !in_array(strtolower((string) parse_url($link, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        $link = null;
    }

    // YouTube and Vimeo links play in their own embedded player.
    $embed = null;

    if (filled($link) && preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{6,})~', $link, $match)) {
        $embed = "https://www.youtube-nocookie.com/embed/{$match[1]}";
    } elseif (filled($link) && preg_match('~vimeo\.com/(?:video/)?(\d+)~', $link, $match)) {
        $embed = "https://player.vimeo.com/video/{$match[1]}";
    }

    $source = $fileURL ?? $link;
@endphp

<div class="flex flex-col gap-4">
    @if ($lesson->type === PRFLessonType::TEXT)
        @if (filled(strip_tags((string) $lesson->content)))
            <div class="prose max-w-none dark:prose-invert">{!! str($lesson->content)->sanitizeHtml() !!}</div>
        @else
            <p class="text-sm text-gray-500">This lesson has no text yet.</p>
        @endif
    @elseif ($fileURL === null && $embed !== null)
        <iframe src="{{ $embed }}" class="aspect-video w-full rounded-lg" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen title="{{ $lesson->name }}"></iframe>
    @elseif (filled($source))
        @if ($lesson->type === PRFLessonType::VIDEO)
            <video controls preload="metadata" class="w-full rounded-lg bg-black" src="{{ $source }}"></video>
        @elseif ($lesson->type === PRFLessonType::AUDIO)
            <audio controls preload="metadata" class="w-full" src="{{ $source }}"></audio>
        @else
            <iframe src="{{ $source }}" class="h-[70vh] w-full rounded-lg ring-1 ring-gray-950/10" title="{{ $lesson->name }}"></iframe>
        @endif
    @else
        <p class="text-sm text-danger-600 dark:text-danger-400">No file or link has been added to this lesson yet.</p>
    @endif

    @if ($lesson->type !== PRFLessonType::TEXT && filled($source))
        <a href="{{ $source }}" target="_blank" rel="noopener" class="text-sm text-primary-600 underline dark:text-primary-400">
            {{ $fileURL !== null ? 'Open ' . $media?->file_name . ' in a new tab' : 'Open the link in a new tab' }}
        </a>
    @endif
</div>
