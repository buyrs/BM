<template>
  <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg sm:text-xl font-bold text-text-primary">
        Recent Activity
      </h3>
      <button 
        @click="refreshActivity"
        :disabled="loading"
        class="text-primary hover:text-primary-dark text-sm font-medium disabled:opacity-50"
      >
        <svg 
          :class="['w-4 h-4 inline mr-1', loading ? 'animate-spin' : '']" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Refresh
      </button>
    </div>
    
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="animate-pulse">
        <div class="flex items-center space-x-4">
          <div class="w-10 h-10 bg-gray-200 rounded-full"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else-if="activities.length === 0" class="text-center py-8">
      <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <p class="text-text-secondary">No recent activity to display</p>
    </div>
    
    <div v-else class="space-y-4">
      <div 
        v-for="activity in activities.slice(0, maxItems)" 
        :key="activity.id"
        class="flex items-start space-x-4 p-3 rounded-lg hover:bg-gray-50 transition-colors duration-150"
      >
        <div :class="getActivityIconClass(activity.type)" class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActivityIconPath(activity.type)"/>
          </svg>
        </div>
        
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-text-primary truncate">
              {{ activity.description }}
            </p>
            <span :class="getActivityBadgeClass(activity.type)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
              {{ formatActivityType(activity.type) }}
            </span>
          </div>
          
          <div class="flex items-center justify-between mt-1">
            <p class="text-xs text-text-secondary">
              {{ formatRelativeTime(activity.created_at) }}
            </p>
            <p v-if="activity.user" class="text-xs text-text-secondary">
              by {{ activity.user.name }}
            </p>
          </div>
          
          <div v-if="activity.metadata" class="mt-2 text-xs text-text-secondary">
            <span v-if="activity.metadata.address" class="block">
              📍 {{ activity.metadata.address }}
            </span>
            <span v-if="activity.metadata.duration" class="block">
              ⏱️ Duration: {{ activity.metadata.duration }}
            </span>
          </div>
        </div>
      </div>
      
      <div v-if="activities.length > maxItems" class="text-center pt-4 border-t border-gray-200">
        <button 
          @click="showMore = !showMore"
          class="text-primary hover:text-primary-dark text-sm font-medium"
        >
          {{ showMore ? 'Show Less' : `Show ${activities.length - maxItems} More` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { formatDistanceToNow, parseISO } from 'date-fns';

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  },
  maxItems: {
    type: Number,
    default: 5
  }
});

const emit = defineEmits(['refresh']);

const loading = ref(false);
const showMore = ref(false);

const displayedActivities = computed(() => {
  return showMore.value ? props.activities : props.activities.slice(0, props.maxItems);
});

const refreshActivity = async () => {
  loading.value = true;
  try {
    await emit('refresh');
  } finally {
    loading.value = false;
  }
};

const getActivityIconClass = (type) => {
  const classes = {
    mission_created: 'bg-info-bg text-info-text',
    mission_assigned: 'bg-warning-bg text-warning-text',
    mission_completed: 'bg-success-bg text-success-text',
    mission_incident: 'bg-error-bg text-error-text',
    checker_created: 'bg-primary bg-opacity-10 text-primary',
    checker_activated: 'bg-success-bg text-success-text',
    checker_deactivated: 'bg-gray-100 text-gray-600',
    system_alert: 'bg-error-bg text-error-text',
    default: 'bg-gray-100 text-gray-600'
  };
  return classes[type] || classes.default;
};

const getActivityIconPath = (type) => {
  const paths = {
    mission_created: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
    mission_assigned: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    mission_completed: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    mission_incident: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
    checker_created: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
    checker_activated: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    checker_deactivated: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    system_alert: 'M15 17h5l-5 5v-5zM4 19h6v-7a3 3 0 015.755-.96M15 17h5l-5 5v-5z',
    default: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
  };
  return paths[type] || paths.default;
};

const getActivityBadgeClass = (type) => {
  const classes = {
    mission_created: 'bg-info-bg text-info-text',
    mission_assigned: 'bg-warning-bg text-warning-text',
    mission_completed: 'bg-success-bg text-success-text',
    mission_incident: 'bg-error-bg text-error-text',
    checker_created: 'bg-primary bg-opacity-10 text-primary',
    checker_activated: 'bg-success-bg text-success-text',
    checker_deactivated: 'bg-gray-100 text-gray-600',
    system_alert: 'bg-error-bg text-error-text',
    default: 'bg-gray-100 text-gray-600'
  };
  return classes[type] || classes.default;
};

const formatActivityType = (type) => {
  const labels = {
    mission_created: 'Created',
    mission_assigned: 'Assigned',
    mission_completed: 'Completed',
    mission_incident: 'Incident',
    checker_created: 'New User',
    checker_activated: 'Activated',
    checker_deactivated: 'Deactivated',
    system_alert: 'Alert',
    default: 'Activity'
  };
  return labels[type] || labels.default;
};

const formatRelativeTime = (dateString) => {
  try {
    const date = typeof dateString === 'string' ? parseISO(dateString) : dateString;
    return formatDistanceToNow(date, { addSuffix: true });
  } catch (error) {
    return 'Unknown time';
  }
};

onMounted(() => {
  // Auto-refresh every 30 seconds
  const interval = setInterval(() => {
    if (!loading.value) {
      refreshActivity();
    }
  }, 30000);

  // Cleanup interval on unmount
  return () => clearInterval(interval);
});
</script>