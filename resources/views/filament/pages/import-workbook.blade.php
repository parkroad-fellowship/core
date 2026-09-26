<x-filament-panels::page>
    @php
        $preview = $this->getPreviewData();
        $import = $preview['import'] ?? null;
        $hasPreview = $preview !== null && ! isset($preview['error']);
        $step = $preview === null ? 1 : 2;
        $mapping = $import?->mapping ?? [];
        $imports = $this->getPastImports();
    @endphp

    {{-- Where the treasurer is in the process --}}
    <ol class="grid gap-3 sm:grid-cols-3">
        @foreach ([
            1 => ['Upload', 'Choose the cashbook workbook (.xlsx) and its year.'],
            2 => ['Review & map', 'Check the totals and tell us where unfamiliar labels belong.'],
            3 => ['Import', 'Rows are posted in the background; you get a notification.'],
        ] as $number => [$title, $hint])
            <li @class([
                'flex items-start gap-3 rounded-xl p-4 ring-1',
                'bg-primary-50 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/30' => $number === $step,
                'bg-white ring-gray-950/5 dark:bg-white/5 dark:ring-white/10' => $number !== $step,
            ])>
                <span @class([
                    'flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                    'bg-primary-600 text-white' => $number === $step,
                    'bg-success-600 text-white' => $number < $step,
                    'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300' => $number > $step,
                ])>
                    @if ($number < $step)
                        <x-filament::icon icon="heroicon-m-check" class="size-4" />
                    @else
                        {{ $number }}
                    @endif
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $title }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $hint }}</p>
                </div>
            </li>
        @endforeach
    </ol>

    @if ($preview === null)
        <x-filament::section icon="heroicon-o-document-arrow-up" icon-color="primary">
            <x-slot name="heading">Start with the treasurer’s workbook</x-slot>
            <x-slot name="description">Use <strong>Upload workbook</strong> above. We read only the account sheets (Paybill, M-Pesa, Bank, Cash, M-Shwari) and never open the Handover sheet.</x-slot>

            <ul class="grid gap-3 text-sm text-gray-600 sm:grid-cols-3 dark:text-gray-300">
                <li class="flex gap-2"><x-filament::icon icon="heroicon-o-eye" class="size-5 shrink-0 text-primary-500" /> Nothing is posted until you have reviewed a preview.</li>
                <li class="flex gap-2"><x-filament::icon icon="heroicon-o-arrow-path" class="size-5 shrink-0 text-primary-500" /> Importing the same workbook again only adds rows that are new.</li>
                <li class="flex gap-2"><x-filament::icon icon="heroicon-o-envelope" class="size-5 shrink-0 text-primary-500" /> Receipts are never sent for imported rows.</li>
            </ul>
        </x-filament::section>
    @endif

    @if ($preview !== null && isset($preview['error']))
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
            <x-slot name="heading">We couldn’t read this workbook</x-slot>
            <x-slot name="description">{{ $preview['error'] }}</x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-300">Discard this upload, check the sheet names (e.g. “Paybill {{ $import?->year }}”) and that the file is a normal .xlsx export, then upload again.</p>
        </x-filament::section>
    @endif

    @if ($hasPreview)
        {{-- At a glance --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Rows found', $preview['total_rows'], 'heroicon-o-table-cells', 'gray', 'Across '.count($preview['sheets']).' sheets of '.$import->original_name],
                ['Ready to post', $preview['mapped_rows'], 'heroicon-o-check-circle', 'success', 'Booked under a category'],
                ['Need a category', $preview['unmapped_rows'], 'heroicon-o-question-mark-circle', $preview['unmapped_rows'] > 0 ? 'warning' : 'success', $preview['unmapped_rows'] > 0 ? 'Map the labels below' : 'Every label is recognised'],
                ['Skipped', $preview['skipped_rows'], 'heroicon-o-minus-circle', 'gray', 'Totals and rows without an amount or date'],
            ] as [$label, $value, $icon, $color, $hint])
                <div class="rounded-xl bg-white p-4 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <x-filament::icon :icon="$icon" @class(['size-5', 'text-success-500' => $color === 'success', 'text-warning-500' => $color === 'warning', 'text-gray-400' => $color === 'gray']) />
                        {{ $label }}
                    </div>
                    <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($value) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        {{-- Labels that need the treasurer's decision --}}
        @if ($preview['unmapped'] !== [])
            <x-filament::section icon="heroicon-o-question-mark-circle" icon-color="warning">
                <x-slot name="heading">Tell us where these belong</x-slot>
                <x-slot name="description">These Desk/Category labels aren’t in the chart of accounts yet. Map each one to a category, or create a new category. Rows left unmapped are not posted.</x-slot>

                <div class="-mx-6 -mb-6 overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="px-6 py-3 font-medium">Label in the workbook</th>
                                <th class="px-3 py-3 text-right font-medium">Rows</th>
                                <th class="px-3 py-3 font-medium">Example</th>
                                <th class="px-6 py-3 text-right font-medium">Decide</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($preview['unmapped'] as $normalised => $entry)
                                <tr>
                                    <td class="px-6 py-3">
                                        <p class="font-medium text-gray-950 dark:text-white">{{ $entry['label'] }}</p>
                                        <p class="text-xs text-gray-500">{{ implode(', ', $entry['sheets']) }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums">{{ number_format($entry['count']) }}</td>
                                    <td class="max-w-xs truncate px-3 py-3 text-gray-500" title="{{ $entry['example'] }}">{{ $entry['example'] ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex justify-end gap-2">
                                            {{ ($this->mapLabelAction)(['label' => $normalised]) }}
                                            {{ ($this->newCategoryAction)(['label' => $normalised]) }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if ($mapping !== [])
            <x-filament::section icon="heroicon-o-link" icon-color="success" collapsible>
                <x-slot name="heading">Your mappings ({{ count($mapping) }})</x-slot>
                <x-slot name="description">Applied to this import. Undo any of them to decide again.</x-slot>

                <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                    @foreach ($mapping as $normalised => $target)
                        <li class="flex items-center justify-between gap-4 py-2">
                            <span><span class="font-medium text-gray-950 dark:text-white">{{ ucwords($normalised) }}</span> <span class="text-gray-400">→</span> {{ $this->categoryDisplayName($target) }}</span>
                            {{ ($this->removeMappingAction)(['label' => $normalised]) }}
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        {{-- Per-sheet totals to compare with the workbook --}}
        <x-filament::section icon="heroicon-o-banknotes">
            <x-slot name="heading">Compare with your Cash Balances sheet</x-slot>
            <x-slot name="description">Receipts and payments per sheet, as they will be booked. They should match the workbook’s totals.</x-slot>

            <div class="-mx-6 -mb-6 overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr class="text-left text-gray-500 dark:text-gray-400">
                            <th class="px-6 py-3 font-medium">Sheet</th>
                            <th class="px-3 py-3 font-medium">Books into</th>
                            <th class="px-3 py-3 text-right font-medium">Rows</th>
                            <th class="px-3 py-3 text-right font-medium">Receipts (KES)</th>
                            <th class="px-6 py-3 text-right font-medium">Payments (KES)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($preview['sheets'] as $sheet)
                            <tr>
                                <td class="px-6 py-3 font-medium text-gray-950 dark:text-white">{{ $sheet['sheet'] }}</td>
                                <td class="px-3 py-3">
                                    @if ($sheet['account_exists'])
                                        {{ $sheet['account_name'] }}
                                    @else
                                        <x-filament::badge color="info" size="sm">New account: {{ $sheet['account_type'] }}</x-filament::badge>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">
                                    {{ number_format($sheet['rows']) }}
                                    @if ($sheet['unmapped'] > 0)
                                        <span class="text-xs text-warning-600">({{ $sheet['unmapped'] }} unmapped)</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums text-success-700 dark:text-success-400">{{ number_format($sheet['receipts']) }}</td>
                                <td class="px-6 py-3 text-right tabular-nums text-danger-700 dark:text-danger-400">{{ number_format($sheet['payments']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Details the treasurer may want to double-check --}}
        <div class="grid gap-6 lg:grid-cols-2">
            @if ($preview['mapped_counts'] !== [])
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">How rows will be booked</x-slot>
                    <x-slot name="description">{{ number_format($preview['mapped_rows']) }} rows by category.</x-slot>

                    <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                        @foreach (collect($preview['mapped_counts'])->sortDesc() as $category => $count)
                            <li class="flex justify-between gap-4 py-1.5"><span>{{ $category }}</span><span class="tabular-nums text-gray-500">{{ number_format($count) }}</span></li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif

            @if ($preview['opening_balances'] !== [])
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Opening balances</x-slot>
                    <x-slot name="description">Booked on 1 January {{ $import->year }}.</x-slot>

                    <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                        @foreach ($preview['opening_balances'] as $opening)
                            <li class="flex justify-between gap-4 py-1.5">
                                <span>{{ $opening['sheet'] }}</span>
                                <span class="tabular-nums">KES {{ number_format($opening['amount']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif

            @if ($preview['paired_transfers'] !== [] || $preview['unpaired_transfers'] !== [])
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Transfers between accounts</x-slot>
                    <x-slot name="description">{{ count($preview['paired_transfers']) }} matched pairs become transfers; {{ count($preview['unpaired_transfers']) }} unmatched rows are booked on their own. Neither counts as income or expense.</x-slot>

                    <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                        @foreach ($preview['paired_transfers'] as $pair)
                            <li class="flex justify-between gap-4 py-1.5"><span>{{ $pair['transferred_on'] }} · {{ $pair['from_sheet'] }} → {{ $pair['to_sheet'] }}</span><span class="tabular-nums">KES {{ number_format($pair['amount']) }}</span></li>
                        @endforeach
                        @foreach ($preview['unpaired_transfers'] as $leg)
                            <li class="flex justify-between gap-4 py-1.5 text-warning-700 dark:text-warning-400"><span>{{ $leg['date'] }} · {{ $leg['sheet'] }} row {{ $leg['row'] }} (no match)</span><span class="tabular-nums">KES {{ number_format($leg['amount']) }}</span></li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif

            @if ($preview['skipped_samples'] !== [] || ($preview['inherited_date_rows'] ?? 0) > 0)
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Rows we adjusted or skipped</x-slot>
                    @if (($preview['inherited_date_rows'] ?? 0) > 0)
                        <x-slot name="description">{{ $preview['inherited_date_rows'] }} rows had a blank date and took the date of the row above.</x-slot>
                    @endif

                    <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
                        @foreach ($preview['skipped_samples'] as $skipped)
                            <li class="py-1.5">{{ $skipped['sheet'] }} row {{ $skipped['row'] }}: <span class="text-gray-500">{{ $skipped['reason'] }}</span></li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif
        </div>

        @foreach ($preview['sheets'] as $sheet)
            @if ($sheet['samples'] !== [])
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">First rows of {{ $sheet['sheet'] }}</x-slot>

                    <div class="-mx-6 -mb-6 overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                            <thead class="bg-gray-50 dark:bg-white/5">
                                <tr class="text-left text-gray-500 dark:text-gray-400">
                                    <th class="px-6 py-2 font-medium">Date</th>
                                    <th class="px-3 py-2 font-medium">From / To</th>
                                    <th class="px-3 py-2 font-medium">Description</th>
                                    <th class="px-3 py-2 text-right font-medium">Amount</th>
                                    <th class="px-6 py-2 font-medium">Booked as</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($sheet['samples'] as $sample)
                                    <tr>
                                        <td class="px-6 py-2 whitespace-nowrap">{{ $sample['date'] }}@if ($sample['date_inherited'] ?? false)<span class="text-xs text-gray-400" title="Date taken from the row above"> *</span>@endif</td>
                                        <td class="px-3 py-2">{{ $sample['counterparty'] }}</td>
                                        <td class="px-3 py-2">{{ $sample['description'] }}</td>
                                        <td @class(['px-3 py-2 text-right tabular-nums', 'text-success-700 dark:text-success-400' => str_starts_with($sample['flow'], 'Receipt'), 'text-danger-700 dark:text-danger-400' => ! str_starts_with($sample['flow'], 'Receipt')])>
                                            {{ str_starts_with($sample['flow'], 'Receipt') ? '+' : '−' }}{{ number_format($sample['amount']) }}
                                        </td>
                                        <td class="px-6 py-2">{{ $sample['category'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endforeach
    @endif

    {{-- History --}}
    @if ($imports !== [])
        <div @if ($this->hasImportsInProgress()) wire:poll.10s @endif>
            <x-filament::section icon="heroicon-o-clock" collapsible :collapsed="$hasPreview">
                <x-slot name="heading">Past imports</x-slot>

                <div class="-mx-6 -mb-6 overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="px-6 py-3 font-medium">Workbook</th>
                                <th class="px-3 py-3 font-medium">Status</th>
                                <th class="px-3 py-3 font-medium">Result</th>
                                <th class="px-6 py-3 font-medium">Uploaded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($imports as $past)
                                <tr>
                                    <td class="px-6 py-3">
                                        <p class="font-medium text-gray-950 dark:text-white">{{ $past->original_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $past->year }}</p>
                                    </td>
                                    <td class="px-3 py-3"><x-filament::badge :color="$past->status->getColor()">{{ $past->status === \App\Enums\PRFProcessingStatus::PROCESSING ? 'Importing…' : $past->status->getLabel() }}</x-filament::badge></td>
                                    <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                                        @if (is_array($past->summary))
                                            {{ number_format($past->summary['posted'] ?? 0) }} posted · {{ number_format($past->summary['skipped'] ?? 0) }} already on the books · {{ number_format($past->summary['unmapped'] ?? 0) }} unmapped
                                        @else
                                            {{ $past->error ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-500">{{ $past->created_at?->format('j M Y, H:i') }}<br><span class="text-xs">{{ $past->importedBy?->name }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif

</x-filament-panels::page>
