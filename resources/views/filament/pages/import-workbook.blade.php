<x-filament-panels::page>
    @php($preview = $this->getPreviewData())

    @if ($preview !== null && isset($preview['error']))
        <x-filament::section>
            <x-slot name="heading">Preview failed</x-slot>

            <p class="text-sm text-danger-600 dark:text-danger-400">{{ $preview['error'] }}</p>
            <p class="mt-2 text-sm text-gray-500">Cancel this upload and try again with a fresh .xlsx export.</p>
        </x-filament::section>
    @endif

    @if ($preview !== null && ! isset($preview['error']))
        <x-filament::section>
            <x-slot name="heading">Sheets — {{ $preview['import']->original_name }} ({{ $preview['import']->year }})</x-slot>
            <x-slot name="description">Compare the totals below with the workbook's Cash Balances before starting the import.</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">Sheet</th>
                            <th class="py-2 pr-4 font-medium">Account</th>
                            <th class="py-2 pr-4 text-right font-medium">Rows</th>
                            <th class="py-2 pr-4 text-right font-medium">Mapped</th>
                            <th class="py-2 pr-4 text-right font-medium">Unmapped</th>
                            <th class="py-2 pr-4 text-right font-medium">Receipts (KES)</th>
                            <th class="py-2 text-right font-medium">Payments (KES)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($preview['sheets'] as $sheet)
                            <tr>
                                <td class="py-2 pr-4 font-medium">{{ $sheet['sheet'] }}</td>
                                <td class="py-2 pr-4">{{ $sheet['account_name'] ?? $sheet['account_type'].' (to create)' }}</td>
                                <td class="py-2 pr-4 text-right">{{ $sheet['rows'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ $sheet['mapped'] }}</td>
                                <td class="py-2 pr-4 text-right {{ $sheet['unmapped'] > 0 ? 'font-semibold text-warning-600' : '' }}">{{ $sheet['unmapped'] }}</td>
                                <td class="py-2 pr-4 text-right">{{ number_format($sheet['receipts']) }}</td>
                                <td class="py-2 text-right">{{ number_format($sheet['payments']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        @if ($preview['accounts_to_create'] !== [])
            <x-filament::section>
                <x-slot name="heading">Accounts to be created</x-slot>

                <ul class="list-disc space-y-1 pl-5 text-sm">
                    @foreach ($preview['accounts_to_create'] as $account)
                        <li>{{ $account['name'] }} <span class="text-gray-500">({{ $account['type'] }})</span></li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        @if ($preview['mapped_counts'] !== [])
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">Mapped categories ({{ $preview['mapped_rows'] }} rows)</x-slot>

                <ul class="grid gap-1 text-sm md:grid-cols-2">
                    @foreach ($preview['mapped_counts'] as $code => $count)
                        <li class="flex justify-between gap-4">
                            <span>{{ $this->categoryDisplayName($code) }}</span>
                            <span class="text-gray-500">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        @if ($preview['unmapped'] !== [])
            <x-filament::section>
                <x-slot name="heading">Unmapped labels ({{ $preview['unmapped_rows'] }} rows won't be posted)</x-slot>
                <x-slot name="description">Use “Map label” or “New category” above, then start the import.</x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10">
                                <th class="py-2 pr-4 font-medium">Label</th>
                                <th class="py-2 pr-4 text-right font-medium">Rows</th>
                                <th class="py-2 pr-4 font-medium">Sheets</th>
                                <th class="py-2 pr-4 font-medium">Example</th>
                                <th class="py-2 font-medium">Your mapping</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($preview['unmapped'] as $normalised => $entry)
                                <tr>
                                    <td class="py-2 pr-4 font-medium">{{ $entry['label'] }}</td>
                                    <td class="py-2 pr-4 text-right">{{ $entry['count'] }}</td>
                                    <td class="py-2 pr-4">{{ implode(', ', $entry['sheets']) }}</td>
                                    <td class="py-2 pr-4 text-gray-500">{{ $entry['example'] }}</td>
                                    <td class="py-2">
                                        @php($mapping = $preview['import']->mapping[$normalised] ?? null)
                                        {{ $mapping !== null ? $this->categoryDisplayName($mapping) : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if ($preview['opening_balances'] !== [])
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">Opening balances ({{ count($preview['opening_balances']) }})</x-slot>

                <ul class="space-y-1 text-sm">
                    @foreach ($preview['opening_balances'] as $opening)
                        <li>{{ $opening['sheet'] }}: {{ number_format($opening['amount']) }} KES ({{ $opening['flow'] }}, dated {{ $opening['date'] }})</li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        @if ($preview['paired_transfers'] !== [] || $preview['unpaired_transfers'] !== [])
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">Inter-account transfers ({{ count($preview['paired_transfers']) }} paired, {{ count($preview['unpaired_transfers']) }} unpaired)</x-slot>
                <x-slot name="description">Paired rows become one transfer each. Unpaired rows are booked as single transfer lines.</x-slot>

                <ul class="space-y-1 text-sm">
                    @foreach ($preview['paired_transfers'] as $pair)
                        <li>{{ $pair['from_sheet'] }} → {{ $pair['to_sheet'] }}: {{ number_format($pair['amount']) }} KES on {{ $pair['transferred_on'] }}</li>
                    @endforeach
                    @foreach ($preview['unpaired_transfers'] as $leg)
                        <li class="text-warning-600">Unpaired — {{ $leg['sheet'] }} row {{ $leg['row'] }}: {{ number_format($leg['amount']) }} KES {{ $leg['flow'] }} on {{ $leg['date'] }}</li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        @if ($preview['skipped_samples'] !== [])
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">Skipped rows ({{ $preview['skipped_rows'] }})</x-slot>

                <ul class="space-y-1 text-sm">
                    @foreach ($preview['skipped_samples'] as $skipped)
                        <li>{{ $skipped['sheet'] }} row {{ $skipped['row'] }}: {{ $skipped['reason'] }}</li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif

        @foreach ($preview['sheets'] as $sheet)
            @if ($sheet['samples'] !== [])
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Sample rows — {{ $sheet['sheet'] }}</x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10">
                                    <th class="py-2 pr-4 font-medium">Date</th>
                                    <th class="py-2 pr-4 font-medium">From / To</th>
                                    <th class="py-2 pr-4 font-medium">Description</th>
                                    <th class="py-2 pr-4 text-right font-medium">Amount</th>
                                    <th class="py-2 pr-4 font-medium">Flow</th>
                                    <th class="py-2 font-medium">Category</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($sheet['samples'] as $sample)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $sample['date'] }}</td>
                                        <td class="py-2 pr-4">{{ $sample['counterparty'] }}</td>
                                        <td class="py-2 pr-4">{{ $sample['description'] }}</td>
                                        <td class="py-2 pr-4 text-right">{{ number_format($sample['amount']) }}</td>
                                        <td class="py-2 pr-4">{{ $sample['flow'] }}</td>
                                        <td class="py-2">{{ $sample['category'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endforeach
    @endif

    @php($imports = $this->getPastImports())

    @if ($imports !== [])
        <x-filament::section>
            <x-slot name="heading">Past imports</x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">File</th>
                            <th class="py-2 pr-4 font-medium">Year</th>
                            <th class="py-2 pr-4 font-medium">Status</th>
                            <th class="py-2 pr-4 font-medium">Result</th>
                            <th class="py-2 font-medium">Uploaded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($imports as $import)
                            <tr>
                                <td class="py-2 pr-4 font-medium">{{ $import->original_name }}</td>
                                <td class="py-2 pr-4">{{ $import->year }}</td>
                                <td class="py-2 pr-4">
                                    <x-filament::badge :color="$import->status->getColor()">{{ $import->status->getLabel() }}</x-filament::badge>
                                </td>
                                <td class="py-2 pr-4 text-gray-500">
                                    @if (is_array($import->summary))
                                        {{ $import->summary['posted'] ?? 0 }} posted, {{ $import->summary['skipped'] ?? 0 }} skipped, {{ $import->summary['unmapped'] ?? 0 }} unmapped
                                    @else
                                        {{ $import->error ?? '—' }}
                                    @endif
                                </td>
                                <td class="py-2">{{ $import->created_at?->format('j M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @elseif ($preview === null)
        <x-filament::section>
            <x-slot name="heading">No workbook yet</x-slot>
            <x-slot name="description">Upload the treasurer's cashbook workbook to preview how each row will be booked before anything is posted.</x-slot>
        </x-filament::section>
    @endif
</x-filament-panels::page>
