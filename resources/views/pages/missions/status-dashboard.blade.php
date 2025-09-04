<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Suivi des Statuts') }}
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
                @if(auth()->user()->hasRole(['super-admin', 'ops']))
                <a href="{{ route('mission-assignments.index') }}" 
                   class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Assignations
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="statusTrackingComponent()">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-6 gap-6 mb-6">
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
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Non assignées</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $statusStats['unassigned'] }}</p>
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
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Assignées</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $statusStats['assigned'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">En cours</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $statusStats['in_progress'] }}</p>
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
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Terminées</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $statusStats['completed'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-900 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Annulées</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $statusStats['cancelled'] }}</p>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Taux de réussite</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $statusStats['completion_rate'] }}%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Metrics -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Today's Progress -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Aujourd'hui
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Total</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $progressMetrics['today']['total'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Terminées</span>
                                <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $progressMetrics['today']['completed'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">En cours</span>
                                <span class="text-sm font-medium text-yellow-600 dark:text-yellow-400">{{ $progressMetrics['today']['in_progress'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Assignées</span>
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $progressMetrics['today']['assigned'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- This Week's Progress -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Cette semaine
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Total</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $progressMetrics['this_week']['total'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Terminées</span>
                                <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $progressMetrics['this_week']['completed'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Taux de réussite</span>
                                <span class="text-sm font-medium text-purple-600 dark:text-purple-400">{{ $progressMetrics['this_week']['completion_rate'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Performance
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Temps moyen</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $progressMetrics['average_completion_time'] }}min</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">En retard</span>
                                <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $progressMetrics['overdue_missions'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Échéances proches</span>
                                <span class="text-sm font-medium text-orange-600 dark:text-orange-400">{{ $progressMetrics['upcoming_deadlines'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('mission-status.dashboard') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                            <select name="status" 
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les statuts</option>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Agent Filter -->
                        @if(auth()->user()->hasRole(['super-admin', 'ops']))
                        <div>
                            <label for="agent" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Agent</label>
                            <select name="agent" 
                                    id="agent"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les agents</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ ($filters['agent'] ?? '') == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Priority Filter -->
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Priorité</label>
                            <select name="priority" 
                                    id="priority"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Toutes les priorités</option>
                                @foreach($priorities as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['priority'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
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
                            <a href="{{ route('mission-status.dashboard') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Real-time Status Updates -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Mises à jour en temps réel
                        </h3>
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">En direct</span>
                            </div>
                            <button @click="refreshStatusUpdates()" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-1 px-3 rounded">
                                Actualiser
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-2 max-h-64 overflow-y-auto" id="status-updates">
                        @foreach($recentChanges as $change)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($change['status'] === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($change['status'] === 'in_progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif($change['status'] === 'assigned') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($change['status'] === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200
                                    @endif">
                                    {{ ucfirst($change['status']) }}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        Mission #{{ $change['id'] }} - {{ $change['address'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $change['agent_name'] }} • {{ $change['updated_at'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Missions List -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Missions ({{ $missions->total() }})
                    </h3>

                    @if($missions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Mission
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Agent
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Date prévue
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Dernière mise à jour
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($missions as $mission)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        #{{ $mission->id }} - {{ $mission->address }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $mission->tenant_name }}
                                                    </div>
                                                    @if($mission->isBailMobiliteMission())
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                            Bail Mobilité
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $mission->agent?->name ?? 'Non assigné' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($mission->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($mission->status === 'in_progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @elseif($mission->status === 'assigned') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @elseif($mission->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200
                                                @endif">
                                                {{ ucfirst($mission->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $mission->scheduled_at?->format('d/m/Y H:i') ?? 'Non définie' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $mission->status_updated_at?->format('d/m/Y H:i') ?? 'Jamais' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('blade-missions.show', $mission) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    Voir
                                                </a>
                                                @if(auth()->user()->hasRole(['super-admin', 'ops']) || $mission->agent_id === auth()->id())
                                                <button @click="openStatusModal({{ $mission->id }}, '{{ $mission->status }}', '{{ $mission->address }}')"
                                                        class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300">
                                                    Modifier
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $missions->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucune mission</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aucune mission ne correspond aux critères sélectionnés.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div x-show="showStatusModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeStatusModal()"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="updateMissionStatus()">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Modifier le statut - Mission #<span x-text="selectedMissionId"></span>
                        </h3>
                        
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedMissionAddress"></p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nouveau statut <span class="text-red-500">*</span>
                            </label>
                            <select x-model="newStatus" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Sélectionner un statut</option>
                                <option value="assigned">Assigné</option>
                                <option value="in_progress">En cours</option>
                                <option value="completed">Terminé</option>
                                <option value="cancelled">Annulé</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes (optionnel)
                            </label>
                            <textarea x-model="statusNotes" 
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Notes sur le changement de statut..."></textarea>
                        </div>

                        <div x-show="newStatus === 'completed'" class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Notes de fin de mission
                            </label>
                            <textarea x-model="completionNotes" 
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      placeholder="Notes sur la fin de mission..."></textarea>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Mettre à jour
                        </button>
                        <button type="button" @click="closeStatusModal()" 
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
        function statusTrackingComponent() {
            return {
                showStatusModal: false,
                selectedMissionId: null,
                selectedMissionAddress: '',
                currentStatus: '',
                newStatus: '',
                statusNotes: '',
                completionNotes: '',
                lastUpdate: new Date().toISOString(),

                openStatusModal(missionId, currentStatus, address) {
                    this.selectedMissionId = missionId;
                    this.currentStatus = currentStatus;
                    this.selectedMissionAddress = address;
                    this.newStatus = '';
                    this.statusNotes = '';
                    this.completionNotes = '';
                    this.showStatusModal = true;
                },

                closeStatusModal() {
                    this.showStatusModal = false;
                    this.selectedMissionId = null;
                },

                async updateMissionStatus() {
                    if (!this.newStatus) {
                        alert('Veuillez sélectionner un nouveau statut');
                        return;
                    }

                    try {
                        const response = await fetch(`/mission-status/update/${this.selectedMissionId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                status: this.newStatus,
                                notes: this.statusNotes,
                                completion_notes: this.completionNotes
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification(data.message, 'success');
                            this.closeStatusModal();
                            window.location.reload();
                        } else {
                            this.showNotification(data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error updating mission status:', error);
                        this.showNotification('Erreur lors de la mise à jour du statut', 'error');
                    }
                },

                async refreshStatusUpdates() {
                    try {
                        const response = await fetch(`/mission-status/updates?last_update=${this.lastUpdate}`);
                        const data = await response.json();

                        if (data.success) {
                            this.updateStatusDisplay(data.updated_missions);
                            this.lastUpdate = data.last_update;
                        }
                    } catch (error) {
                        console.error('Error refreshing status updates:', error);
                    }
                },

                updateStatusDisplay(updatedMissions) {
                    const container = document.getElementById('status-updates');
                    if (!container) return;

                    // Add new updates to the top
                    updatedMissions.forEach(mission => {
                        const statusClass = this.getStatusClass(mission.status);
                        const statusText = this.getStatusText(mission.status);
                        
                        const updateElement = document.createElement('div');
                        updateElement.className = 'flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg mb-2';
                        updateElement.innerHTML = `
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                                    ${statusText}
                                </span>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        Mission #${mission.id} - ${mission.address}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        ${mission.agent_name} • ${mission.status_updated_at}
                                    </p>
                                </div>
                            </div>
                        `;
                        
                        container.insertBefore(updateElement, container.firstChild);
                    });

                    // Keep only the last 10 updates
                    while (container.children.length > 10) {
                        container.removeChild(container.lastChild);
                    }
                },

                getStatusClass(status) {
                    const classes = {
                        'completed': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                        'in_progress': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                        'assigned': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                        'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                        'unassigned': 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200'
                    };
                    return classes[status] || classes['unassigned'];
                },

                getStatusText(status) {
                    const texts = {
                        'completed': 'Terminé',
                        'in_progress': 'En cours',
                        'assigned': 'Assigné',
                        'cancelled': 'Annulé',
                        'unassigned': 'Non assigné'
                    };
                    return texts[status] || status;
                },

                showNotification(message, type) {
                    // Simple notification - you can enhance this with a proper notification system
                    alert(message);
                },

                // Auto-refresh every 30 seconds
                init() {
                    setInterval(() => {
                        this.refreshStatusUpdates();
                    }, 30000);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
