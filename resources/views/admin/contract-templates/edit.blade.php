@extends('layouts.app')

@section('title', 'Edit Contract Template')

@section('content')
<div class="py-12" x-data="{ previewContent: '{{ addslashes(old('content', $template->content)) }}' }">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold">Edit Contract Template</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Update the contract template details
                    </p>
                </div>

                @if($template->isSignedByAdmin())
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                        <div class="flex justify-between items-center">
                            <p>This template has been signed and cannot be edited. Please create a new version instead.</p>
                            <form action="{{ route('admin.contract-templates.create-version', $template) }}" method="POST" class="ml-4">
                                @csrf
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                                    Create New Version
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

        <!-- Edit Form -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <form action="{{ route('admin.contract-templates.update', $template) }}" method="POST" @if($template->isSignedByAdmin()) onsubmit="return false;" @endif>
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nom du modèle *
                            </label>
                            <input type="text" id="name" name="name" required
                                   class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   value="{{ old('name', $template->name) }}"
                                   placeholder="Ex: Contrat Standard d'Entrée">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Type de contrat *
                            </label>
                            <select id="type" name="type" required
                                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="checkin" {{ old('type', $template->type) == 'checkin' ? 'selected' : '' }}>Entrée</option>
                                <option value="checkout" {{ old('type', $template->type) == 'checkout' ? 'selected' : '' }}>Sortie</option>
                                <option value="general" {{ old('type', $template->type) == 'general' ? 'selected' : '' }}>Général</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                  placeholder="Description du modèle de contrat...">{{ old('description', $template->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Content -->
                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Contenu du modèle *
                        </label>
                        <textarea id="content" name="content" rows="12" required
                                  class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
                                  placeholder="Contenu du modèle avec les variables {{ variable_name }}..."
                                  x-model="previewContent">{{ old('content', $template->content) }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Utilisez des variables entre doubles accolades, ex: {{ '{{ client_name }}' }}, {{ '{{ property_address }}' }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="is_active" name="is_active" value="1" 
                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                   {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                            <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Modèle actif
                            </label>
                        </div>
                        @error('is_active')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('admin.contract-templates.show', $template) }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                            Annuler
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md @if($template->isSignedByAdmin()) opacity-50 cursor-not-allowed @endif" @if($template->isSignedByAdmin()) disabled @endif>
                            Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Aperçu du modèle
                </h3>
                
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                    <pre class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap font-mono" x-text="previewContent"></pre>
                </div>
                
                <a href="{{ route('admin.contract-templates.preview', $template) }}" 
                   target="_blank"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Aperçu complet
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Template Variables Help -->
<div class="fixed bottom-4 right-4">
    <button type="button" 
            onclick="document.getElementById('variablesHelp').classList.toggle('hidden')"
            class="bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </button>
    
    <div id="variablesHelp" class="hidden absolute bottom-16 right-0 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-4">
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Variables disponibles:</h4>
        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
            <li>{{ '{{ client_name }}' }} - Nom du client</li>
            <li>{{ '{{ property_address }}' }} - Adresse du bien</li>
            <li>{{ '{{ mission_date }}' }} - Date de la mission</li>
            <li>{{ '{{ agent_name }}' }} - Nom de l'agent</li>
            <li>{{ '{{ mission_type }}' }} - Type de mission</li>
            <li>{{ '{{ current_date }}' }} - Date actuelle</li>
        </ul>
    </div>
</div>

<script>
    // Toggle variables help on click outside
    document.addEventListener('click', function(event) {
        const help = document.getElementById('variablesHelp');
        const button = event.target.closest('button');
        
        if (!help.contains(event.target) && button !== event.target) {
            help.classList.add('hidden');
        }
    });
</script>
@endsection