<div class="text-gray-900">
    @switch($provider)
        @case ('google')
            <x-socialstream-icons.google {{ $attributes }} />
            @break
    @endswitch
</div>
