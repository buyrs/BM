<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
     onclick="window.location='{{ route('bail-mobilites.show', $bailMobilite) }}'">
    
    <div class="flex justify-between items-start mb-2">
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 truncate">
            {{ $bailMobilite->tenant_name }}
        </h4>
        <span class="px-2 py-1 text-xs rounded-full 
            {{ $bailMobilite->status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
            {{ $bailMobilite->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
            {{ $bailMobilite->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
            {{ $bailMobilite->status === 'incident' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
        ">
            {{ str_replace('_', ' ', $bailMobilite->status) }}
        </span>
    </div>

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 truncate">
        {{ $bailMobilite->address }}
    </p>

    <div class="text-xs text-gray-500 dark:text-gray-500 space-y-1">
        <div class="flex justify-between">
            <span>Début:</span>
            <span class="font-medium">{{ $bailMobilite->start_date->format('d/m/Y') }}</span>
        </div>
        <div class="flex justify-between">
            <span>Fin:</span>
            <span class="font-medium">{{ $bailMobilite->end_date->format('d/m/Y') }}</span>
        </div>
    </div>

    <!-- Mission Status -->
    <div class="mt-3 pt-2 border-t border-gray-200 dark:border-gray-600">
        <div class="flex justify-between text-xs">
            <span class="text-gray-500 dark:text-gray-400">Entrée:</span>
            @if($bailMobilite->entryMission)
                <span class="px-1 rounded 
                    {{ $bailMobilite->entryMission->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                    {{ $bailMobilite->entryMission->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                    {{ $bailMobilite->entryMission->status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                ">
                    {{ str_replace('_', ' ', $bailMobilite->entryMission->status) }}
                </span>
            @else
                <span class="text-red-500 dark:text-red-400">Non créée</span>
            @endif
        </div>
        <div class="flex justify-between text-xs mt-1">
            <span class="text-gray-500 dark:text-gray-400">Sortie:</span>
            @if($bailMobilite->exitMission)
                <span class="px-1 rounded 
                    {{ $bailMobilite->exitMission->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                    {{ $bailMobilite->exitMission->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                    {{ $bailMobilite->exitMission->status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                ">
                    {{ str_replace('_', ' ', $bailMobilite->exitMission->status) }}
                </span>
            @else
                <span class="text-red-500 dark:text-red-400">Non créée</span>
            @endif
        </div>
    </div>

    <!-- Assigned Checkers -->
    @if($bailMobilite->entryMission && $bailMobilite->entryMission->agent)
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Entrée: {{ $bailMobilite->entryMission->agent->name }}
        </div>
    @endif
    @if($bailMobilite->exitMission && $bailMobilite->exitMission->agent)
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Sortie: {{ $bailMobilite->exitMission->agent->name }}
        </div>
    @endif
</div>