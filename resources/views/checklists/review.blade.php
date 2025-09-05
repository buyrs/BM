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
                            Validation du Checklist
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Mission: {{ $mission->reference ?? 'BM-' . $mission->id }} - {{ $mission->address }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                            Type: {{ $mission->mission_type === 'checkin' ? 'Entrée' : 'Sortie' }}
                        </p>
                        @if($mission->agent)
                        <p class="text-sm text-gray-500 dark:text-gray-500">
                            Agent: {{ $mission->agent->name }}
                        </p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Statut actuel:
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

        <!-- Validation Form -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Validation OPS</h2>
                
                <form action="{{ route('checklists.validate', $checklist) }}" method="POST">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Decision -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Décision de validation *
                            </label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="radio" id="validate" name="decision" value="validate" 
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" required>
                                    <label for="validate" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Valider le checklist
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="reject" name="decision" value="reject" 
                                           class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300">
                                    <label for="reject" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Rejeter le checklist
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="request_changes" name="decision" value="request_changes" 
                                           class="focus:ring-yellow-500 h-4 w-4 text-yellow-600 border-gray-300">
                                    <label for="request_changes" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Demander des modifications
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Comments -->
                        <div>
                            <label for="comments" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Commentaires de validation
                            </label>
                            <textarea id="comments" name="comments" rows="4"
                                      class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                      placeholder="Commentaires sur la validation...">{{ old('comments') }}</textarea>
                            @error('comments')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Checklist Preview -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Aperçu du checklist</h3>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Nombre d'éléments:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $checklist->items->count() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Photos:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $checklist->items->sum(fn($item) => $item->photos->count()) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Soumis le:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ $checklist->updated_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('checklists.show', ['mission' => $mission, 'checklist' => $checklist]) }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                            Voir le détail
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            Soumettre la validation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg text-center">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Total éléments</h3>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-300">{{ $checklist->items->count() }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg text-center">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">État bon</h3>
                <p class="text-2xl font-bold text-green-600 dark:text-green-300">
                    {{ $checklist->items->where('condition', 'good')->count() }}
                </p>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg text-center">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">État moyen</h3>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-300">
                    {{ $checklist->items->where('condition', 'fair')->count() }}
                </p>
            </div>
            <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg text-center">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">État mauvais</h3>
                <p class="text-2xl font-bold text-red-600 dark:text-red-300">
                    {{ $checklist->items->where('condition', 'poor')->count() }}
                </p>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Activité récente</h3>
                
                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Créé le {{ $checklist->created_at->format('d/m/Y à H:i') }}
                    </div>
                    
                    @if($checklist->updated_at->gt($checklist->created_at))
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Dernière modification le {{ $checklist->updated_at->format('d/m/Y à H:i') }}
                        </div>
                    @endif
                    
                    @if($checklist->status === 'submitted')
                        <div class="flex items-center text-sm text-blue-600 dark:text-blue-400">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Soumis pour validation
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const decisionRadios = document.querySelectorAll('input[name="decision"]');
        const commentsTextarea = document.getElementById('comments');
        
        // Make comments required only for rejection or change requests
        decisionRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'reject' || this.value === 'request_changes') {
                    commentsTextarea.setAttribute('required', 'required');
                } else {
                    commentsTextarea.removeAttribute('required');
                }
            });
        });
        
        // Initialize required state
        const selectedDecision = document.querySelector('input[name="decision"]:checked');
        if (selectedDecision && (selectedDecision.value === 'reject' || selectedDecision.value === 'request_changes')) {
            commentsTextarea.setAttribute('required', 'required');
        }
    });
</script>
@endsection