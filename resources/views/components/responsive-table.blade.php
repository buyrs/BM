@props([
    'title' => '',
    'controls' => null,
    'responsive' => true, // Whether to make the table responsive on small screens
])

<div class="w-full">
    @if($title || $controls)
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 space-y-2 md:space-y-0">
        @if($title)
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">{{ $title }}</h2>
        @endif
        @if($controls)
            <div class="flex space-x-2">
                {{ $controls }}
            </div>
        @endif
    </div>
    @endif

    @if($responsive)
        <div class="overflow-x-auto rounded-lg shadow">
            <table {{ $attributes->merge(['class' => 'min-w-full bg-white dark:bg-gray-800']) }}>
                {{ $slot }}
            </table>
        </div>
    @else
        <table {{ $attributes->merge(['class' => 'min-w-full bg-white dark:bg-gray-800 rounded-lg shadow']) }}>
            {{ $slot }}
        </table>
    @endif
</div>