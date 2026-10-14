@php
    use App\Filament\Forms\Schemas\ELearningSchema;

    $modules = $this->modules();
    $canSortModules = $this->canReorderModules();
@endphp

<div class="fi-resource-relation-manager flex flex-col gap-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            A course is made of modules (chapters), and each module holds lessons.
            @if ($canSortModules)
                Drag <x-filament::icon icon="heroicon-m-bars-3" class="inline h-4 w-4 align-text-bottom" /> to reorder; lessons can be dragged into another module.
            @endif
        </p>

        <div class="flex flex-wrap gap-2">
            {{ $this->addModuleAction }}
            {{ $this->newModuleAction }}
        </div>
    </div>

    @if ($modules->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-gray-300 px-6 py-12 text-center dark:border-white/15">
            <x-filament::icon icon="heroicon-o-queue-list" class="h-10 w-10 text-gray-400" />
            <p class="font-medium text-gray-950 dark:text-white">No modules yet</p>
            <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
                Add the first module to start building this course, then add lessons inside it.
            </p>
        </div>
    @else
        <ol class="flex flex-col gap-3" @if ($canSortModules) wire:sort="sortModules" @endif>
            @foreach ($modules as $position => $link)
                @php($module = $link->module)

                <li
                    wire:key="module-{{ $link->ulid }}"
                    @if ($canSortModules) wire:sort:item="{{ $link->ulid }}" @endif
                    x-data="{ open: true }"
                    class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                >
                    <div class="flex items-center gap-4 px-5 py-4">
                        @if ($canSortModules)
                            <button type="button" wire:sort:handle class="cursor-grab text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Drag to reorder">
                                <x-filament::icon icon="heroicon-m-bars-3" class="h-5 w-5" />
                            </button>
                        @endif

                        <button type="button" x-on:click="open = ! open" class="flex min-w-0 flex-1 items-center gap-3 text-start">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-400">
                                {{ $position + 1 }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-semibold text-gray-950 dark:text-white">{{ $module->name }}</span>
                                <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $module->description }}</span>
                            </span>
                        </button>

                        <div class="hidden shrink-0 flex-wrap items-center justify-end gap-2 lg:flex">
                            <x-filament::badge color="gray">
                                {{ trans_choice(':count lesson|:count lessons', $module->lessonModules->count()) }}
                            </x-filament::badge>

                            @if ($module->course_modules_count > 1)
                                <x-filament::badge color="warning" icon="heroicon-m-link" tooltip="Also used in other courses: edits apply to all of them">
                                    Shared ×{{ $module->course_modules_count }}
                                </x-filament::badge>
                            @endif

                            <x-filament::badge :color="ELearningSchema::statusColor($module->is_active)" :icon="ELearningSchema::statusIcon($module->is_active)">
                                {{ ELearningSchema::statusLabel($module->is_active) }}
                            </x-filament::badge>
                        </div>

                        <div class="flex shrink-0 items-center gap-4 border-s border-gray-100 ps-4 dark:border-white/10">
                            {{ ($this->editModuleAction)(['module' => $module->ulid]) }}
                            {{ ($this->removeModuleAction)(['link' => $link->ulid]) }}

                            <button type="button" x-on:click="open = ! open" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300" title="Show or hide lessons">
                                <x-filament::icon icon="heroicon-m-chevron-down" class="h-5 w-5 transition" x-bind:class="open || '-rotate-90'" />
                            </button>
                        </div>
                    </div>

                    <div x-show="open" x-collapse class="border-t border-gray-100 dark:border-white/5">
                        @include('filament.e-learning.partials.lessons', ['module' => $module])
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    <x-filament-actions::modals />
</div>
