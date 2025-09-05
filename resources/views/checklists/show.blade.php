@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">
                            {{ $mission->mission_type === 'checkin' ? 'Checklist d\'Entrée' : 'Checklist de Sortie' }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            Mission: {{ $mission->reference ?? 'BM-' . $mission->id }} - {{ $mission->address }}
                        </p>
                        @if($mission->agent)
                        <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">
                            Agent: {{ $mission->agent->name }}
                        </p>
                        @endif
                        <p class="text-sm text-gray-500 dark:text-gray-500">
                            Date: {{ $mission->scheduled_at->format('d/m/Y') }}
                            @if($mission->scheduled_time)
                                à {{ substr($mission->scheduled_time, 0, 5) }}
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Statut:
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if($checklist->status === 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($checklist->status === 'submitted') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                            @elseif($checklist->status === 'validated') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($checklist->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                            {{ ucfirst($checklist->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checklist Summary -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Résumé du Checklist</h2>
                
                <!-- General Information -->
                @if($checklist->general_info)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Informations générales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($checklist->general_info as $key => $value)
                                @if(!empty($value))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                        </label>
                                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $value }}</p>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Rooms -->
                @if($checklist->rooms && count($checklist->rooms) > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Pièces</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($checklist->rooms as $roomName => $roomData)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ $roomName }}</h4>
                                    @if($roomData && is_array($roomData))
                                        @foreach($roomData as $key => $value)
                                            @if(!empty($value) && $key !== 'type')
                                                <div class="text-sm">
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                                    </span>
                                                    <span class="text-gray-900 dark:text-gray-100">{{ $value }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Utilities -->
                @if($checklist->utilities)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Équipements</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($checklist->utilities as $key => $value)
                                @if(!empty($value))
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                        </label>
                                        <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $value }}</p>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Checklist Items -->
                @if($checklist->items->count() > 0)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Éléments vérifiés</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Catégorie
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Élément
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            État
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Commentaire
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Photos
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($checklist->items as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ ucfirst($item->category) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $item->item_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="px-2 py-1 rounded-full text-xs 
                                                    @if($item->condition === 'good') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                    @elseif($item->condition === 'fair') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                    @elseif($item->condition === 'poor') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 @endif">
                                                    {{ $item->condition === 'good' ? 'Bon' : ($item->condition === 'fair' ? 'Moyen' : ($item->condition === 'poor' ? 'Mauvais' : 'Non spécifié')) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                                {{ $item->comment ?? 'Aucun commentaire' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                @if($item->photos->count() > 0)
                                                    <div class="flex space-x-2">
                                                        @foreach($item->photos->take(3) as $photo)
                                                            <img src="{{ Storage::url($photo->photo_path) }}" 
                                                                 alt="Photo" 
                                                                 class="w-12 h-12 rounded object-cover cursor-pointer"
                                                                 onclick="openPhotoModal('{{ Storage::url($photo->photo_path) }}')">
                                                        @endforeach
                                                        @if($item->photos->count() > 3)
                                                            <span class="text-xs text-gray-500 dark:text-gray-400 self-center">
                                                                +{{ $item->photos->count() - 3 }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">Aucune photo</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Signatures -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($checklist->tenant_signature)
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Signature du locataire</h4>
                            <img src="{{ $checklist->tenant_signature }}" 
                                 alt="Signature locataire" 
                                 class="w-full h-32 object-contain border border-gray-200 dark:border-gray-600 rounded">
                        </div>
                    @endif
                    
                    @if($checklist->agent_signature)
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Signature de l'agent</h4>
                            <img src="{{ $checklist->agent_signature }}" 
                                 alt="Signature agent" 
                                 class="w-full h-32 object-contain border border-gray-200 dark:border-gray-600 rounded">
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex flex-wrap gap-4">
                    <!-- Generate PDF -->
                    <a 
                        href="{{ route('pdf.checklist', $checklist) }}"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Générer PDF
                    </a>

                    <!-- Back to Mission -->
                    <a 
                        href="{{ route('missions.show', $mission) }}"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour à la mission
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Modal -->
<div id="photoModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 max-w-4xl max-h-full">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Photo</h3>
            <button onclick="closePhotoModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <img id="modalPhoto" src="" alt="" class="max-w-full max-h-96 object-contain">
    </div>
</div>

<script>
    function openPhotoModal(photoUrl) {
        document.getElementById('modalPhoto').src = photoUrl;
        document.getElementById('photoModal').classList.remove('hidden');
    }
    
    function closePhotoModal() {
        document.getElementById('photoModal').classList.add('hidden');
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePhotoModal();
        }
    });
</script>
@endsection