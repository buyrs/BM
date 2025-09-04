<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Gestion des Missions') }}
            </h2>
            @can('create', App\Models\Mission::class)
                <a href="{{ route('missions.create') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Nouvelle Mission
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('missions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recherche</label>
                            <input type="text" 
                                   name="search" 
                                   id="search"
                                   value="{{ $filters['search'] ?? '' }}"
                                   placeholder="Adresse, locataire..."
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

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
                            <a href="{{ route('missions.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Missions List -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($missions->count() > 0)
                        <!-- Bulk Actions -->
                        @if(auth()->user()->hasRole(['super-admin', 'ops']))
                        <div class="mb-4" x-data="bulkActions()">
                            <form method="POST" action="{{ route('missions.bulk-update') }}" @submit="confirmBulkAction">
                                @csrf
                                <div class="flex items-center space-x-4">
                                    <select name="action" 
                                            x-model="action"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Action en lot</option>
                                        <option value="assign">Assigner</option>
                                        <option value="status_update">Changer statut</option>
                                        <option value="delete">Supprimer</option>
                                    </select>

                                    <select name="agent_id" 
                                            x-show="action === 'assign'"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Sélectionner un agent</option>
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                        @endforeach
                                    </select>

                                    <select name="status" 
                                            x-show="action === 'status_update'"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Nouveau statut</option>
                                        @foreach($statuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>

                                    <button type="submit" 
                                            x-show="action && selectedMissions.length > 0"
                                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                        Appliquer (<span x-text="selectedMissions.length"></span>)
                                    </button>
                                </div>
                            </form>
                        </div>
                        @endif

                        <!-- Missions Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        @if(auth()->user()->hasRole(['super-admin', 'ops']))
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            <input type="checkbox" 
                                                   @change="toggleAll($event)"
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        </th>
                                        @endif
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
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($missions as $mission)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        @if(auth()->user()->hasRole(['super-admin', 'ops']))
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" 
                                                   value="{{ $mission->id }}"
                                                   @change="toggleMission($event)"
                                                   class="mission-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        </td>
                                        @endif
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $mission->address }}
                                                    </div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $mission->tenant_name }}
                                                    </div>
                                                    @if($mission->isBailMobiliteMission())
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
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
                                                @if($mission->status === 'completed') bg-green-100 text-green-800
                                                @elseif($mission->status === 'in_progress') bg-yellow-100 text-yellow-800
                                                @elseif($mission->status === 'assigned') bg-blue-100 text-blue-800
                                                @elseif($mission->status === 'cancelled') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ $statuses[$mission->status] ?? $mission->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $mission->scheduled_at?->format('d/m/Y H:i') ?? 'Non définie' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('missions.show', $mission) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    Voir
                                                </a>
                                                @can('update', $mission)
                                                <a href="{{ route('missions.edit', $mission) }}" 
                                                   class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300">
                                                    Modifier
                                                </a>
                                                @endcan
                                                @can('delete', $mission)
                                                <form method="POST" action="{{ route('missions.destroy', $mission) }}" 
                                                      class="inline"
                                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette mission ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                        Supprimer
                                                    </button>
                                                </form>
                                                @endcan
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
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Commencez par créer une nouvelle mission.</p>
                            @can('create', App\Models\Mission::class)
                            <div class="mt-6">
                                <a href="{{ route('missions.create') }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    Nouvelle Mission
                                </a>
                            </div>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function bulkActions() {
            return {
                selectedMissions: [],
                action: '',
                
                toggleAll(event) {
                    const checkboxes = document.querySelectorAll('.mission-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = event.target.checked;
                        this.toggleMission({ target: checkbox });
                    });
                },
                
                toggleMission(event) {
                    const missionId = parseInt(event.target.value);
                    if (event.target.checked) {
                        if (!this.selectedMissions.includes(missionId)) {
                            this.selectedMissions.push(missionId);
                        }
                    } else {
                        this.selectedMissions = this.selectedMissions.filter(id => id !== missionId);
                    }
                },
                
                confirmBulkAction(event) {
                    if (this.selectedMissions.length === 0) {
                        event.preventDefault();
                        alert('Veuillez sélectionner au moins une mission.');
                        return false;
                    }
                    
                    const action = this.action;
                    let message = '';
                    
                    switch (action) {
                        case 'assign':
                            message = `Assigner ${this.selectedMissions.length} mission(s) ?`;
                            break;
                        case 'status_update':
                            message = `Changer le statut de ${this.selectedMissions.length} mission(s) ?`;
                            break;
                        case 'delete':
                            message = `Supprimer définitivement ${this.selectedMissions.length} mission(s) ?`;
                            break;
                    }
                    
                    if (!confirm(message)) {
                        event.preventDefault();
                        return false;
                    }
                    
                    // Add selected mission IDs to form
                    this.selectedMissions.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'mission_ids[]';
                        input.value = id;
                        event.target.appendChild(input);
                    });
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
