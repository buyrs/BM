@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true
])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow rounded-lg border border-gray-200']) }}>
    @if($title || $subtitle)
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            @if($title)
                <h3 class="text-lg font-medium leading-6 text-gray-900">{{ $title }}</h3>
            @endif
            @if($subtitle)
                <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    
    <div class="{{ $padding ? 'px-4 py-5 sm:p-6' : '' }}">
        {{ $slot }}
    </div>
</div>
