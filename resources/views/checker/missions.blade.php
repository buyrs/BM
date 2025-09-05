@extends('layouts.app')

@section('title', 'Missions Checker')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">
                            Mes Missions
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Liste des missions assignées
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $missions->total() }} mission(s)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('checker.missions') }}">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Statut
                            </label>
                            <select id="status" name="status" 
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Tous les statuts</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En cours</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Terminé</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                            </select>
                        </div>

                        <!-- Date Filter -->
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date
                            </label>
                            <select id="date" name="date" 
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Toutes les dates</option>
                                <option value="today" {{ request('date') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                                <option value="tomorrow" {{ request('date') == 'tomorrow' ? 'selected' : '' }}>Demain</option>
                                <option value="this_week" {{ request('date') == 'this_week' ? 'selected' : '' }}>Cette semaine</option>
                                <option value="next_week" {{ request('date') == 'next_week' ? 'selected' : '' }}>Semaine prochaine</option>
                            </select>
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Type
                            </label>
                            <select id="type" name="type" 
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Tous les types</option>
                                <option value="checkin" {{ request('type') == 'checkin' ? 'selected' : '' }}>Entrée</option>
                                <option value="checkout" {{ request('type') == 'checkout' ? 'selected' : '' }}>Sortie</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                Filtrer
                            </button>
                            <a href="{{ route('checker.missions') }}" 
                               class="ml-2 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                                Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Missions List -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                @if($missions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Référence
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Adresse
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Date/Heure
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Checklist
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($missions as $mission)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $mission->reference ?? 'BM-' . $mission->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ Str::limit($mission->address, 25) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $mission->scheduled_at->format('d/m/Y') }}
                                            @if($mission->scheduled_time)
                                                <br>
                                                <span class="text-xs">{{ substr($mission->scheduled_time, 0, 5) }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $mission->mission_type === 'checkin' ? 'Entrée' : 'Sortie' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium 
                                                @if($mission->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                @elseif($mission->status === 'in_progress') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @elseif($mission->status === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                                {{ ucfirst($mission->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($mission->checklist)
                                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                                    @if($mission->checklist->status === 'validated') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                    @elseif($mission->checklist->status === 'submitted') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                    @elseif($mission->checklist->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                                    {{ ucfirst($mission->checklist->status) }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500 text-xs">Non créé</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('missions.show', $mission) }}" 
                                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                                Voir
                                            </a>
                                            @if($mission->status === 'in_progress' && !$mission->checklist)
                                                <a href="{{ route('checklists.create', $mission) }}" 
                                                   class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                    Créer checklist
                                                </a>
                                            @endif
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
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucune mission</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Aucune mission ne correspond à vos critères de recherche.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js for interactive filtering -->
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('missionFilters', () => ({
            filters: {
                status: '{{ request('status') }}',
                date: '{{ request('date') }}',
                type: '{{ request('type') }}'
            },
            
            updateFilter(filter, value) {
                this.filters[filter] = value;
                this.submitForm();
            },
            
            submitForm() {
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = '{{ route('checker.missions') }}';
                
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        form.appendChild(input);
                    }
                });
                
                document.body.appendChild(form);
                form.submit();
            }
        }));
    });
</script>
@endsection