<x-filament-panels::page>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($this->getAccountabilityStats() as $stat)
            <div class="rounded-xl bg-white p-4 shadow-xs ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                <p @class([
                    'mt-1 text-xl font-semibold tracking-tight',
                    'text-success-600 dark:text-success-400' => $stat['color'] === 'success',
                    'text-danger-600 dark:text-danger-400' => $stat['color'] === 'danger',
                    'text-warning-600 dark:text-warning-400' => $stat['color'] === 'warning',
                    'text-gray-950 dark:text-white' => $stat['color'] === 'gray',
                ])>{{ $stat['value'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stat['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <details class="group rounded-xl bg-white p-4 text-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <summary class="flex cursor-pointer items-center gap-2 font-medium text-gray-950 dark:text-white">
            <x-filament::icon icon="heroicon-o-information-circle" class="size-5 text-primary-500" />
            How to read this page
        </summary>
        <ul class="mt-3 space-y-1.5 text-gray-600 dark:text-gray-300">
            <li><strong>Sent</strong> is what was disbursed to the mission or event through approved requisitions.</li>
            <li><strong>Spent</strong> is what the desk recorded as real expenses (hover it for the breakdown; show the category columns from the table’s column toggle).</li>
            <li><strong>To refund</strong> = Sent − Spent + Token. <strong>Balance</strong> = To refund − Refunded, and should be <strong>zero</strong>.</li>
            <li>The <strong>status</strong> is suggested from the numbers until you confirm it. Use <em>Fully accounted</em> or <em>Needs attention</em> to record your verdict for the month.</li>
            <li>Missing expenses or refunds are added on the mission or event itself (<em>Open requisitions &amp; refunds</em>).</li>
        </ul>
    </details>

    {{ $this->table }}
</x-filament-panels::page>
