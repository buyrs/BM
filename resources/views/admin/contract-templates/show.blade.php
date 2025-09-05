@extends('layouts.app')

@section('title', $contractTemplate->name)

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ $contractTemplate->name }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Modèle de contrat {{ $contractTemplate->type === 'checkin' ? 'd\'Entrée' : ($contractTemplate->type === 'checkout' ? 'de Sortie' : 'Général') }}
                        </p>
                        <div class="flex items-center mt-2 space-x-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                @if($contractTemplate->is_active) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                                {{ $contractTemplate->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Créé le {{ $contractTemplate->created_at->format('d/m/Y') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.contract-templates.edit', $contractTemplate) }}" 
                           class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md">
                            Modifier
                        </a>
                        <a href="{{ route('admin.contract-templates.index') }}" 
                           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                            Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Info Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Informations du modèle
                    </h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nom
                            </label>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $contractTemplate->name }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Type
                            </label>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">
                                {{ ucfirst($contractTemplate->type) }}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Statut
                            </label>
                            <p class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($contractTemplate->is_active) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                                    {{ $contractTemplate->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </p>
                        </div>
                        
                        @if($contractTemplate->description)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Description
                            </label>
                            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $contractTemplate->description }}</p>
                        </div>
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Créé le
                            </label>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">
                                {{ $contractTemplate->created_at->format('d/m/Y à H:i') }}
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Modifié le
                            </label>
                            <p class="mt-1 text-gray-500 dark:text-gray-400">
                                {{ $contractTemplate->updated_at->format('d/m/Y à H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Content -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Contenu du modèle
                        </h3>
                        <button onclick="copyTemplateContent()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                            Copier
                        </button>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <pre class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap font-mono">{{ $contractTemplate->content }}</pre>
                    </div>
                    
                    <!-- Preview Button -->
                    <div class="mt-4">
                        <a href="{{ route('admin.contract-templates.preview', $contractTemplate) }}" 
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Aperçu
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Utilisation du modèle
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg text-center">
                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Missions totales</h4>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-300">{{ $usageStats['total_missions'] ?? 0 }}</p>
                    </div>
                    
                    <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg text-center">
                        <h4 class="text-sm font-medium text-green-800 dark:text-green-200">Missions ce mois</h4>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-300">{{ $usageStats['monthly_missions'] ?? 0 }}</p>
                    </div>
                    
                    <div class="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg text-center">
                        <h4 class="text-sm font-medium text-purple-800 dark:text-purple-200">Dernière utilisation</h4>
                        <p class="text-lg font-bold text-purple-600 dark:text-purple-300">
                            @if($usageStats['last_used'])
                                {{ $usageStats['last_used']->format('d/m/Y') }}
                            @else
                                Jamais
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyTemplateContent() {
        const content = `{{ $contractTemplate->content }}`;
        navigator.clipboard.writeText(content).then(() => {
            alert('Contenu copié dans le presse-papiers');
        }).catch(err => {
            console.error('Erreur lors de la copie:', err);
        });
    }
</script>
@endsection