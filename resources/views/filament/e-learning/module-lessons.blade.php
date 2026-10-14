<div class="fi-resource-relation-manager flex flex-col gap-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        The lessons in this module, in the order students take them.
        @if ($this->canChangeCurriculum())
            Drag <x-filament::icon icon="heroicon-m-bars-3" class="inline h-4 w-4 align-text-bottom" /> to reorder.
        @endif
    </p>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        @include('filament.e-learning.partials.lessons', ['module' => $this->module()])
    </div>

    <x-filament-actions::modals />
</div>
