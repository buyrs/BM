@extends('layouts.app')

@section('title', 'Tableau de Bord Checker')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">
                            Tableau de Bord Checker
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Bienvenue, {{ auth()->user()->name }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Aujourd'hui: {{ now()->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg text-center">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Missions totales</h3>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-300">{{ $stats['total_missions'] ?? 0 }}</p>
            </div>
            
            <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg text-center">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Missions complétées</h3>
                <p class="text-2xl font-bold text-green-600 dark:text-green-300">{{ $stats['completed_missions'] ?? 0 }}</p>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg text-center">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Missions en cours</h3>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-300">{{ $stats['pending_missions'] ?? 0 }}</p>
            </div>
            
            <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg text-center">
                <h3 class="text-sm font-medium text-purple-800 dark:text-purple-200">Taux de complétion</h3>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-300">
                    {{ $stats['completion_rate'] ?? 0 }}%
                </p>
            </div>
        </div>

        <!-- Recent Missions -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                        Missions Récentes
                    </h2>
                    <a href="{{ route('checker.missions') }}" 
                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                        Voir toutes →
                    </a>
                </div>

                @if($recentMissions && $recentMissions->count() > 0)
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
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentMissions as $mission)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $mission->reference ?? 'BM-' . $mission->id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ Str::limit($mission->address, 30) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $mission->scheduled_at->format('d/m/Y') }}
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('missions.show', $mission) }}" 
                                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                Voir
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Aucune mission récente
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Checklist Progress -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Checklist en attente
                    </h3>
                    
                    @if($pendingChecklists && $pendingChecklists->count() > 0)
                        <div class="space-y-3">
                            @foreach($pendingChecklists as $checklist)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $checklist->mission->reference ?? 'BM-' . $checklist->mission->id }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $checklist->mission->address }}
                                        </p>
                                    </div>
                                    <a href="{{ route('checklists.show', ['mission' => $checklist->mission, 'checklist' => $checklist]) }}" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                        Vérifier
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-sm">
                            Aucun checklist en attente de vérification
                        </p>
                    @endif
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Notifications récentes
                    </h3>
                    
                    @if($recentNotifications && $recentNotifications->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentNotifications as $notification)
                                <div class="p-3 bg-blue-50 dark:bg-blue-900 rounded-lg">
                                    <p class="text-sm text-blue-800 dark:text-blue-200">
                                        {{ $notification->data['message'] ?? $notification->data['title'] ?? 'Nouvelle notification' }}
                                    </p>
                                    <p class="text-xs text-blue-600 dark:text-blue-300">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-sm">
                            Aucune notification récente
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection