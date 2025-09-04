@props([
    'type' => 'info',
    'message' => '',
    'dismissible' => true
])

@php
$classes = [
    'success' => 'bg-green-50 border border-green-200 text-green-800',
    'error' => 'bg-red-50 border border-red-200 text-red-800',
    'warning' => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
    'info' => 'bg-blue-50 border border-blue-200 text-blue-800',
];

$icons = [
    'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'error' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z',
    'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-md p-4 ' . $classes[$type]]) }}>
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="{{ $icons[$type] }}" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm font-medium">
                {{ $message ?: $slot }}
            </p>
        </div>
        @if($dismissible)
        <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
                <button type="button" class="inline-flex rounded-md p-1.5 hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2"
                        onclick="this.closest('[role=alert], .alert, .notification').remove()">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
        @endif
    </div>
</div>
