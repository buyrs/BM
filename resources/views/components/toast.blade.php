@props(['type' => 'info', 'title' => null, 'dismissible' => true, 'duration' => 5000])

@php
$baseClasses = 'fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-xl shadow-strong border';

$types = [
    'info' => 'border-primary-200',
    'success' => 'border-success-200',
    'warning' => 'border-warning-200',
    'danger' => 'border-danger-200',
];

$iconTypes = [
    'info' => [
        'bg' => 'bg-primary-100',
        'text' => 'text-primary-600',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5v3a.75.75 0 001.5 0v-3A.75.75 0 009 9z" clip-rule="evenodd"></path></svg>'
    ],
    'success' => [
        'bg' => 'bg-success-100',
        'text' => 'text-success-600',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.236 4.53L8.1 10.612a.75.75 0 00-1.2.956l2.5 3.125a.75.75 0 001.207-.103l4.25-5.931z" clip-rule="evenodd"></path></svg>'
    ],
    'warning' => [
        'bg' => 'bg-warning-100',
        'text' => 'text-warning-600',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>'
    ],
    'danger' => [
        'bg' => 'bg-danger-100',
        'text' => 'text-danger-600',
        'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"></path></svg>'
    ],
];

$typeConfig = $iconTypes[$type];
$classes = $baseClasses . ' ' . $types[$type];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }} 
     x-data="{ 
         show: true,
         init() {
             @if($duration && !$dismissible)
                 setTimeout(() => this.show = false, {{ $duration }})
             @endif
         }
     }" 
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-x-full"
     x-transition:enter-end="opacity-100 transform translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform translate-x-0"
     x-transition:leave-end="opacity-0 transform translate-x-full"
     style="display: none;">
    
    <div class="p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center w-8 h-8 {{ $typeConfig['bg'] }} rounded-lg">
                    <div class="{{ $typeConfig['text'] }}">
                        {!! $typeConfig['icon'] !!}
                    </div>
                </div>
            </div>
            
            <div class="ml-3 flex-1">
                @if($title)
                    <h3 class="text-sm font-semibold text-secondary-900 mb-1">{{ $title }}</h3>
                @endif
                <div class="text-sm text-secondary-700">
                    {{ $slot }}
                </div>
            </div>
            
            @if($dismissible)
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button @click="show = false" type="button" 
                                class="inline-flex rounded-lg p-1.5 text-secondary-400 hover:text-secondary-600 hover:bg-secondary-100 focus:outline-none focus:ring-2 focus:ring-secondary-300 transition-colors duration-200">
                            <span class="sr-only">Dismiss</span>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    @if($duration && $dismissible)
        <div class="h-1 bg-secondary-100 rounded-b-xl overflow-hidden">
            <div class="h-full {{ $typeConfig['bg'] }} rounded-b-xl"
                 x-data="{ progress: 100 }"
                 x-init="
                     let interval = setInterval(() => {
                         progress -= (100 / {{ $duration / 100 }});
                         if (progress <= 0) {
                             clearInterval(interval);
                             show = false;
                         }
                     }, 100);
                 "
                 :style="`width: ${progress}%`">
            </div>
        </div>
    @endif
</div>