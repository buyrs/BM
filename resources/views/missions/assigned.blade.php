@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100" x-data="missionList()">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">My Assigned Missions</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`${filteredMissions.length} mission(s)`"></span>
                        <button @click="toggleFilters()" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                            <span x-show="!showFilters">Show Filters</span>
                            <span x-show="showFilters">Hide Filters</span>
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div x-show="showFilters" x-transition class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <select x-model="filters.status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-600 rounded-md shadow-sm">
                                <option value="">All Statuses</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mission Type</label>
                            <select x-model="filters.type" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-600 rounded-md shadow-sm">
                                <option value="">All Types</option>
                                <option value="checkin">Check-in</option>
                                <option value="checkout">Check-out</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                            <select x-model="sortBy" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-600 rounded-md shadow-sm">
                                <option value="scheduled_at">Date (Newest)</option>
                                <option value="scheduled_at_asc">Date (Oldest)</option>
                                <option value="status">Status</option>
                                <option value="type">Type</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button @click="clearFilters()" class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                            Clear Filters
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer" @click="sortBy = sortBy === 'type' ? 'type_desc' : 'type'">
                                    Mission
                                    <span x-show="sortBy === 'type'" class="ml-1">↑</span>
                                    <span x-show="sortBy === 'type_desc'" class="ml-1">↓</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer" @click="sortBy = sortBy === 'scheduled_at' ? 'scheduled_at_asc' : 'scheduled_at'">
                                    Scheduled
                                    <span x-show="sortBy === 'scheduled_at'" class="ml-1">↑</span>
                                    <span x-show="sortBy === 'scheduled_at_asc'" class="ml-1">↓</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer" @click="sortBy = sortBy === 'status' ? 'status_desc' : 'status'">
                                    Status
                                    <span x-show="sortBy === 'status'" class="ml-1">↑</span>
                                    <span x-show="sortBy === 'status_desc'" class="ml-1">↓</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="mission in filteredMissions" :key="mission.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="`${mission.type} - ${mission.address}`"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="mission.tenant_name"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100" x-text="formatDate(mission.scheduled_at)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                              :class="{
                                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': mission.status === 'assigned',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': mission.status === 'in_progress',
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': mission.status === 'completed'
                                              }"
                                              x-text="mission.status.replace('_', ' ')"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a :href="`/missions/${mission.id}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">View</a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    
                    <!-- Empty State -->
                    <div x-show="filteredMissions.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <span x-show="hasActiveFilters">No missions match your filters</span>
                            <span x-show="!hasActiveFilters">No missions assigned</span>
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <span x-show="hasActiveFilters">Try adjusting your filters to see more results.</span>
                            <span x-show="!hasActiveFilters">You don't have any missions assigned to you yet.</span>
                        </p>
                        <button x-show="hasActiveFilters" @click="clearFilters()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function missionList() {
    return {
        missions: @json($missions->items()),
        showFilters: false,
        filters: {
            status: '',
            type: ''
        },
        sortBy: 'scheduled_at',
        
        get filteredMissions() {
            let filtered = this.missions;
            
            // Apply filters
            if (this.filters.status) {
                filtered = filtered.filter(mission => mission.status === this.filters.status);
            }
            
            if (this.filters.type) {
                filtered = filtered.filter(mission => mission.type === this.filters.type);
            }
            
            // Apply sorting
            switch(this.sortBy) {
                case 'scheduled_at':
                    filtered.sort((a, b) => new Date(b.scheduled_at) - new Date(a.scheduled_at));
                    break;
                case 'scheduled_at_asc':
                    filtered.sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at));
                    break;
                case 'type':
                    filtered.sort((a, b) => a.type.localeCompare(b.type));
                    break;
                case 'type_desc':
                    filtered.sort((a, b) => b.type.localeCompare(a.type));
                    break;
                case 'status':
                    filtered.sort((a, b) => a.status.localeCompare(b.status));
                    break;
                case 'status_desc':
                    filtered.sort((a, b) => b.status.localeCompare(a.status));
                    break;
            }
            
            return filtered;
        },
        
        get hasActiveFilters() {
            return this.filters.status || this.filters.type;
        },
        
        toggleFilters() {
            this.showFilters = !this.showFilters;
        },
        
        clearFilters() {
            this.filters.status = '';
            this.filters.type = '';
            this.sortBy = 'scheduled_at';
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>
@endpush
@endsection