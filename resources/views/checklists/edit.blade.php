@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ $mission->mission_type === 'checkin' ? 'Checklist d\'Entrée' : 'Checklist de Sortie' }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Mission: {{ $mission->reference ?? 'BM-' . $mission->id }} - {{ $mission->address }}
                        </p>
                        @if($mission->agent)
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                            Agent assigné: {{ $mission->agent->name }}
                        </p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Statut du checklist:
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($checklist->status === 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($checklist->status === 'submitted') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                            @elseif($checklist->status === 'validated') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($checklist->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                            {{ ucfirst($checklist->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Checklist Form -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                @include('components.checklist.dynamic-form', [
                    'mission' => $mission,
                    'checklist' => $checklist,
                    'mode' => 'edit'
                ])
            </div>
        </div>

        <!-- Additional Actions -->
        <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Actions supplémentaires</h3>
                
                <div class="flex flex-wrap gap-4">
                    <!-- Generate PDF -->
                    <a 
                        href="{{ route('pdf.checklist', $checklist) }}"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Générer PDF
                    </a>

                    <!-- Back to Mission -->
                    <a 
                        href="{{ route('missions.show', $mission) }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour à la mission
                    </a>
                </div>
            </div>
        </div>

        <!-- Validation Comments (if any) -->
        @if($checklist->ops_validation_comments)
        <div class="mt-6 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Commentaires de validation
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>{{ $checklist->ops_validation_comments }}</p>
                    </div>
                    @if($checklist->validatedBy)
                    <div class="mt-1 text-xs text-yellow-600 dark:text-yellow-400">
                        Par {{ $checklist->validatedBy->name }} le {{ $checklist->validated_at->format('d/m/Y à H:i') }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection