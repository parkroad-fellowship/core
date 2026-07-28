<div class="p-4 bg-gray-900 text-gray-100 rounded-lg font-mono text-xs overflow-x-auto">
    <pre>{{ json_encode($data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</div>
