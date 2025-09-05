@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Modifier le Bail Mobilité #{{ $bailMobilite->id }}</h2>
                    <a href="{{ route('bail-mobilites.show', $bailMobilite) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        ← Retour au détail
                    </a>
                </div>

                <form action="{{ route('bail-mobilites.update', $bailMobilite) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Tenant Information -->
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-semibold mb-4 text-blue-600 dark:text-blue-400">Informations du locataire</h3>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du locataire *</label>
                            <input type="text" name="tenant_name" required 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('tenant_name', $bailMobilite->tenant_name) }}">
                            @error('tenant_name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                            <input type="tel" name="tenant_phone" 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('tenant_phone', $bailMobilite->tenant_phone) }}">
                            @error('tenant_phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" name="tenant_email" 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('tenant_email', $bailMobilite->tenant_email) }}">
                            @error('tenant_email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse *</label>
                        <textarea name="address" required rows="3"
                                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address', $bailMobilite->address) }}</textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-semibold mb-4 text-blue-600 dark:text-blue-400">Dates du bail</h3>
                            <div class="bg-yellow-50 dark:bg-yellow-900 p-3 rounded-lg mb-4">
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    ⚠️ La modification des dates mettra à jour automatiquement les missions associées.
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de début *</label>
                            <input type="date" name="start_date" required 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('start_date', $bailMobilite->start_date->format('Y-m-d')) }}">
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de fin *</label>
                            <input type="date" name="end_date" required 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('end_date', $bailMobilite->end_date->format('Y-m-d')) }}">
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes supplémentaires</label>
                        <textarea name="notes" rows="4"
                                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $bailMobilite->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Current Missions Info -->
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg mb-6">
                        <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Missions actuelles</h4>
                        <div class="text-sm text-blue-700 dark:text-blue-300 space-y-2">
                            @if($bailMobilite->entryMission)
                                <div>
                                    <strong>Mission d'entrée:</strong> 
                                    {{ $bailMobilite->entryMission->scheduled_at->format('d/m/Y') }}
                                    @if($bailMobilite->entryMission->agent)
                                        - Assignée à {{ $bailMobilite->entryMission->agent->name }}
                                    @endif
                                </div>
                            @endif
                            @if($bailMobilite->exitMission)
                                <div>
                                    <strong>Mission de sortie:</strong> 
                                    {{ $bailMobilite->exitMission->scheduled_at->format('d/m/Y') }}
                                    @if($bailMobilite->exitMission->agent)
                                        - Assignée à {{ $bailMobilite->exitMission->agent->name }}
                                    @endif
                                </div>
                            @endif
                        </div>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-2">
                            Ces missions seront automatiquement mises à jour avec les nouvelles dates.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('bail-mobilites.show', $bailMobilite) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                            Annuler
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            Mettre à jour
                        </button>
                    </div>
                </form>

                <!-- Danger Zone -->
                @if($bailMobilite->status === 'assigned')
                    <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">Zone de danger</h3>
                        <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg">
                            <h4 class="font-semibold text-red-800 dark:text-red-200 mb-2">Supprimer le Bail Mobilité</h4>
                            <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                                Cette action supprimera définitivement le bail mobilité et toutes les missions associées.
                                Cette action est irréversible.
                            </p>
                            <form action="{{ route('bail-mobilites.destroy', $bailMobilite) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce Bail Mobilité ? Cette action est irréversible.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm">
                                    Supprimer définitivement
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        
        // Set minimum end date based on start date
        startDateInput.addEventListener('change', function() {
            if (this.value) {
                const minEndDate = new Date(new Date(this.value).getTime() + 86400000);
                endDateInput.min = minEndDate.toISOString().split('T')[0];
                
                if (endDateInput.value && new Date(endDateInput.value) <= new Date(this.value)) {
                    endDateInput.value = '';
                }
            }
        });
        
        // Validate end date
        endDateInput.addEventListener('change', function() {
            if (startDateInput.value && new Date(this.value) <= new Date(startDateInput.value)) {
                alert('La date de fin doit être postérieure à la date de début.');
                this.value = '';
            }
        });
        
        // Initialize min end date if start date is already set
        if (startDateInput.value) {
            const minEndDate = new Date(new Date(startDateInput.value).getTime() + 86400000);
            endDateInput.min = minEndDate.toISOString().split('T')[0];
        }
    });
</script>
@endsection