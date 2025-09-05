@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Créer un nouveau Bail Mobilité</h2>
                    <a href="{{ route('bail-mobilites.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        ← Retour à la liste
                    </a>
                </div>

                <form action="{{ route('bail-mobilites.store') }}" method="POST">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Tenant Information -->
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-semibold mb-4 text-blue-600 dark:text-blue-400">Informations du locataire</h3>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du locataire *</label>
                            <input type="text" name="tenant_name" required 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('tenant_name') }}">
                            @error('tenant_name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                            <input type="tel" name="tenant_phone" 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('tenant_phone') }}">
                            @error('tenant_phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" name="tenant_email" 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('tenant_email') }}">
                            @error('tenant_email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse *</label>
                        <textarea name="address" required rows="3"
                                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="md:col-span-2">
                            <h3 class="text-lg font-semibold mb-4 text-blue-600 dark:text-blue-400">Dates du bail</h3>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de début *</label>
                            <input type="date" name="start_date" required min="{{ date('Y-m-d') }}"
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('start_date') }}">
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de fin *</label>
                            <input type="date" name="end_date" required 
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('end_date') }}">
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes supplémentaires</label>
                        <textarea name="notes" rows="4"
                                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Auto-generated missions info -->
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg mb-6">
                        <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">⚠️ Information importante</h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            La création de ce Bail Mobilité générera automatiquement deux missions :
                        </p>
                        <ul class="text-sm text-blue-700 dark:text-blue-300 mt-2 list-disc list-inside">
                            <li>Une mission d'entrée programmée à la date de début</li>
                            <li>Une mission de sortie programmée à la date de fin</li>
                        </ul>
                        <p class="text-sm text-blue-700 dark:text-blue-300 mt-2">
                            Les missions seront créées avec le statut "non assigné" et pourront être assignées ultérieurement à des checkers.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('bail-mobilites.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                            Annuler
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            Créer le Bail Mobilité
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Date validation
    document.addEventListener('DOMContentLoaded', function() {
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        
        startDateInput.addEventListener('change', function() {
            if (endDateInput.value && new Date(endDateInput.value) <= new Date(this.value)) {
                endDateInput.value = '';
            }
            endDateInput.min = new Date(new Date(this.value).getTime() + 86400000).toISOString().split('T')[0];
        });
        
        endDateInput.addEventListener('change', function() {
            if (startDateInput.value && new Date(this.value) <= new Date(startDateInput.value)) {
                alert('La date de fin doit être postérieure à la date de début.');
                this.value = '';
            }
        });
    });
</script>
@endsection