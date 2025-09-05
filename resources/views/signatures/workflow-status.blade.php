@extends('layouts.app')

@section('title', 'Statut du Workflow de Signature')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="text-center">
                    <h1 class="text-2xl font-bold">
                        Workflow de Signature
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Suivi du processus de signature multi-parties
                    </p>
                </div>
            </div>
        </div>

        <!-- Workflow Status -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <!-- Progress Steps -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Progression du workflow
                    </h3>
                    
                    <div class="flex items-center justify-between mb-4">
                        @foreach(['pending' => 'En attente', 'in_progress' => 'En cours', 'completed' => 'Terminé', 'cancelled' => 'Annulé'] as $status => $label)
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center 
                                    @if($workflow->status === $status) bg-blue-600 text-white
                                    @elseif(array_search($status, array_keys(['pending' => 1, 'in_progress' => 2, 'completed' => 3, 'cancelled' => 4])) 
                                           < array_search($workflow->status, array_keys(['pending' => 1, 'in_progress' => 2, 'completed' => 3, 'cancelled' => 4]))) bg-green-600 text-white
                                    @else bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 @endif">
                                    {{ array_search($status, array_keys(['pending' => 1, 'in_progress' => 2, 'completed' => 3, 'cancelled' => 4])) }}
                                </div>
                                <span class="text-xs mt-1 text-gray-600 dark:text-gray-400">{{ $label }}</span>
                            </div>
                            @if(!$loop->last)
                                <div class="flex-1 h-1 bg-gray-300 dark:bg-gray-600 mx-2"></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Workflow Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
                            Informations du workflow
                        </h4>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Référence:</span>
                                <span class="text-gray-900 dark:text-gray-100 font-medium">{{ $workflow->reference }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Statut:</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    @if($workflow->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($workflow->status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($workflow->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                    {{ ucfirst($workflow->status) }}
                                </span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Créé le:</span>
                                <span class="text-gray-900 dark:text-gray-100">{{ $workflow->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            
                            @if($workflow->completed_at)
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Terminé le:</span>
                                <span class="text-gray-900 dark:text-gray-100">{{ $workflow->completed_at->format('d/m/Y H:i') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
                            Document associé
                        </h4>
                        
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Type:</span>
                                <span class="text-gray-900 dark:text-gray-100">{{ ucfirst($workflow->document_type) }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Référence:</span>
                                <span class="text-gray-900 dark:text-gray-100">{{ $workflow->document_reference }}</span>
                            </div>
                            
                            @if($workflow->document)
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Titre:</span>
                                <span class="text-gray-900 dark:text-gray-100">{{ $workflow->document->title }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Signatories -->
                <div class="mb-6">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
                        Signataires
                    </h4>
                    
                    <div class="space-y-3">
                        @foreach($workflow->signatories as $signatory)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                            {{ $loop->iteration }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $signatory->name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $signatory->email }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                                        @if($signatory->signed_at) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($signatory->invitation_sent_at) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                                        @if($signatory->signed_at)
                                            Signé le {{ $signatory->signed_at->format('d/m/Y') }}
                                        @elseif($signatory->invitation_sent_at)
                                            Invitation envoyée
                                        @else
                                            En attente
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-center space-x-4">
                    <a href="{{ route('missions.show', $workflow->mission_id) }}" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                        Retour à la mission
                    </a>
                    
                    @if($workflow->status === 'in_progress' && auth()->user()->can('manage', $workflow))
                        <button type="button" 
                                onclick="sendReminders()"
                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md">
                            Envoyer des rappels
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Journal d'activité
                </h3>
                
                <div class="space-y-3">
                    @forelse($workflow->activities->sortByDesc('created_at') as $activity)
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $activity->description }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activity->created_at->format('d/m/Y H:i') }}
                                    @if($activity->causer)
                                        par {{ $activity->causer->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Aucune activité enregistrée</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function sendReminders() {
        if (confirm('Êtes-vous sûr de vouloir envoyer des rappels à tous les signataires ?')) {
            fetch('{{ route('signatures.send-reminders', $workflow) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Rappels envoyés avec succès');
                    location.reload();
                } else {
                    alert('Erreur lors de l\'envoi des rappels');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur lors de l\'envoi des rappels');
            });
        }
    }
</script>
@endsection