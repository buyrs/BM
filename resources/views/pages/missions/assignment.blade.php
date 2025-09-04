<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Assignation des Missions') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('blade-missions.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Liste des Missions
                </a>
                <a href="{{ route('blade-missions.calendar') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Planning
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Non assignées (aujourd'hui)</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['unassigned_today'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Assignées (aujourd'hui)</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['assigned_today'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Terminées (aujourd'hui)</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['completed_today'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Temps moyen d'assignation</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['avg_assignment_time'] }}min</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('mission-assignments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Agent Filter -->
                        <div>
                            <label for="agent" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Agent</label>
                            <select name="agent" 
                                    id="agent"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les agents</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent['id'] }}" {{ ($filters['agent'] ?? '') == $agent['id'] ? 'selected' : '' }}>
                                        {{ $agent['name'] }} ({{ $agent['current_missions'] }} missions)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                            <select name="status" 
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les statuts</option>
                                <option value="unassigned" {{ ($filters['status'] ?? '') === 'unassigned' ? 'selected' : '' }}>Non assignées</option>
                                <option value="assigned" {{ ($filters['status'] ?? '') === 'assigned' ? 'selected' : '' }}>Assignées</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date début</label>
                            <input type="date" 
                                   name="date_from" 
                                   id="date_from"
                                   value="{{ $filters['date_from'] ?? '' }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date fin</label>
                            <input type="date" 
                                   name="date_to" 
                                   id="date_to"
                                   value="{{ $filters['date_to'] ?? '' }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex items-end space-x-2">
                            <button type="submit" 
                                    class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Filtrer
                            </button>
                            <a href="{{ route('mission-assignments.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="assignmentComponent()">
                <!-- Unassigned Missions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Missions non assignées ({{ $unassignedMissions->count() }})
                            </h3>
                            @if($unassignedMissions->count() > 0)
                            <button @click="openBulkAssignModal()" 
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                                Assignation en lot
                            </button>
                            @endif
                        </div>

                        @if($unassignedMissions->count() > 0)
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                @foreach($unassignedMissions as $mission)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    Mission #{{ $mission->id }}
                                                </h4>
                                                @if($mission->isBailMobiliteMission())
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        Bail Mobilité
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $mission->address }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-500">{{ $mission->tenant_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                                {{ $mission->scheduled_at?->format('d/m/Y H:i') ?? 'Date non définie' }}
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button @click="openAssignModal({{ $mission->id }}, '{{ $mission->address }}', '{{ $mission->scheduled_at?->format('Y-m-d') }}')"
                                                    class="bg-blue-500 hover:bg-blue-700 text-white text-xs font-bold py-1 px-3 rounded">
                                                Assigner
                                            </button>
                                            <a href="{{ route('blade-missions.show', $mission) }}" 
                                               class="bg-gray-500 hover:bg-gray-700 text-white text-xs font-bold py-1 px-3 rounded">
                                                Voir
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucune mission non assignée</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Toutes les missions sont assignées.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Assigned Missions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Missions assignées ({{ $assignedMissions->count() }})
                        </h3>

                        @if($assignedMissions->count() > 0)
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                @foreach($assignedMissions as $mission)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    Mission #{{ $mission->id }}
                                                </h4>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                    @if($mission->status === 'assigned') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                    @elseif($mission->status === 'in_progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                    @endif">
                                                    {{ ucfirst($mission->status) }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $mission->address }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-500">{{ $mission->tenant_name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                                {{ $mission->scheduled_at?->format('d/m/Y H:i') ?? 'Date non définie' }}
                                            </p>
                                            <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                                Agent: {{ $mission->agent?->name ?? 'Non assigné' }}
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button @click="openReassignModal({{ $mission->id }}, '{{ $mission->address }}', {{ $mission->agent?->id ?? 'null' }}, '{{ $mission->agent?->name ?? '' }}')"
                                                    class="bg-yellow-500 hover:bg-yellow-700 text-white text-xs font-bold py-1 px-3 rounded">
                                                Réassigner
                                            </button>
                                            <a href="{{ route('blade-missions.show', $mission) }}" 
                                               class="bg-gray-500 hover:bg-gray-700 text-white text-xs font-bold py-1 px-3 rounded">
                                                Voir
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucune mission assignée</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aucune mission n'est actuellement assignée.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Agent Workload Chart -->
            @if(count($stats['agent_workload']) > 0)
            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Charge de travail des agents (aujourd'hui)
                    </h3>
                    <div class="space-y-3">
                        @foreach($stats['agent_workload'] as $agent)
                        <div class="flex items-center space-x-4">
                            <div class="w-32 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $agent['name'] }}
                            </div>
                            <div class="flex-1">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" 
                                         style="width: {{ $agent['workload_percentage'] }}%"></div>
                                </div>
                            </div>
                            <div class="w-16 text-sm text-gray-500 dark:text-gray-400 text-right">
                                {{ $agent['missions_count'] }}/3
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Single Assignment Modal -->
    <div x-show="showAssignModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAssignModal()"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="assignSingleMission()">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Assigner Mission #<span x-text="selectedMissionId"></span>
                        </h3>
                        
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedMissionAddress"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-500" x-text="selectedMissionDate"></p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Agent <span class="text-red-500">*</span>
                            </label>
                            <select x-model="selectedAgentId" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Sélectionner un agent</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent['id'] }}" 
                                            :disabled="!{{ $agent['is_available'] ? 'true' : 'false' }}">
                                        {{ $agent['name'] }} 
                                        ({{ $agent['current_missions'] }} missions)
                                        {{ !$agent['is_available'] ? '- Indisponible' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes (optionnel)
                            </label>
                            <textarea x-model="assignmentNotes" 
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Notes sur l'assignation..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Priorité
                            </label>
                            <select x-model="assignmentPriority" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="normal">Normale</option>
                                <option value="high">Élevée</option>
                                <option value="urgent">Urgente</option>
                                <option value="low">Faible</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Assigner
                        </button>
                        <button type="button" @click="closeAssignModal()" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Assignment Modal -->
    <div x-show="showBulkAssignModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeBulkAssignModal()"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form @submit.prevent="bulkAssignMissions()">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Assignation en lot
                        </h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Stratégie d'assignation <span class="text-red-500">*</span>
                            </label>
                            <select x-model="bulkAssignmentStrategy" 
                                    @change="updateBulkFormFields()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Sélectionner une stratégie</option>
                                <option value="auto">Assignation automatique (IA)</option>
                                <option value="manual">Assignation manuelle</option>
                                <option value="round_robin">Round-robin</option>
                            </select>
                        </div>

                        <div x-show="bulkAssignmentStrategy === 'manual'" class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Agent <span class="text-red-500">*</span>
                            </label>
                            <select x-model="bulkSelectedAgentId" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Sélectionner un agent</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent['id'] }}">
                                        {{ $agent['name'] }} ({{ $agent['current_missions'] }} missions)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nombre maximum de missions par agent
                            </label>
                            <input type="number" 
                                   x-model="maxMissionsPerAgent"
                                   min="1" 
                                   max="10" 
                                   value="3"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes (optionnel)
                            </label>
                            <textarea x-model="bulkAssignmentNotes" 
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Notes sur l'assignation en lot..."></textarea>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-md">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>Missions sélectionnées:</strong> {{ $unassignedMissions->count() }} missions non assignées seront traitées.
                            </p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Assigner en lot
                        </button>
                        <button type="button" @click="closeBulkAssignModal()" 
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function assignmentComponent() {
            return {
                showAssignModal: false,
                showBulkAssignModal: false,
                selectedMissionId: null,
                selectedMissionAddress: '',
                selectedMissionDate: '',
                selectedAgentId: '',
                assignmentNotes: '',
                assignmentPriority: 'normal',
                bulkAssignmentStrategy: '',
                bulkSelectedAgentId: '',
                maxMissionsPerAgent: 3,
                bulkAssignmentNotes: '',

                openAssignModal(missionId, address, date) {
                    this.selectedMissionId = missionId;
                    this.selectedMissionAddress = address;
                    this.selectedMissionDate = date;
                    this.selectedAgentId = '';
                    this.assignmentNotes = '';
                    this.assignmentPriority = 'normal';
                    this.showAssignModal = true;
                },

                closeAssignModal() {
                    this.showAssignModal = false;
                    this.selectedMissionId = null;
                },

                openBulkAssignModal() {
                    this.bulkAssignmentStrategy = '';
                    this.bulkSelectedAgentId = '';
                    this.maxMissionsPerAgent = 3;
                    this.bulkAssignmentNotes = '';
                    this.showBulkAssignModal = true;
                },

                closeBulkAssignModal() {
                    this.showBulkAssignModal = false;
                },

                updateBulkFormFields() {
                    if (this.bulkAssignmentStrategy !== 'manual') {
                        this.bulkSelectedAgentId = '';
                    }
                },

                async assignSingleMission() {
                    if (!this.selectedAgentId) {
                        alert('Veuillez sélectionner un agent');
                        return;
                    }

                    try {
                        const response = await fetch(`/mission-assignments/assign/${this.selectedMissionId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                agent_id: this.selectedAgentId,
                                notes: this.assignmentNotes,
                                priority: this.assignmentPriority
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification(data.message, 'success');
                            this.closeAssignModal();
                            window.location.reload();
                        } else {
                            this.showNotification(data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error assigning mission:', error);
                        this.showNotification('Erreur lors de l\'assignation', 'error');
                    }
                },

                async bulkAssignMissions() {
                    if (!this.bulkAssignmentStrategy) {
                        alert('Veuillez sélectionner une stratégie d\'assignation');
                        return;
                    }

                    if (this.bulkAssignmentStrategy === 'manual' && !this.bulkSelectedAgentId) {
                        alert('Veuillez sélectionner un agent pour l\'assignation manuelle');
                        return;
                    }

                    try {
                        const missionIds = @json($unassignedMissions->pluck('id'));
                        
                        const response = await fetch('/mission-assignments/bulk-assign', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                mission_ids: missionIds,
                                assignment_strategy: this.bulkAssignmentStrategy,
                                agent_id: this.bulkSelectedAgentId,
                                max_missions_per_agent: this.maxMissionsPerAgent,
                                notes: this.bulkAssignmentNotes
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification(data.message, 'success');
                            this.closeBulkAssignModal();
                            window.location.reload();
                        } else {
                            this.showNotification(data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error bulk assigning missions:', error);
                        this.showNotification('Erreur lors de l\'assignation en lot', 'error');
                    }
                },

                showNotification(message, type) {
                    // Simple notification - you can enhance this with a proper notification system
                    alert(message);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
