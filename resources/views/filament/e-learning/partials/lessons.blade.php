@php
    use App\Enums\PRFActiveStatus;

    $canChange = $this->canChangeCurriculum();
@endphp

<ol
    class="divide-y divide-gray-100 dark:divide-white/5"
    @if ($canChange)
        wire:sort="sortLessons"
        wire:sort:group="lessons"
        wire:sort:group-id="{{ $module->ulid }}"
    @endif
>
    @forelse ($module->lessonModules as $position => $lessonLink)
        @php
            $lesson = $lessonLink->lesson;
            $badge = $this->lessonBadge($lesson);
        @endphp

        <li
            wire:key="lesson-{{ $lessonLink->ulid }}"
            @if ($canChange) wire:sort:item="{{ $lessonLink->ulid }}" @endif
            class="flex items-center gap-4 bg-white px-5 py-3 dark:bg-gray-900"
        >
            @if ($canChange)
                <button type="button" wire:sort:handle class="cursor-grab text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400" title="Drag to reorder or move to another module">
                    <x-filament::icon icon="heroicon-m-bars-3" class="h-4 w-4" />
                </button>
            @endif

            <span class="w-6 shrink-0 text-end text-sm tabular-nums text-gray-400">{{ $position + 1 }}.</span>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $lesson->name }}</p>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $lesson->description }}</p>
            </div>

            <div class="hidden shrink-0 flex-wrap items-center justify-end gap-2 lg:flex">
                <x-filament::badge :color="$badge['color']" :icon="$badge['icon']">{{ $badge['label'] }}</x-filament::badge>

                @unless ($lesson->hasContent())
                    <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle" tooltip="Students would see an empty lesson. Edit it to add the content.">
                        No content yet
                    </x-filament::badge>
                @endunless

                @if ($lesson->lesson_modules_count > 1)
                    <x-filament::badge color="warning" icon="heroicon-m-link" tooltip="Used in {{ $lesson->lesson_modules_count }} modules: edits apply everywhere">
                        Shared ×{{ $lesson->lesson_modules_count }}
                    </x-filament::badge>
                @endif

                @if ($lesson->is_active !== PRFActiveStatus::ACTIVE)
                    <x-filament::badge color="gray" icon="heroicon-m-eye-slash">Hidden</x-filament::badge>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2 border-s border-gray-100 ps-3 dark:border-white/10">
                {{ ($this->previewLessonAction)(['lesson' => $lesson->ulid]) }}
                {{ ($this->editLessonAction)(['lesson' => $lesson->ulid]) }}
                {{ ($this->removeLessonAction)(['link' => $lessonLink->ulid]) }}
            </div>
        </li>
    @empty
        <li wire:sort:ignore class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
            No lessons in this module yet.
            @if ($canChange)
                Write a new one or add one from the library below{{ $this instanceof \App\Filament\Resources\Courses\RelationManagers\CurriculumRelationManager ? ', or drag one here from another module' : '' }}.
            @endif
        </li>
    @endforelse
</ol>

@if ($canChange)
    <div class="flex flex-wrap items-center gap-6 border-t border-gray-100 bg-gray-50/60 px-5 py-3 dark:border-white/5 dark:bg-white/[0.02]">
        {{ ($this->newLessonAction)(['module' => $module->ulid]) }}
        {{ ($this->addLessonAction)(['module' => $module->ulid]) }}
    </div>
@endif
