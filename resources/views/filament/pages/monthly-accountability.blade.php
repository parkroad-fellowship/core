<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($this->getAccountabilityStats() as $stat)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                <div class="mt-1 text-xl font-semibold tracking-tight">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{ $this->table }}
</x-filament-panels::page>
