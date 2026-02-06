@props([
    'items' => [],  // Array of ['label' => 'Home', 'href' => '/'] or just strings
    'separator' => 'chevron', // chevron, slash, dot
])

@php
    $separators = [
        'chevron' => '<svg class="w-4 h-4 text-secondary-400 dark:text-secondary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
        'slash' => '<span class="text-secondary-300 dark:text-secondary-600">/</span>',
        'dot' => '<span class="w-1 h-1 rounded-full bg-secondary-300 dark:bg-secondary-600"></span>',
    ];
    
    $separatorHtml = $separators[$separator] ?? $separators['chevron'];
@endphp

<nav 
    aria-label="Breadcrumb"
    {{ $attributes->merge(['class' => 'hidden sm:flex items-center space-x-1 text-sm']) }}
>
    {{-- Home icon --}}
    <a 
        href="{{ route('dashboard') }}" 
        class="text-secondary-400 dark:text-secondary-500 hover:text-secondary-600 dark:hover:text-secondary-300 transition-colors"
        title="Home"
    >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
        </svg>
    </a>
    
    @foreach($items as $index => $item)
        {{-- Separator --}}
        <span class="flex items-center justify-center w-5">
            {!! $separatorHtml !!}
        </span>
        
        @php
            $isLast = $index === count($items) - 1;
            $label = is_array($item) ? $item['label'] : $item;
            $href = is_array($item) ? ($item['href'] ?? null) : null;
            $icon = is_array($item) ? ($item['icon'] ?? null) : null;
        @endphp
        
        @if($isLast)
            {{-- Current page (not a link) --}}
            <span 
                class="flex items-center gap-1.5 font-medium text-secondary-900 dark:text-white truncate max-w-[200px]"
                aria-current="page"
            >
                @if($icon)
                    <span class="flex-shrink-0">{!! $icon !!}</span>
                @endif
                {{ $label }}
            </span>
        @else
            {{-- Link --}}
            @if($href)
                <a 
                    href="{{ $href }}"
                    class="flex items-center gap-1.5 text-secondary-500 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors truncate max-w-[150px]"
                >
                    @if($icon)
                        <span class="flex-shrink-0">{!! $icon !!}</span>
                    @endif
                    {{ $label }}
                </a>
            @else
                <span class="flex items-center gap-1.5 text-secondary-500 dark:text-secondary-400 truncate max-w-[150px]">
                    @if($icon)
                        <span class="flex-shrink-0">{!! $icon !!}</span>
                    @endif
                    {{ $label }}
                </span>
            @endif
        @endif
    @endforeach
</nav>
