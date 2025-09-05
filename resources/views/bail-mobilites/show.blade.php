@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold">Bail Mobilité #{{ $bailMobilite->id }}</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Créé le {{ $bailMobilite->created_at->format('d/m/Y H:i') }} par 
                            {{ $bailMobilite->opsUser->name ?? 'Système' }}
                        </p>
                    </div>
                    
                    <div class="flex space-x-2">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            {{ $bailMobilite->status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                            {{ $bailMobilite->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                            {{ $bailMobilite->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            {{ $bailMobilite->status === 'incident' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                        ">
                            {{ str_replace('_', ' ', $bailMobilite->status) }}
                        </span>
                        
                        <a href="{{ route('bail-mobilites.edit', $bailMobilite) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm">
                            Modifier
                        </a>
                        <a href="{{ route('bail-mobilites.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm">
                            ← Retour
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Details -->
                    <div class="lg:col-span-2">
                        <!-- Tenant Information -->
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-semibold mb-3">Informations du locataire</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $bailMobilite->tenant_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Téléphone</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $bailMobilite->tenant_phone ?? 'Non renseigné' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $bailMobilite->tenant_email ?? 'Non renseigné' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $bailMobilite->address }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-semibold mb-3">Dates du bail</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de début</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $bailMobilite->start_date->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date de fin</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $bailMobilite->end_date->format('d/m/Y') }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Durée</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">
                                        {{ $bailMobilite->start_date->diffInDays($bailMobilite->end_date) }} jours
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        @if($bailMobilite->notes)
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                                <h3 class="text-lg font-semibold mb-3">Notes</h3>
                                <p class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $bailMobilite->notes }}</p>
                            </div>
                        @endif

                        <!-- Missions -->
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-3">Missions associées</h3>
                            <div class="space-y-4">
                                <!-- Entry Mission -->
                                @if($bailMobilite->entryMission)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        <h4 class="font-semibold text-blue-600 dark:text-blue-400 mb-2">Mission d'entrée</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Statut:</span>
                                                <span class="ml-2 px-2 py-1 rounded-full text-xs 
                                                    {{ $bailMobilite->entryMission->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                                    {{ $bailMobilite->entryMission->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                                    {{ $bailMobilite->entryMission->status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                                ">
                                                    {{ str_replace('_', ' ', $bailMobilite->entryMission->status) }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Checker:</span>
                                                <span class="ml-2">{{ $bailMobilite->entryMission->agent->name ?? 'Non assigné' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Date:</span>
                                                <span class="ml-2">{{ $bailMobilite->entryMission->scheduled_at->format('d/m/Y') }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Heure:</span>
                                                <span class="ml-2">{{ $bailMobilite->entryMission->scheduled_time ? substr($bailMobilite->entryMission->scheduled_time, 0, 5) : 'Non définie' }}</span>
                                            </div>
                                        </div>
                                        @if($bailMobilite->entryMission->checklist)
                                            <div class="mt-2">
                                                <a href="{{ route('checklists.show', $bailMobilite->entryMission->checklist) }}" 
                                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                                    Voir le checklist d'entrée
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 text-center">
                                        <p class="text-gray-500 dark:text-gray-400">Mission d'entrée non créée</p>
                                    </div>
                                @endif

                                <!-- Exit Mission -->
                                @if($bailMobilite->exitMission)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        <h4 class="font-semibold text-blue-600 dark:text-blue-400 mb-2">Mission de sortie</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Statut:</span>
                                                <span class="ml-2 px-2 py-1 rounded-full text-xs 
                                                    {{ $bailMobilite->exitMission->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                                    {{ $bailMobilite->exitMission->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                                    {{ $bailMobilite->exitMission->status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                                ">
                                                    {{ str_replace('_', ' ', $bailMobilite->exitMission->status) }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Checker:</span>
                                                <span class="ml-2">{{ $bailMobilite->exitMission->agent->name ?? 'Non assigné' }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Date:</span>
                                                <span class="ml-2">{{ $bailMobilite->exitMission->scheduled_at->format('d/m/Y') }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Heure:</span>
                                                <span class="ml-2">{{ $bailMobilite->exitMission->scheduled_time ? substr($bailMobilite->exitMission->scheduled_time, 0, 5) : 'Non définie' }}</span>
                                            </div>
                                        </div>
                                        @if($bailMobilite->exitMission->checklist)
                                            <div class="mt-2">
                                                <a href="{{ route('checklists.show', $bailMobilite->exitMission->checklist) }}" 
                                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                                    Voir le checklist de sortie
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 text-center">
                                        <p class="text-gray-500 dark:text-gray-400">Mission de sortie non créée</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Status Actions -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3">Actions</h3>
                            
                            @if($bailMobilite->status === 'assigned' && $bailMobilite->entryMission && $bailMobilite->entryMission->checklist)
                                <a href="{{ route('ops.mission-validation', $bailMobilite->entryMission) }}" 
                                   class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center px-4 py-2 rounded-md mb-2">
                                    Valider l'entrée
                                </a>
                            @endif
                            
                            @if($bailMobilite->status === 'in_progress' && $bailMobilite->exitMission && $bailMobilite->exitMission->checklist)
                                <a href="{{ route('ops.mission-validation', $bailMobilite->exitMission) }}" 
                                   class="block w-full bg-green-600 hover:bg-green-700 text-white text-center px-4 py-2 rounded-md mb-2">
                                    Valider la sortie
                                </a>
                            @endif
                            
                            @if($bailMobilite->status === 'incident')
                                <button onclick="openIncidentModal()" 
                                        class="block w-full bg-red-600 hover:bg-red-700 text-white text-center px-4 py-2 rounded-md mb-2">
                                    Gérer l'incident
                                </button>
                            @endif

                            <a href="{{ route('bail-mobilites.edit', $bailMobilite) }}" 
                               class="block w-full bg-gray-600 hover:bg-gray-700 text-white text-center px-4 py-2 rounded-md">
                                Modifier le bail
                            </a>
                        </div>

                        <!-- Timeline -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3">Historique</h3>
                            <div class="space-y-2 text-sm">
                                <div>
                                    <span class="font-medium">Créé:</span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ $bailMobilite->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                @if($bailMobilite->updated_at->gt($bailMobilite->created_at))
                                    <div>
                                        <span class="font-medium">Dernière modification:</span>
                                        <span class="text-gray-600 dark:text-gray-400">{{ $bailMobilite->updated_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                                @if($bailMobilite->entryMission && $bailMobilite->entryMission->completed_at)
                                    <div>
                                        <span class="font-medium">Entrée validée:</span>
                                        <span class="text-gray-600 dark:text-gray-400">{{ $bailMobilite->entryMission->completed_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                                @if($bailMobilite->exitMission && $bailMobilite->exitMission->completed_at)
                                    <div>
                                        <span class="font-medium">Sortie validée:</span>
                                        <span class="text-gray-600 dark:text-gray-400">{{ $bailMobilite->exitMission->completed_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Notifications -->
                        @if($bailMobilite->notifications->count() > 0)
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h3 class="text-lg font-semibold mb-3">Notifications récentes</h3>
                                <div class="space-y-2">
                                    @foreach($bailMobilite->notifications->take(5) as $notification)
                                        <div class="text-sm p-2 rounded bg-gray-50 dark:bg-gray-700">
                                            <div class="font-medium">{{ $notification->type }}</div>
                                            <div class="text-gray-600 dark:text-gray-400">{{ $notification->created_at->format('d/m/Y H:i') }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incident Modal (would be implemented with Alpine.js) -->
<div id="incidentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <h3 class="text-lg font-semibold mb-4">Gérer l'incident</h3>
        <!-- Incident handling form would go here -->
        <div class="flex justify-end space-x-2">
            <button onclick="closeIncidentModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                Annuler
            </button>
            <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                Traiter
            </button>
        </div>
    </div>
</div>

<script>
    function openIncidentModal() {
        document.getElementById('incidentModal').classList.remove('hidden');
    }
    
    function closeIncidentModal() {
        document.getElementById('incidentModal').classList.add('hidden');
    }
</script>
@endsection