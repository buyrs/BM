@props(['striped' => true, 'hoverable' => true, 'compact' => false])

@php
$baseClasses = 'min-w-full divide-y divide-secondary-200 dark:divide-secondary-700';
$wrapperClasses = 'overflow-hidden shadow-sm dark:shadow-none ring-1 ring-secondary-200 dark:ring-secondary-700 rounded-xl';
$tbodyClasses = 'bg-white dark:bg-secondary-800 divide-y divide-secondary-200 dark:divide-secondary-700';

if ($striped) {
    $tbodyClasses .= ' [&>tr:nth-child(odd)]:bg-secondary-50 dark:[&>tr:nth-child(odd)]:bg-secondary-900/50';
}

if ($hoverable) {
    $tbodyClasses .= ' [&>tr:hover]:bg-secondary-100 dark:[&>tr:hover]:bg-secondary-700';
}

$thClasses = 'px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider bg-secondary-50 dark:bg-secondary-900';
$tdClasses = $compact ? 'px-6 py-2 whitespace-nowrap text-sm' : 'px-6 py-4 whitespace-nowrap text-sm';
@endphp

<div {{ $attributes->merge(['class' => $wrapperClasses]) }}>
    <table class="{{ $baseClasses }}">
        @isset($header)
            <thead class="bg-secondary-50 dark:bg-secondary-900">
                {{ $header }}
            </thead>
        @endisset
        
        <tbody class="{{ $tbodyClasses }}">
            {{ $slot }}
        </tbody>
        
        @isset($footer)
            <tfoot class="bg-secondary-50 dark:bg-secondary-900">
                {{ $footer }}
            </tfoot>
        @endisset
    </table>
</div>

@push('styles')
<style>
.table-th {
    @apply px-6 py-3 text-left text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wider bg-secondary-50 dark:bg-secondary-900;
}

.table-td {
    @apply {{ $tdClasses }} text-secondary-900 dark:text-secondary-100;
}

.table-td-text {
    @apply text-secondary-900 dark:text-secondary-100;
}

.table-td-muted {
    @apply text-secondary-600 dark:text-secondary-400;
}
</style>
@endpush