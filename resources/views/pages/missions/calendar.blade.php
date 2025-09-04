<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Planning des Missions') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('blade-missions.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Liste
                </a>
                @can('create', App\Models\Mission::class)
                <a href="{{ route('blade-missions.create') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Nouvelle Mission
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Calendar Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('blade-missions.calendar') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Month/Year Navigation -->
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('blade-missions.calendar', array_merge($filters, ['month' => $month == 1 ? 12 : $month - 1, 'year' => $month == 1 ? $year - 1 : $year])) }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-3 rounded">
                                ‹
                            </a>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ \Carbon\Carbon::create($year, $month, 1)->locale('fr')->isoFormat('MMMM YYYY') }}
                            </h3>
                            <a href="{{ route('blade-missions.calendar', array_merge($filters, ['month' => $month == 12 ? 1 : $month + 1, 'year' => $month == 12 ? $year + 1 : $year])) }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-3 rounded">
                                ›
                            </a>
                        </div>

                        <!-- Agent Filter -->
                        @if(auth()->user()->hasRole(['super-admin', 'ops']))
                        <div>
                            <label for="agent" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Agent</label>
                            <select name="agent" 
                                    id="agent"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les agents</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ ($filters['agent'] ?? '') == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Statut</label>
                            <select name="status" 
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Tous les statuts</option>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Quick Navigation -->
                        <div class="flex items-end space-x-2">
                            <a href="{{ route('blade-missions.calendar') }}" 
                               class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Aujourd'hui
                            </a>
                            <button type="submit" 
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg" x-data="calendarComponent()">
                <div class="p-6">
                    <!-- Calendar Header -->
                    <div class="grid grid-cols-7 gap-1 mb-4">
                        <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">Lun</div>
                        <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">Mar</div>
                        <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">Mer</div>
                        <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">Jeu</div>
                        <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">Ven</div>
                        <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">Sam</div>
                        <div class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 py-2">Dim</div>
                    </div>

                    <!-- Calendar Body -->
                    <div class="grid grid-cols-7 gap-1">
                        @php
                            $firstDay = \Carbon\Carbon::create($year, $month, 1);
                            $startOfWeek = $firstDay->startOfWeek(\Carbon\Carbon::MONDAY);
                            $currentDate = $startOfWeek->copy();
                        @endphp

                        @for($week = 0; $week < 6; $week++)
                            @for($day = 0; $day < 7; $day++)
                                @php
                                    $dayKey = $currentDate->format('Y-m-d');
                                    $dayData = $calendarData[$dayKey] ?? null;
                                    $isCurrentMonth = $currentDate->month == $month;
                                    $isToday = $currentDate->isToday();
                                    $isWeekend = $currentDate->isWeekend();
                                    $isPast = $currentDate->isPast();
                                @endphp

                                <div class="min-h-[120px] border border-gray-200 dark:border-gray-600 p-2
                                    {{ !$isCurrentMonth ? 'bg-gray-50 dark:bg-gray-700' : '' }}
                                    {{ $isToday ? 'bg-blue-50 dark:bg-blue-900' : '' }}
                                    {{ $isWeekend ? 'bg-gray-50 dark:bg-gray-700' : '' }}">
                                    
                                    <!-- Date Number -->
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium
                                            {{ !$isCurrentMonth ? 'text-gray-400' : '' }}
                                            {{ $isToday ? 'text-blue-600 dark:text-blue-400' : '' }}
                                            {{ $isPast && $isCurrentMonth ? 'text-gray-500' : 'text-gray-900 dark:text-gray-100' }}">
                                            {{ $currentDate->day }}
                                        </span>
                                        @if($isToday)
                                            <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">Aujourd'hui</span>
                                        @endif
                                    </div>

                                    <!-- Missions for this day -->
                                    @if($dayData && count($dayData['missions']) > 0)
                                        <div class="space-y-1">
                                            @foreach(array_slice($dayData['missions'], 0, 3) as $mission)
                                                <div class="text-xs p-1 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600
                                                    @if($mission['status'] === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                    @elseif($mission['status'] === 'in_progress') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                    @elseif($mission['status'] === 'assigned') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                    @elseif($mission['status'] === 'cancelled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                    @else bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200
                                                    @endif"
                                                    @click="openMissionModal({{ json_encode($mission) }})"
                                                    draggable="true"
                                                    @dragstart="startDrag($event, {{ json_encode($mission) }})">
                                                    
                                                    <div class="font-medium truncate">{{ $mission['address'] }}</div>
                                                    <div class="text-xs opacity-75">{{ $mission['scheduled_time'] }} - {{ $mission['agent_name'] }}</div>
                                                    @if($mission['is_bail_mobilite'])
                                                        <div class="text-xs opacity-75">Bail Mobilité</div>
                                                    @endif
                                                </div>
                                            @endforeach

                                            @if(count($dayData['missions']) > 3)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                                    +{{ count($dayData['missions']) - 3 }} autres
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Drop zone for drag and drop -->
                                    <div class="min-h-[20px] w-full border-2 border-dashed border-transparent rounded
                                        hover:border-blue-300 dark:hover:border-blue-600"
                                        @dragover.prevent
                                        @drop.prevent="dropMission($event, '{{ $dayKey }}')">
                                    </div>
                                </div>

                                @php $currentDate->addDay(); @endphp
                            @endfor
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mission Modal -->
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Mission #<span x-text="selectedMission?.id"></span>
                            </h3>
                            
                            <div x-show="selectedMission" class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Adresse</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100" x-text="selectedMission?.address"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Locataire</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100" x-text="selectedMission?.tenant_name"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Agent</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100" x-text="selectedMission?.agent_name"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Statut</label>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': selectedMission?.status === 'completed',
                                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': selectedMission?.status === 'in_progress',
                                              'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': selectedMission?.status === 'assigned',
                                              'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': selectedMission?.status === 'cancelled',
                                              'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200': !['completed', 'in_progress', 'assigned', 'cancelled'].includes(selectedMission?.status)
                                          }"
                                          x-text="selectedMission?.status"></span>
                                </div>

                                <!-- Schedule Update Form -->
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Modifier le planning</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="date" 
                                               x-model="newScheduleDate"
                                               class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <input type="time" 
                                               x-model="newScheduleTime"
                                               class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <button @click="updateSchedule()" 
                                            class="mt-2 w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                                        Mettre à jour
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <a :href="selectedMission?.url" 
                       class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Voir détails
                    </a>
                    <button @click="closeModal()" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function calendarComponent() {
            return {
                showModal: false,
                selectedMission: null,
                newScheduleDate: '',
                newScheduleTime: '',
                draggedMission: null,

                openMissionModal(mission) {
                    this.selectedMission = mission;
                    this.newScheduleDate = new Date(mission.scheduled_at).toISOString().split('T')[0];
                    this.newScheduleTime = mission.scheduled_time;
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                    this.selectedMission = null;
                },

                startDrag(event, mission) {
                    this.draggedMission = mission;
                    event.dataTransfer.effectAllowed = 'move';
                },

                dropMission(event, targetDate) {
                    if (!this.draggedMission) return;

                    const newScheduleDate = targetDate;
                    const newScheduleTime = this.draggedMission.scheduled_time;

                    this.updateMissionSchedule(this.draggedMission.id, newScheduleDate, newScheduleTime);
                    this.draggedMission = null;
                },

                async updateSchedule() {
                    if (!this.selectedMission) return;

                    await this.updateMissionSchedule(
                        this.selectedMission.id,
                        this.newScheduleDate,
                        this.newScheduleTime
                    );
                },

                async updateMissionSchedule(missionId, date, time) {
                    try {
                        const response = await fetch(`/blade-missions/${missionId}/update-schedule`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                scheduled_at: date,
                                scheduled_time: time
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            // Show success message
                            this.showNotification(data.message, 'success');
                            // Reload the page to reflect changes
                            window.location.reload();
                        } else {
                            // Show error message
                            this.showNotification(data.message, 'error');
                            if (data.conflicts) {
                                console.log('Conflicts:', data.conflicts);
                            }
                        }
                    } catch (error) {
                        console.error('Error updating schedule:', error);
                        this.showNotification('Erreur lors de la mise à jour du planning', 'error');
                    }
                },

                showNotification(message, type) {
                    // Simple notification - you can enhance this with a proper notification system
                    alert(message);
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
