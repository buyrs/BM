<template>
  <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-3 sm:p-4 lg:p-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-3 sm:mb-4 lg:mb-6">
      <div class="mb-3 sm:mb-0">
        <h3 class="text-base sm:text-lg lg:text-xl font-bold text-text-primary">Today's Schedule</h3>
        <p class="text-xs sm:text-sm text-text-secondary mt-1">
          {{ missions.length }} mission{{ missions.length !== 1 ? 's' : '' }} scheduled
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <!-- Filter buttons - responsive -->
        <div class="flex items-center space-x-1 sm:space-x-2">
          <button
            v-for="filter in filters"
            :key="filter.key"
            @click="activeFilter = filter.key"
            :class="[
              'px-2 sm:px-3 py-1 text-xs rounded-full transition-all duration-200 touch-manipulation',
              activeFilter === filter.key
                ? 'bg-primary text-white shadow-md transform scale-105'
                : 'bg-gray-100 text-text-secondary hover:bg-gray-200 hover:scale-105'
            ]"
          >
            {{ filter.label }}
            <span v-if="getFilterCount(filter.key) > 0" class="ml-1 text-xs opacity-75">
              ({{ getFilterCount(filter.key) }})
            </span>
          </button>
        </div>
        
        <Link
          :href="route('missions.assigned')"
          class="text-xs sm:text-sm text-primary hover:underline font-medium touch-manipulation whitespace-nowrap"
        >
          View All →
        </Link>
      </div>
    </div>
    
    <!-- Enhanced Timeline view for mobile -->
    <div class="block sm:hidden">
      <div class="relative">
        <!-- Timeline line -->
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gradient-to-b from-primary to-gray-200"></div>
        
        <div class="space-y-3">
          <div 
            v-for="(mission, index) in filteredMissions.slice(0, 6)" 
            :key="mission.id"
            class="relative flex items-start pl-10 touch-manipulation"
          >
            <!-- Enhanced Timeline dot with animation -->
            <div 
              class="absolute left-2.5 w-3 h-3 rounded-full border-2 border-white shadow-md z-10 transition-all duration-200"
              :class="getTimelineDotColor(mission.status)"
            >
              <div 
                v-if="mission.status === 'in_progress'"
                class="absolute inset-0 rounded-full animate-ping"
                :class="getTimelineDotColor(mission.status)"
              />
            </div>
            
            <!-- Enhanced Mission card -->
            <div 
              class="flex-1 bg-white border rounded-lg p-3 hover:shadow-md transition-all duration-200 transform hover:scale-[1.02]"
              :class="getMissionCardBorder(mission)"
              @click="navigateToMission(mission)"
            >
              <div class="flex items-start justify-between mb-2">
                <div class="flex-1 pr-2">
                  <h4 class="font-semibold text-text-primary text-sm">{{ mission.address }}</h4>
                  <div class="flex items-center text-xs text-text-secondary mt-1">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }}
                    <span class="mx-1">•</span>
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ formatTime(mission.scheduled_at) }}
                  </div>
                </div>
                <div class="flex flex-col items-end space-y-1">
                  <span :class="[
                    'text-xs px-2 py-1 rounded-full font-medium',
                    getStatusClass(mission.status)
                  ]">
                    {{ formatStatus(mission.status) }}
                  </span>
                  <div v-if="isUrgent(mission)" class="flex items-center text-xs text-red-600">
                    <svg class="w-3 h-3 mr-1 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    Urgent
                  </div>
                </div>
              </div>
              
              <!-- Mission details -->
              <div class="flex items-center justify-between">
                <div class="flex flex-wrap items-center gap-2 text-xs text-text-secondary">
                  <span v-if="mission.tenant_name" class="flex items-center bg-gray-100 px-2 py-1 rounded">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ mission.tenant_name }}
                  </span>
                  <span v-if="mission.estimated_duration" class="flex items-center bg-gray-100 px-2 py-1 rounded">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ mission.estimated_duration }}min
                  </span>
                </div>
                
                <!-- Action button -->
                <button
                  v-if="mission.status === 'assigned'"
                  @click.stop="startMission(mission)"
                  class="text-xs bg-primary text-white px-3 py-1.5 rounded-md hover:bg-accent transition-all duration-200 touch-manipulation transform hover:scale-105 active:scale-95 font-medium"
                >
                  {{ isUrgent(mission) ? 'Start Now!' : 'Start' }}
                </button>
              </div>
            </div>
          </div>
          
          <!-- Show more indicator -->
          <div v-if="filteredMissions.length > 6" class="relative flex items-center pl-10">
            <div class="absolute left-2.5 w-3 h-3 rounded-full bg-gray-300 border-2 border-white"></div>
            <div class="flex-1 text-center py-2">
              <Link
                :href="route('missions.assigned')"
                class="text-sm text-primary hover:underline font-medium touch-manipulation"
              >
                +{{ filteredMissions.length - 6 }} more missions
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Table view for desktop -->
    <div class="hidden sm:block space-y-4">
      <div 
        v-for="mission in filteredMissions" 
        :key="mission.id" 
        class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-150 cursor-pointer"
        @click="navigateToMission(mission)"
      >
        <div class="flex-1">
          <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full" :class="getStatusColor(mission.status)"></div>
            <div>
              <h4 class="font-semibold text-text-primary">{{ mission.address }}</h4>
              <p class="text-sm text-text-secondary">
                {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }} • 
                {{ formatTime(mission.scheduled_at) }}
                <span v-if="mission.tenant_name" class="ml-2">• {{ mission.tenant_name }}</span>
              </p>
            </div>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <span :class="[
            'text-xs px-3 py-1 rounded-full font-medium',
            getStatusClass(mission.status)
          ]">
            {{ formatStatus(mission.status) }}
          </span>
          <button
            v-if="mission.status === 'assigned'"
            @click.stop="startMission(mission)"
            class="text-sm bg-primary text-white px-4 py-2 rounded hover:bg-accent transition-colors duration-200"
          >
            Start Mission
          </button>
          <Link
            :href="route('missions.show', mission.id)"
            class="text-primary hover:text-accent text-sm font-medium"
            @click.stop
          >
            View
          </Link>
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-if="filteredMissions.length === 0" class="text-center py-8 text-text-secondary">
      <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
      </svg>
      <p class="text-lg font-medium">
        {{ activeFilter === 'all' ? 'No missions scheduled for today' : `No ${activeFilter} missions today` }}
      </p>
      <p class="text-sm">
        {{ activeFilter === 'all' ? 'Enjoy your free day!' : 'Try changing the filter to see other missions.' }}
      </p>
    </div>
    
    <!-- Offline indicator -->
    <div v-if="isOffline" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <span class="text-sm text-yellow-800">
          You're offline. Some features may be limited.
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  missions: {
    type: Array,
    required: true,
    default: () => [],
  },
});

const emit = defineEmits(['start-mission', 'navigate-to-mission']);

const activeFilter = ref('all');
const isOffline = ref(!navigator.onLine);

const filters = [
  { key: 'all', label: 'All' },
  { key: 'assigned', label: 'Assigned' },
  { key: 'in_progress', label: 'Active' },
  { key: 'urgent', label: 'Urgent' },
];

const filteredMissions = computed(() => {
  let filtered = [...props.missions];
  
  if (activeFilter.value === 'urgent') {
    const now = new Date();
    const twoHoursFromNow = new Date(now.getTime() + 2 * 60 * 60 * 1000);
    filtered = filtered.filter(mission => {
      const scheduledDate = new Date(mission.scheduled_at);
      return scheduledDate <= twoHoursFromNow && mission.status !== 'completed';
    });
  } else if (activeFilter.value !== 'all') {
    filtered = filtered.filter(mission => mission.status === activeFilter.value);
  }
  
  return filtered.sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at));
});

const getStatusClass = (status) => {
  const statusClasses = {
    completed: "bg-success-bg text-success-text",
    in_progress: "bg-info-bg text-info-text",
    assigned: "bg-warning-bg text-warning-text",
    pending: "bg-gray-100 text-gray-800",
    overdue: "bg-error-bg text-error-text",
  };
  return statusClasses[status] || "bg-gray-100 text-gray-800";
};

const getStatusColor = (status) => {
  const statusColors = {
    completed: "bg-success-text",
    in_progress: "bg-info-text",
    assigned: "bg-warning-text",
    pending: "bg-gray-400",
    overdue: "bg-error-text",
  };
  return statusColors[status] || "bg-gray-400";
};

const getTimelineDotColor = (status) => {
  const dotColors = {
    completed: "bg-success-text",
    in_progress: "bg-info-text",
    assigned: "bg-warning-text",
    pending: "bg-gray-400",
    overdue: "bg-error-text",
  };
  return dotColors[status] || "bg-gray-400";
};

const formatStatus = (status) => {
  const statusLabels = {
    completed: "Completed",
    in_progress: "In Progress",
    assigned: "Assigned",
    pending: "Pending",
    overdue: "Overdue",
  };
  return statusLabels[status] || status;
};

const formatTime = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleTimeString("en-US", {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const startMission = (mission) => {
  emit('start-mission', mission);
};

const navigateToMission = (mission) => {
  emit('navigate-to-mission', mission);
};

// Enhanced helper methods
const getFilterCount = (filterKey) => {
  if (filterKey === 'all') return props.missions.length;
  if (filterKey === 'urgent') {
    const now = new Date();
    const twoHoursFromNow = new Date(now.getTime() + 2 * 60 * 60 * 1000);
    return props.missions.filter(mission => {
      const scheduledDate = new Date(mission.scheduled_at);
      return scheduledDate <= twoHoursFromNow && mission.status !== 'completed';
    }).length;
  }
  return props.missions.filter(mission => mission.status === filterKey).length;
};

const isUrgent = (mission) => {
  const now = new Date();
  const scheduled = new Date(mission.scheduled_at);
  const hoursUntilDue = (scheduled - now) / (1000 * 60 * 60);
  return hoursUntilDue <= 2 && hoursUntilDue >= 0;
};

const getMissionCardBorder = (mission) => {
  if (isUrgent(mission)) return 'border-l-4 border-l-red-500';
  if (mission.status === 'in_progress') return 'border-l-4 border-l-blue-500';
  if (mission.status === 'completed') return 'border-l-4 border-l-green-500';
  return 'border-gray-200';
};

// Online/offline detection
const handleOnline = () => {
  isOffline.value = false;
};

const handleOffline = () => {
  isOffline.value = true;
};

onMounted(() => {
  window.addEventListener('online', handleOnline);
  window.addEventListener('offline', handleOffline);
});

onUnmounted(() => {
  window.removeEventListener('online', handleOnline);
  window.removeEventListener('offline', handleOffline);
});
</script>