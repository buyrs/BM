@props([
    'property' => null,
    'compact' => false,
])

<!-- Property Card Component -->
<div 
    {{ $attributes->merge([
        'class' => $compact 
            ? 'bg-white dark:bg-secondary-800 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow'
            : 'bg-white dark:bg-secondary-800 rounded-xl p-4 shadow-soft hover:shadow-lg transition-all'
    ]) }}
>
    @if($property)
        <div class="flex items-start gap-{{ $compact ? '3' : '4' }}">
            <!-- Property Image/Icon -->
            <div class="w-{{ $compact ? '10' : '14' }} h-{{ $compact ? '10' : '14' }} rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-{{ $compact ? '5' : '6' }} h-{{ $compact ? '5' : '6' }} text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>

            <!-- Property Info -->
            <div class="flex-1 min-w-0">
                <h3 class="font-{{ $compact ? 'medium' : 'semibold' }} text-{{ $compact ? 'sm' : 'base' }} text-secondary-900 dark:text-white truncate">
                    {{ $property->name ?? 'Unnamed Property' }}
                </h3>
                <p class="text-{{ $compact ? 'xs' : 'sm' }} text-secondary-500 dark:text-secondary-400 truncate">
                    {{ $property->property_address ?? $property->address ?? '' }}
                </p>
                
                @if(!$compact && ($property->city || $property->type))
                    <div class="flex items-center gap-2 mt-2">
                        @if($property->city)
                            <span class="inline-flex items-center gap-1 text-xs text-secondary-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $property->city }}
                            </span>
                        @endif
                        @if($property->type)
                            <span class="px-2 py-0.5 bg-secondary-100 dark:bg-secondary-700 text-secondary-600 dark:text-secondary-300 text-xs rounded-full">
                                {{ ucfirst($property->type) }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Actions Slot -->
            @if(!$compact)
                <div class="flex-shrink-0">
                    {{ $actions ?? '' }}
                </div>
            @endif
        </div>

        <!-- Stats (full size only) -->
        @if(!$compact && isset($property->missions_count))
            <div class="flex items-center gap-4 mt-4 pt-4 border-t border-secondary-100 dark:border-secondary-700">
                <div class="text-center">
                    <p class="text-lg font-bold text-secondary-900 dark:text-white">{{ $property->missions_count ?? 0 }}</p>
                    <p class="text-xs text-secondary-500">Inspections</p>
                </div>
                @if(isset($property->pending_inspections))
                    <div class="text-center">
                        <p class="text-lg font-bold text-warning-600">{{ $property->pending_inspections }}</p>
                        <p class="text-xs text-secondary-500">Pending</p>
                    </div>
                @endif
            </div>
        @endif
    @else
        <div class="text-center py-4 text-secondary-500">
            No property data
        </div>
    @endif
</div>
