@props([
    'type' => 'text',      // text, card, avatar, table-row, image, button
    'lines' => 3,          // Number of text lines
    'width' => 'full',     // full, 3/4, 1/2, 1/3, 1/4
    'height' => null,      // Custom height (e.g., 'h-32')
    'rounded' => 'md',     // none, sm, md, lg, xl, full
    'animate' => true,     // Enable shimmer animation
    'count' => 1,          // Number of skeletons to render
])

@php
    $widthClasses = [
        'full' => 'w-full',
        '3/4' => 'w-3/4',
        '1/2' => 'w-1/2',
        '1/3' => 'w-1/3',
        '1/4' => 'w-1/4',
    ];
    
    $roundedClasses = [
        'none' => 'rounded-none',
        'sm' => 'rounded-sm',
        'md' => 'rounded-md',
        'lg' => 'rounded-lg',
        'xl' => 'rounded-xl',
        'full' => 'rounded-full',
    ];
    
    $baseClass = 'bg-secondary-200 dark:bg-secondary-700';
    $shimmerClass = $animate ? 'animate-pulse' : '';
    $widthClass = $widthClasses[$width] ?? 'w-full';
    $roundedClass = $roundedClasses[$rounded] ?? 'rounded-md';
@endphp

@for ($i = 0; $i < $count; $i++)
    @switch($type)
        @case('text')
            <div class="space-y-3 {{ $attributes->get('class', '') }}">
                @for ($j = 0; $j < $lines; $j++)
                    @php
                        // Vary widths for more natural look
                        $lineWidths = ['w-full', 'w-11/12', 'w-10/12', 'w-9/12', 'w-3/4', 'w-2/3'];
                        $lineWidth = $lineWidths[$j % count($lineWidths)];
                        // Last line is shorter
                        if ($j === $lines - 1) {
                            $lineWidth = 'w-1/2';
                        }
                    @endphp
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 {{ $lineWidth }} {{ $roundedClass }}"></div>
                @endfor
            </div>
            @break
            
        @case('card')
            <div class="bg-white dark:bg-secondary-800 {{ $roundedClass }} shadow-soft p-4 {{ $attributes->get('class', '') }}">
                <div class="flex items-center space-x-4">
                    {{-- Avatar --}}
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-12 w-12 rounded-full flex-shrink-0"></div>
                    
                    {{-- Content --}}
                    <div class="flex-1 space-y-2">
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-3/4 {{ $roundedClass }}"></div>
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-3 w-1/2 {{ $roundedClass }}"></div>
                    </div>
                </div>
                
                {{-- Body --}}
                <div class="mt-4 space-y-2">
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-full {{ $roundedClass }}"></div>
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-5/6 {{ $roundedClass }}"></div>
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-4/6 {{ $roundedClass }}"></div>
                </div>
                
                {{-- Actions --}}
                <div class="mt-4 flex space-x-3">
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-8 w-20 {{ $roundedClass }}"></div>
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-8 w-16 {{ $roundedClass }}"></div>
                </div>
            </div>
            @break
            
        @case('avatar')
            <div class="{{ $baseClass }} {{ $shimmerClass }} {{ $height ?? 'h-10 w-10' }} rounded-full {{ $attributes->get('class', '') }}"></div>
            @break
            
        @case('table-row')
            <div class="flex items-center space-x-4 py-3 {{ $attributes->get('class', '') }}">
                {{-- Checkbox placeholder --}}
                <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-4 rounded"></div>
                
                {{-- Avatar --}}
                <div class="{{ $baseClass }} {{ $shimmerClass }} h-10 w-10 rounded-full flex-shrink-0"></div>
                
                {{-- Name --}}
                <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-32 {{ $roundedClass }}"></div>
                
                {{-- Email --}}
                <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-48 {{ $roundedClass }} hidden sm:block"></div>
                
                {{-- Status --}}
                <div class="{{ $baseClass }} {{ $shimmerClass }} h-6 w-16 rounded-full"></div>
                
                {{-- Date --}}
                <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-24 {{ $roundedClass }} hidden md:block"></div>
                
                {{-- Actions --}}
                <div class="ml-auto flex space-x-2">
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-8 w-8 rounded-lg"></div>
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-8 w-8 rounded-lg"></div>
                </div>
            </div>
            @break
            
        @case('image')
            <div class="{{ $baseClass }} {{ $shimmerClass }} {{ $height ?? 'h-48' }} {{ $widthClass }} {{ $roundedClass }} {{ $attributes->get('class', '') }}">
                {{-- Image icon placeholder --}}
                <div class="flex items-center justify-center h-full">
                    <svg class="w-10 h-10 text-secondary-300 dark:text-secondary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            @break
            
        @case('button')
            <div class="{{ $baseClass }} {{ $shimmerClass }} h-10 {{ $widthClass ?? 'w-24' }} {{ $roundedClass }} {{ $attributes->get('class', '') }}"></div>
            @break
            
        @case('stats-card')
            <div class="bg-white dark:bg-secondary-800 {{ $roundedClass }} shadow-soft p-6 {{ $attributes->get('class', '') }}">
                <div class="flex items-center justify-between">
                    <div class="space-y-2">
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-20 {{ $roundedClass }}"></div>
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-8 w-16 {{ $roundedClass }}"></div>
                    </div>
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-12 w-12 rounded-lg"></div>
                </div>
                <div class="mt-4">
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-3 w-28 {{ $roundedClass }}"></div>
                </div>
            </div>
            @break
            
        @case('mission-card')
            <div class="bg-white dark:bg-secondary-800 {{ $roundedClass }} shadow-soft overflow-hidden {{ $attributes->get('class', '') }}">
                {{-- Image --}}
                <div class="{{ $baseClass }} {{ $shimmerClass }} h-40 w-full"></div>
                
                {{-- Content --}}
                <div class="p-4 space-y-3">
                    {{-- Title --}}
                    <div class="{{ $baseClass }} {{ $shimmerClass }} h-5 w-3/4 {{ $roundedClass }}"></div>
                    
                    {{-- Address --}}
                    <div class="flex items-center space-x-2">
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-4 rounded"></div>
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-3 w-2/3 {{ $roundedClass }}"></div>
                    </div>
                    
                    {{-- Date --}}
                    <div class="flex items-center space-x-2">
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-4 w-4 rounded"></div>
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-3 w-24 {{ $roundedClass }}"></div>
                    </div>
                    
                    {{-- Status badge --}}
                    <div class="flex justify-between items-center pt-2">
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-6 w-20 rounded-full"></div>
                        <div class="{{ $baseClass }} {{ $shimmerClass }} h-8 w-8 rounded-lg"></div>
                    </div>
                </div>
            </div>
            @break
            
        @default
            <div class="{{ $baseClass }} {{ $shimmerClass }} {{ $height ?? 'h-4' }} {{ $widthClass }} {{ $roundedClass }} {{ $attributes->get('class', '') }}"></div>
    @endswitch

    @if ($count > 1 && $i < $count - 1)
        <div class="my-4"></div>
    @endif
@endfor
