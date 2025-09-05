@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Gestion des Baux Mobilité</h2>
                    <a href="{{ route('bail-mobilites.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        Nouveau Bail Mobilité
                    </a>
                </div>

                <!-- Filters -->
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                    <h3 class="text-lg font-semibold mb-3">Filtres</h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                            <select name="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                <option value="all" {{ ($filters['status'] ?? '') === 'all' ? 'selected' : '' }}>Tous les statuts</option>
                                <option value="assigned" {{ ($filters['status'] ?? '') === 'assigned' ? 'selected' : '' }}>Assigné</option>
                                <option value="in_progress" {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                                <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Terminé</option>
                                <option value="incident" {{ ($filters['status'] ?? '') === 'incident' ? 'selected' : '' }}>Incident</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Checker</label>
                            <select name="checker_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                <option value="">Tous les checkers</option>
                                @foreach($checkers as $checker)
                                    <option value="{{ $checker->id }}" {{ ($filters['checker_id'] ?? '') == $checker->id ? 'selected' : '' }}>
                                        {{ $checker->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date début</label>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date fin</label>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-4 flex space-x-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                Appliquer les filtres
                            </button>
                            <a href="{{ route('bail-mobilites.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Kanban Board -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Assigned Column -->
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-4">Assigné</h3>
                        <div class="space-y-3">
                            @foreach($kanbanData['assigned'] as $bailMobilite)
                                @include('bail-mobilites.partials.kanban-card', ['bailMobilite' => $bailMobilite])
                            @endforeach
                            @if(count($kanbanData['assigned']) === 0)
                                <p class="text-gray-500 dark:text-gray-400 text-center">Aucun bail assigné</p>
                            @endif
                        </div>
                    </div>

                    <!-- In Progress Column -->
                    <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-4">En cours</h3>
                        <div class="space-y-3">
                            @foreach($kanbanData['in_progress'] as $bailMobilite)
                                @include('bail-mobilites.partials.kanban-card', ['bailMobilite' => $bailMobilite])
                            @endforeach
                            @if(count($kanbanData['in_progress']) === 0)
                                <p class="text-gray-500 dark:text-gray-400 text-center">Aucun bail en cours</p>
                            @endif
                        </div>
                    </div>

                    <!-- Completed Column -->
                    <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-4">Terminé</h3>
                        <div class="space-y-3">
                            @foreach($kanbanData['completed'] as $bailMobilite)
                                @include('bail-mobilites.partials.kanban-card', ['bailMobilite' => $bailMobilite])
                            @endforeach
                            @if(count($kanbanData['completed']) === 0)
                                <p class="text-gray-500 dark:text-gray-400 text-center">Aucun bail terminé</p>
                            @endif
                        </div>
                    </div>

                    <!-- Incident Column -->
                    <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-4">Incident</h3>
                        <div class="space-y-3">
                            @foreach($kanbanData['incident'] as $bailMobilite)
                                @include('bail-mobilites.partials.kanban-card', ['bailMobilite' => $bailMobilite])
                            @endforeach
                            @if(count($kanbanData['incident']) === 0)
                                <p class="text-gray-500 dark:text-gray-400 text-center">Aucun incident</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg text-center">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Total</h3>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-300">{{ count($kanbanData['assigned']) + count($kanbanData['in_progress']) + count($kanbanData['completed']) + count($kanbanData['incident']) }}</p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-800 p-4 rounded-lg text-center">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Assigné</h3>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-300">{{ count($kanbanData['assigned']) }}</p>
                    </div>
                    <div class="bg-yellow-100 dark:bg-yellow-800 p-4 rounded-lg text-center">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">En cours</h3>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-300">{{ count($kanbanData['in_progress']) }}</p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-800 p-4 rounded-lg text-center">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Incidents</h3>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-300">{{ count($kanbanData['incident']) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection