<template>
  <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg sm:text-xl font-bold text-text-primary">
        System Health
      </h3>
      <div class="flex items-center space-x-2">
        <div :class="getOverallHealthClass()" class="w-3 h-3 rounded-full"></div>
        <span class="text-sm font-medium" :class="getOverallHealthTextClass()">
          {{ getOverallHealthStatus() }}
        </span>
        <button 
          @click="refreshHealth"
          :disabled="loading"
          class="ml-4 text-primary hover:text-primary-dark text-sm font-medium disabled:opacity-50"
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
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 4" :key="i" class="animate-pulse">
        <div class="flex items-center justify-between p-4 border rounded-lg">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gray-200 rounded"></div>
            <div class="space-y-2">
              <div class="h-4 bg-gray-200 rounded w-24"></div>
              <div class="h-3 bg-gray-200 rounded w-16"></div>
            </div>
          </div>
          <div class="w-16 h-6 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>

    <div v-else class="space-y-4">
      <!-- Database Health -->
      <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
        <div class="flex items-center space-x-3">
          <div :class="getHealthIndicatorClass(health.database)" class="w-8 h-8 rounded flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
            </svg>
          </div>
          <div>
            <h4 class="font-medium text-text-primary">Database</h4>
            <p class="text-sm text-text-secondary">
              {{ health.database?.response_time ? `${health.database.response_time}ms` : 'Checking...' }}
            </p>
          </div>
        </div>
        <span :class="getStatusBadgeClass(health.database?.status)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
          {{ formatHealthStatus(health.database?.status) }}
        </span>
      </div>

      <!-- API Health -->
      <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
        <div class="flex items-center space-x-3">
          <div :class="getHealthIndicatorClass(health.api)" class="w-8 h-8 rounded flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
            </svg>
          </div>
          <div>
            <h4 class="font-medium text-text-primary">API Services</h4>
            <p class="text-sm text-text-secondary">
              {{ health.api?.active_connections || 0 }} active connections
            </p>
          </div>
        </div>
        <span :class="getStatusBadgeClass(health.api?.status)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
          {{ formatHealthStatus(health.api?.status) }}
        </span>
      </div>

      <!-- Storage Health -->
      <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
        <div class="flex items-center space-x-3">
          <div :class="getHealthIndicatorClass(health.storage)" class="w-8 h-8 rounded flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <div>
            <h4 class="font-medium text-text-primary">File Storage</h4>
            <p class="text-sm text-text-secondary">
              {{ health.storage?.disk_usage || 'Unknown' }} disk usage
            </p>
          </div>
        </div>
        <span :class="getStatusBadgeClass(health.storage?.status)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
          {{ formatHealthStatus(health.storage?.status) }}
        </span>
      </div>

      <!-- Queue Health -->
      <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
        <div class="flex items-center space-x-3">
          <div :class="getHealthIndicatorClass(health.queue)" class="w-8 h-8 rounded flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
          </div>
          <div>
            <h4 class="font-medium text-text-primary">Background Jobs</h4>
            <p class="text-sm text-text-secondary">
              {{ health.queue?.pending_jobs || 0 }} pending jobs
            </p>
          </div>
        </div>
        <span :class="getStatusBadgeClass(health.queue?.status)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
          {{ formatHealthStatus(health.queue?.status) }}
        </span>
      </div>
    </div>

    <!-- Recent Errors -->
    <div v-if="health.recent_errors && health.recent_errors.length > 0" class="mt-6 pt-6 border-t border-gray-200">
      <h4 class="font-medium text-text-primary mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-error-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        Recent Errors
      </h4>
      <div class="space-y-2">
        <div 
          v-for="error in health.recent_errors.slice(0, 3)" 
          :key="error.id"
          class="p-3 bg-error-bg bg-opacity-10 border border-error-border rounded-lg"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="text-sm font-medium text-error-text">{{ error.message }}</p>
              <p class="text-xs text-text-secondary mt-1">
                {{ formatRelativeTime(error.created_at) }} • {{ error.context || 'System' }}
              </p>
            </div>
            <button 
              @click="viewErrorDetails(error)"
              class="text-error-text hover:text-error-text-dark text-xs font-medium ml-2"
            >
              Details
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Metrics -->
    <div v-if="health.performance" class="mt-6 pt-6 border-t border-gray-200">
      <h4 class="font-medium text-text-primary mb-4">Performance Metrics</h4>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="text-center">
          <p class="text-2xl font-bold text-primary">{{ health.performance.avg_response_time }}ms</p>
          <p class="text-xs text-text-secondary">Avg Response</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-info-text">{{ health.performance.requests_per_minute }}</p>
          <p class="text-xs text-text-secondary">Requests/min</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-success-text">{{ health.performance.uptime }}%</p>
          <p class="text-xs text-text-secondary">Uptime</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-warning-text">{{ health.performance.memory_usage }}MB</p>
          <p class="text-xs text-text-secondary">Memory</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { formatDistanceToNow, parseISO } from 'date-fns';

const props = defineProps({
  health: {
    type: Object,
    default: () => ({
      database: { status: 'unknown' },
      api: { status: 'unknown' },
      storage: { status: 'unknown' },
      queue: { status: 'unknown' },
      recent_errors: [],
      performance: null
    })
  }
});

const emit = defineEmits(['refresh', 'view-error']);

const loading = ref(false);

const refreshHealth = async () => {
  loading.value = true;
  try {
    await emit('refresh');
  } finally {
    loading.value = false;
  }
};

const viewErrorDetails = (error) => {
  emit('view-error', error);
};

const getOverallHealthClass = () => {
  const statuses = [
    props.health.database?.status,
    props.health.api?.status,
    props.health.storage?.status,
    props.health.queue?.status
  ];
  
  if (statuses.includes('error') || statuses.includes('critical')) {
    return 'bg-error-text';
  } else if (statuses.includes('warning')) {
    return 'bg-warning-text';
  } else if (statuses.every(status => status === 'healthy')) {
    return 'bg-success-text';
  } else {
    return 'bg-gray-400';
  }
};

const getOverallHealthTextClass = () => {
  const statuses = [
    props.health.database?.status,
    props.health.api?.status,
    props.health.storage?.status,
    props.health.queue?.status
  ];
  
  if (statuses.includes('error') || statuses.includes('critical')) {
    return 'text-error-text';
  } else if (statuses.includes('warning')) {
    return 'text-warning-text';
  } else if (statuses.every(status => status === 'healthy')) {
    return 'text-success-text';
  } else {
    return 'text-text-secondary';
  }
};

const getOverallHealthStatus = () => {
  const statuses = [
    props.health.database?.status,
    props.health.api?.status,
    props.health.storage?.status,
    props.health.queue?.status
  ];
  
  if (statuses.includes('error') || statuses.includes('critical')) {
    return 'Critical Issues';
  } else if (statuses.includes('warning')) {
    return 'Minor Issues';
  } else if (statuses.every(status => status === 'healthy')) {
    return 'All Systems Healthy';
  } else {
    return 'Checking...';
  }
};

const getHealthIndicatorClass = (healthItem) => {
  const status = healthItem?.status || 'unknown';
  const classes = {
    healthy: 'bg-success-bg text-success-text',
    warning: 'bg-warning-bg text-warning-text',
    error: 'bg-error-bg text-error-text',
    critical: 'bg-error-bg text-error-text',
    unknown: 'bg-gray-100 text-gray-600'
  };
  return classes[status] || classes.unknown;
};

const getStatusBadgeClass = (status) => {
  const classes = {
    healthy: 'bg-success-bg text-success-text',
    warning: 'bg-warning-bg text-warning-text',
    error: 'bg-error-bg text-error-text',
    critical: 'bg-error-bg text-error-text',
    unknown: 'bg-gray-100 text-gray-600'
  };
  return classes[status] || classes.unknown;
};

const formatHealthStatus = (status) => {
  const labels = {
    healthy: 'Healthy',
    warning: 'Warning',
    error: 'Error',
    critical: 'Critical',
    unknown: 'Unknown'
  };
  return labels[status] || status;
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
  // Auto-refresh every 60 seconds
  const interval = setInterval(() => {
    if (!loading.value) {
      refreshHealth();
    }
  }, 60000);

  // Cleanup interval on unmount
  return () => clearInterval(interval);
});
</script>