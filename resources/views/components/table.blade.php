@props(['striped' => true, 'hoverable' => true, 'compact' => false])

@php
$baseClasses = 'min-w-full divide-y divide-secondary-200';
$wrapperClasses = 'overflow-hidden shadow-sm ring-1 ring-secondary-200 rounded-xl';
$tbodyClasses = 'bg-white divide-y divide-secondary-200';

if ($striped) {
    $tbodyClasses .= ' [&>tr:nth-child(odd)]:bg-secondary-50';
}

if ($hoverable) {
    $tbodyClasses .= ' [&>tr:hover]:bg-secondary-100';
}

$thClasses = 'px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider bg-secondary-50';
$tdClasses = $compact ? 'px-6 py-2 whitespace-nowrap text-sm' : 'px-6 py-4 whitespace-nowrap text-sm';
@endphp

<div {{ $attributes->merge(['class' => $wrapperClasses]) }}>
    <table class="{{ $baseClasses }}">
        @isset($header)
            <thead class="bg-secondary-50">
                {{ $header }}
            </thead>
        @endisset
        
        <tbody class="{{ $tbodyClasses }}">
            {{ $slot }}
        </tbody>
        
        @isset($footer)
            <tfoot class="bg-secondary-50">
                {{ $footer }}
            </tfoot>
        @endisset
    </table>
</div>

@push('styles')
<style>
.table-th {
    @apply px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider bg-secondary-50;
}

.table-td {
    @apply {{ $tdClasses }};
}

.table-td-text {
    @apply text-secondary-900;
}

.table-td-muted {
    @apply text-secondary-600;
}
</style>
@endpush