<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Mission #{{ $mission->id }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('missions.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Retour
                </a>
                @can('update', $mission)
                <a href="{{ route('missions.edit', $mission) }}" 
                   class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                    Modifier
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Mission Details -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Détails de la mission
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Mission Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Type</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        @if($mission->mission_type === 'entry')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Entrée
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Sortie
                                            </span>
                                        @endif
                                    </p>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Statut</label>
                                    <p class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($mission->status === 'completed') bg-green-100 text-green-800
                                            @elseif($mission->status === 'in_progress') bg-yellow-100 text-yellow-800
                                            @elseif($mission->status === 'assigned') bg-blue-100 text-blue-800
                                            @elseif($mission->status === 'cancelled') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($mission->status) }}
                                        </span>
                                    </p>
                                </div>

                                <!-- Property Address -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Adresse</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $mission->address }}</p>
                                </div>

                                <!-- Tenant Information -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Locataire</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $mission->tenant_name }}</p>
                                    @if($mission->tenant_phone)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $mission->tenant_phone }}</p>
                                    @endif
                                    @if($mission->tenant_email)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $mission->tenant_email }}</p>
                                    @endif
                                </div>

                                <!-- Scheduled Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Date prévue</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $mission->scheduled_at?->format('d/m/Y H:i') ?? 'Non définie' }}
                                    </p>
                                </div>

                                <!-- Agent -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Agent assigné</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $mission->agent?->name ?? 'Non assigné' }}
                                    </p>
                                </div>

                                <!-- Assigned by -->
                                @if($mission->opsAssignedBy)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Assigné par</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $mission->opsAssignedBy->name }}
                                    </p>
                                </div>
                                @endif

                                <!-- Notes -->
                                @if($mission->notes)
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Notes</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $mission->notes }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Checklist Section -->
                    @if($mission->checklist)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Checklist
                            </h3>
                            
                            <div class="flex items-center justify-between mb-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($mission->checklist->status === 'validated') bg-green-100 text-green-800
                                    @elseif($mission->checklist->status === 'submitted') bg-yellow-100 text-yellow-800
                                    @elseif($mission->checklist->status === 'rejected') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($mission->checklist->status) }}
                                </span>
                                
                                <a href="{{ route('checklists.show', $mission->checklist) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    Voir la checklist
                                </a>
                            </div>

                            @if($mission->checklist->ops_validation_comments)
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Commentaires de validation</h4>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $mission->checklist->ops_validation_comments }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Contract Section -->
                    @if($mission->isBailMobiliteMission() && $contractTemplates->count() > 0)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Contrat
                            </h3>
                            
                            @if($mission->bailMobilite->signatures->count() > 0)
                                <div class="space-y-4">
                                    @foreach($mission->bailMobilite->signatures as $signature)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $signature->contractTemplate->name }}
                                                </h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    Signé le {{ $signature->tenant_signed_at?->format('d/m/Y H:i') }}
                                                </p>
                                            </div>
                                            <div class="flex space-x-2">
                                                @if($signature->contract_pdf_path)
                                                <a href="{{ route('contracts.download', $signature) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                    Télécharger PDF
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">Aucun contrat signé pour cette mission.</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Actions Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Status Actions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Actions
                            </h3>
                            
                            @if(auth()->user()->hasRole(['super-admin', 'ops']))
                                <!-- Assignment Actions -->
                                @if($mission->status === 'unassigned')
                                <form method="POST" action="{{ route('missions.assign', $mission) }}" class="mb-4">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="agent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Assigner à un agent
                                        </label>
                                        <select name="agent_id" 
                                                id="agent_id"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Sélectionner un agent</option>
                                            @foreach(\App\Models\User::role('checker')->get() as $agent)
                                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" 
                                            class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Assigner
                                    </button>
                                </form>
                                @endif

                                <!-- Status Update -->
                                @if(in_array($mission->status, ['assigned', 'in_progress']))
                                <form method="POST" action="{{ route('missions.update-status', $mission) }}" class="mb-4">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Changer le statut
                                        </label>
                                        <select name="status" 
                                                id="status"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            @if($mission->status === 'assigned')
                                                <option value="in_progress">En cours</option>
                                                <option value="cancelled">Annuler</option>
                                            @elseif($mission->status === 'in_progress')
                                                <option value="completed">Terminer</option>
                                                <option value="cancelled">Annuler</option>
                                            @endif
                                        </select>
                                    </div>
                                    <button type="submit" 
                                            class="w-full bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                        Mettre à jour
                                    </button>
                                </form>
                                @endif
                            @endif

                            @if(auth()->user()->hasRole('checker') && $mission->agent_id === auth()->id())
                                <!-- Checker Actions -->
                                @if($mission->status === 'assigned')
                                <form method="POST" action="{{ route('missions.update-status', $mission) }}" class="mb-4">
                                    @csrf
                                    <input type="hidden" name="status" value="in_progress">
                                    <button type="submit" 
                                            class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        Commencer la mission
                                    </button>
                                </form>
                                @endif

                                @if($mission->status === 'in_progress')
                                <div class="space-y-2">
                                    <a href="{{ route('checklists.create', ['mission' => $mission->id]) }}" 
                                       class="block w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center">
                                        Créer la checklist
                                    </a>
                                    
                                    <form method="POST" action="{{ route('missions.update-status', $mission) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" 
                                                class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                            Terminer la mission
                                        </button>
                                    </form>
                                </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Mission Statistics -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Informations
                            </h3>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Créée le</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $mission->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Dernière modification</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $mission->updated_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>

                                @if($mission->isBailMobiliteMission())
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Bail Mobilité</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        #{{ $mission->bail_mobilite_id }}
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
