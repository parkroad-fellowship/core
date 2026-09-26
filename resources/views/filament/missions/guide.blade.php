@php
    use App\Enums\PRFMissionStatus;

    /** @var \App\Services\Missions\MissionProgress $progress */
    /** @var \App\Models\Mission $mission */
    $stages = $progress->stages();
    $next = $progress->nextStep();
    $checklist = $progress->checklist();
    $currentIndex = collect($stages)->search(fn (array $stage): bool => ! $stage['done']);
    $stopped = $progress->isStopped();
    $page = $this;

    $goTo = fn (?string $label): string => $label === null ? '' : "const tab = [...document.querySelectorAll('.fi-tabs-item')].find(el => el.textContent.trim().startsWith('{$label}')); tab?.click(); tab?.scrollIntoView({ behavior: 'smooth', block: 'start' });";
@endphp

<div class="flex flex-col gap-4">
    @if ($stopped)
        <div @class([
            'rounded-xl px-5 py-4 ring-1',
            'bg-warning-50 ring-warning-200 dark:bg-warning-500/10 dark:ring-warning-500/20' => $mission->status->is(PRFMissionStatus::POSTPONED),
            'bg-danger-50 ring-danger-200 dark:bg-danger-500/10 dark:ring-danger-500/20' => !$mission->status->is(PRFMissionStatus::POSTPONED),
        ])>
            <p class="font-semibold text-gray-950 dark:text-white">This mission is {{ strtolower($mission->status->getLabel()) }}.</p>
            @if (filled($mission->status_reason))
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">Reason: {{ $mission->status_reason }}</p>
            @endif
        </div>
    @else
        {{-- Stage tracker --}}
        <ol class="grid grid-cols-7 gap-1 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @foreach ($stages as $index => $stage)
                @php
                    $isCurrent = $index === $currentIndex;
                @endphp
                <li class="flex flex-col items-center gap-2 text-center">
                    <span @class([
                        'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                        'bg-success-500 text-white' => $stage['done'],
                        'bg-primary-600 text-white ring-4 ring-primary-100 dark:ring-primary-500/20' => $isCurrent,
                        'bg-gray-100 text-gray-400 dark:bg-white/5' => ! $stage['done'] && ! $isCurrent,
                    ])>
                        @if ($stage['done'])
                            <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                        @else
                            {{ $index + 1 }}
                        @endif
                    </span>
                    <span @class([
                        'text-xs leading-tight',
                        'font-semibold text-gray-950 dark:text-white' => $isCurrent,
                        'text-gray-500 dark:text-gray-400' => ! $isCurrent,
                    ])>{{ $stage['label'] }}</span>
                </li>
            @endforeach
        </ol>
    @endif

    <div class="grid gap-4 lg:grid-cols-5">
        {{-- Next step --}}
        <div class="flex flex-col justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 lg:col-span-3 dark:bg-gray-900 dark:ring-white/10">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Next step</p>
                <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $next['title'] }}</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $next['description'] }}</p>
            </div>

            @if ($page->actionName($next['action']) !== null)
                <div>
                    <x-filament::button size="lg" wire:click="mountAction('{{ $page->actionName($next['action']) }}')" icon="heroicon-m-arrow-right" icon-position="after">
                        {{ $next['title'] }}
                    </x-filament::button>
                </div>
            @elseif ($page->tabLabel($next['tab']) !== null)
                <div>
                    <x-filament::button size="lg" x-on:click="{{ $goTo($page->tabLabel($next['tab'])) }}" icon="heroicon-m-arrow-down" icon-position="after">
                        Go to {{ $page->tabLabel($next['tab']) }}
                    </x-filament::button>
                </div>
            @endif
        </div>

        {{-- Checklist --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 lg:col-span-2 dark:bg-gray-900 dark:ring-white/10">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Checklist</p>
            <ul class="mt-3 flex flex-col gap-2.5">
                @foreach ($checklist as $item)
                    <li class="flex items-start gap-2.5 text-sm">
                        <x-filament::icon
                            :icon="$item['done'] ? 'heroicon-m-check-circle' : 'heroicon-o-minus-circle'"
                            @class([
                                'mt-0.5 h-5 w-5 shrink-0',
                                'text-success-500' => $item['done'],
                                'text-gray-300 dark:text-gray-600' => ! $item['done'],
                            ])
                        />
                        <span @class([
                            'flex-1',
                            'text-gray-500 line-through decoration-gray-300 dark:text-gray-500' => $item['done'],
                            'text-gray-950 dark:text-white' => ! $item['done'],
                        ])>
                            {{ $item['label'] }}
                            @if (! $item['required'] && ! $item['done'])
                                <span class="text-xs text-gray-400">(optional)</span>
                            @endif
                        </span>

                        @unless ($item['done'] || $stopped)
                            @if ($page->actionName($item['action']) !== null)
                                <button type="button" wire:click="mountAction('{{ $page->actionName($item['action']) }}')" class="shrink-0 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">Do it</button>
                            @elseif ($page->tabLabel($item['tab']) !== null)
                                <button type="button" x-on:click="{{ $goTo($page->tabLabel($item['tab'])) }}" class="shrink-0 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">Open</button>
                            @endif
                        @endunless
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
