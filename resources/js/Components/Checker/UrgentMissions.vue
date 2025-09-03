<template>
  <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 lg:p-6 border-l-4 border-error-border relative overflow-hidden">
    <!-- Animated background for critical alerts -->
    <div 
      v-if="hasCriticalMissions" 
      class="absolute inset-0 bg-gradient-to-r from-red-50 to-orange-50 opacity-50 animate-pulse"
    />
    
    <div class="relative z-10">
      <div class="flex items-start sm:items-center mb-3 sm:mb-4">
        <div class="relative mr-2 sm:mr-3 flex-shrink-0">
          <svg 
            class="w-5 h-5 sm:w-6 sm:h-6 text-error-text mt-0.5 sm:mt-0" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
            />
          </svg>
          <!-- Pulsing ring for critical missions -->
          <div 
            v-if="hasCriticalMissions"
            class="absolute -inset-1 bg-error-text rounded-full opacity-25 animate-ping"
          />
        </div>
        
        <div class="flex-1">
          <h3 class="text-base sm:text-lg lg:text-xl font-bold text-error-text">
            {{ getUrgencyTitle() }}
          </h3>
          <p class="text-xs sm:text-sm text-error-text mt-1">
            {{ missions.length }} mission{{ missions.length !== 1 ? 's' : '' }} {{ getUrgencyDescription() }}
          </p>
        </div>
        
        <!-- Enhanced priority indicators -->
        <div class="flex flex-col items-end ml-2">
          <div class="flex items-center space-x-1 mb-1">
            <div 
              v-for="i in Math.min(missions.length, 3)" 
              :key="i" 
              class="w-2 h-2 rounded-full animate-pulse"
              :class="getPriorityDotColor(missions[i-1])"
              :style="{ animationDelay: `${i * 0.3}s` }"
            />
          </div>
          <span v-if="missions.length > 3" class="text-xs text-error-text font-medium">
            +{{ missions.length - 3 }}
          </span>
        </div>
      </div>
      
      <!-- Priority level indicator -->
      <div class="mb-3 sm:mb-4">
        <div class="flex items-center justify-between text-xs">
          <span class="text-text-secondary">Priority Level</span>
          <span :class="getOverallPriorityClass()" class="font-semibold px-2 py-1 rounded-full">
            {{ getOverallPriority() }}
          </span>
        </div>
        <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
          <div 
            class="h-2 rounded-full transition-all duration-500"
            :class="getPriorityBarColor()"
            :style="{ width: `${getPriorityPercentage()}%` }"
          />
        </div>
      </div>
    </div>
    
    <div class="relative z-10">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        <div 
          v-for="mission in sortedMissions" 
          :key="mission.id" 
          class="rounded-lg p-3 sm:p-4 hover:shadow-lg transition-all duration-200 touch-manipulation relative"
          :class="getMissionCardClass(mission)"
        >
          <!-- Priority stripe -->
          <div 
            class="absolute left-0 top-0 bottom-0 w-1 rounded-l-lg"
            :class="getPriorityStripeColor(mission)"
          />
          
          <div class="flex items-start justify-between mb-2">
            <h4 class="font-semibold text-sm sm:text-base pr-2" :class="getMissionTextColor(mission)">
              {{ mission.address }}
            </h4>
            <div class="flex flex-col items-end space-y-1 flex-shrink-0">
              <span 
                class="text-xs px-2 py-1 rounded-full font-medium"
                :class="getPriorityBadgeClass(mission)"
              >
                {{ getPriorityLabel(mission) }}
              </span>
              <!-- Time remaining indicator -->
              <div class="flex items-center text-xs" :class="getTimeRemainingColor(mission)">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ getTimeRemaining(mission) }}
              </div>
            </div>
          </div>
        
          <div class="space-y-1 mb-3">
            <p class="text-sm flex items-center" :class="getMissionTextColor(mission)">
              <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }}
            </p>
            
            <p class="text-xs flex items-center" :class="getMissionTextColor(mission)">
              <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              Due: {{ formatDate(mission.scheduled_at) }}
            </p>
            
            <p v-if="mission.tenant_name" class="text-xs flex items-center" :class="getMissionTextColor(mission)">
              <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              {{ mission.tenant_name }}
            </p>
            
            <!-- Additional mission details -->
            <div v-if="mission.notes" class="text-xs opacity-75 mt-2 p-2 bg-black bg-opacity-5 rounded">
              {{ mission.notes.substring(0, 60) }}{{ mission.notes.length > 60 ? '...' : '' }}
            </div>
          </div>
        
          <div class="flex space-x-2">
            <Link
              :href="route('missions.show', mission.id)"
              class="flex-1 text-center text-xs px-3 py-2 rounded font-medium transition-all duration-200 touch-manipulation"
              :class="getViewButtonClass(mission)"
            >
              View Details
            </Link>
            
            <button
              v-if="mission.status === 'assigned'"
              @click="startMission(mission)"
              class="flex-1 text-center text-xs px-3 py-2 rounded font-medium transition-all duration-200 touch-manipulation transform hover:scale-105 active:scale-95"
              :class="getStartButtonClass(mission)"
            >
              {{ getStartButtonText(mission) }}
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-if="missions.length === 0" class="text-center py-8">
      <svg class="mx-auto h-12 w-12 text-success-text mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p class="text-lg font-medium text-success-text">All caught up!</p>
      <p class="text-sm text-text-secondary">No urgent missions at the moment.</p>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  missions: {
    type: Array,
    required: true,
    default: () => [],
  },
});

const emit = defineEmits(['start-mission']);

// Computed properties
const sortedMissions = computed(() => {
  return [...props.missions].sort((a, b) => {
    const aPriority = getPriorityScore(a);
    const bPriority = getPriorityScore(b);
    return bPriority - aPriority; // Higher priority first
  });
});

const hasCriticalMissions = computed(() => {
  return props.missions.some(mission => getPriorityScore(mission) >= 90);
});

// Priority and urgency methods
const getPriorityScore = (mission) => {
  const now = new Date();
  const scheduled = new Date(mission.scheduled_at);
  const hoursUntilDue = (scheduled - now) / (1000 * 60 * 60);
  
  if (hoursUntilDue < 0) return 100; // Overdue
  if (hoursUntilDue < 1) return 95;  // Critical
  if (hoursUntilDue < 2) return 85;  // Urgent
  if (hoursUntilDue < 6) return 70;  // High
  if (hoursUntilDue < 24) return 50; // Medium
  return 30; // Low
};

const getPriorityLabel = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 100) return 'OVERDUE';
  if (score >= 95) return 'CRITICAL';
  if (score >= 85) return 'URGENT';
  if (score >= 70) return 'HIGH';
  if (score >= 50) return 'TODAY';
  return 'SOON';
};

const getOverallPriority = () => {
  if (props.missions.length === 0) return 'NONE';
  const maxScore = Math.max(...props.missions.map(getPriorityScore));
  if (maxScore >= 100) return 'CRITICAL';
  if (maxScore >= 85) return 'URGENT';
  if (maxScore >= 70) return 'HIGH';
  return 'MEDIUM';
};

const getPriorityPercentage = () => {
  if (props.missions.length === 0) return 0;
  const maxScore = Math.max(...props.missions.map(getPriorityScore));
  return Math.min(maxScore, 100);
};

const getUrgencyTitle = () => {
  const priority = getOverallPriority();
  switch (priority) {
    case 'CRITICAL': return '🚨 Critical Missions Alert';
    case 'URGENT': return '⚠️ Urgent Missions';
    case 'HIGH': return '📋 High Priority Missions';
    default: return '📝 Missions Requiring Attention';
  }
};

const getUrgencyDescription = () => {
  const priority = getOverallPriority();
  switch (priority) {
    case 'CRITICAL': return 'require immediate action';
    case 'URGENT': return 'need urgent attention';
    case 'HIGH': return 'should be completed soon';
    default: return 'need your attention';
  }
};

// Styling methods
const getMissionCardClass = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 100) return 'bg-red-100 border border-red-300 shadow-red-100';
  if (score >= 95) return 'bg-red-50 border border-red-200 shadow-red-50';
  if (score >= 85) return 'bg-orange-50 border border-orange-200 shadow-orange-50';
  if (score >= 70) return 'bg-yellow-50 border border-yellow-200 shadow-yellow-50';
  return 'bg-blue-50 border border-blue-200 shadow-blue-50';
};

const getMissionTextColor = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 100) return 'text-red-800';
  if (score >= 95) return 'text-red-700';
  if (score >= 85) return 'text-orange-700';
  if (score >= 70) return 'text-yellow-700';
  return 'text-blue-700';
};

const getPriorityBadgeClass = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 100) return 'bg-red-600 text-white animate-pulse';
  if (score >= 95) return 'bg-red-500 text-white';
  if (score >= 85) return 'bg-orange-500 text-white';
  if (score >= 70) return 'bg-yellow-500 text-white';
  return 'bg-blue-500 text-white';
};

const getPriorityStripeColor = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 100) return 'bg-red-600';
  if (score >= 95) return 'bg-red-500';
  if (score >= 85) return 'bg-orange-500';
  if (score >= 70) return 'bg-yellow-500';
  return 'bg-blue-500';
};

const getPriorityDotColor = (mission) => {
  if (!mission) return 'bg-gray-400';
  const score = getPriorityScore(mission);
  if (score >= 100) return 'bg-red-600';
  if (score >= 95) return 'bg-red-500';
  if (score >= 85) return 'bg-orange-500';
  if (score >= 70) return 'bg-yellow-500';
  return 'bg-blue-500';
};

const getOverallPriorityClass = () => {
  const priority = getOverallPriority();
  switch (priority) {
    case 'CRITICAL': return 'bg-red-600 text-white';
    case 'URGENT': return 'bg-orange-500 text-white';
    case 'HIGH': return 'bg-yellow-500 text-white';
    default: return 'bg-blue-500 text-white';
  }
};

const getPriorityBarColor = () => {
  const priority = getOverallPriority();
  switch (priority) {
    case 'CRITICAL': return 'bg-red-600';
    case 'URGENT': return 'bg-orange-500';
    case 'HIGH': return 'bg-yellow-500';
    default: return 'bg-blue-500';
  }
};

const getViewButtonClass = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 95) return 'bg-red-600 text-white hover:bg-red-700';
  if (score >= 85) return 'bg-orange-500 text-white hover:bg-orange-600';
  if (score >= 70) return 'bg-yellow-500 text-white hover:bg-yellow-600';
  return 'bg-blue-500 text-white hover:bg-blue-600';
};

const getStartButtonClass = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 95) return 'bg-white text-red-600 border-2 border-red-600 hover:bg-red-50';
  if (score >= 85) return 'bg-white text-orange-500 border-2 border-orange-500 hover:bg-orange-50';
  if (score >= 70) return 'bg-white text-yellow-600 border-2 border-yellow-600 hover:bg-yellow-50';
  return 'bg-white text-blue-500 border-2 border-blue-500 hover:bg-blue-50';
};

const getStartButtonText = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 100) return 'START NOW!';
  if (score >= 95) return 'URGENT START';
  return 'Start Now';
};

const getTimeRemainingColor = (mission) => {
  const score = getPriorityScore(mission);
  if (score >= 100) return 'text-red-600 font-bold';
  if (score >= 95) return 'text-red-500 font-semibold';
  if (score >= 85) return 'text-orange-500 font-semibold';
  if (score >= 70) return 'text-yellow-600';
  return 'text-blue-600';
};

// Time and date formatting
const getTimeRemaining = (mission) => {
  const now = new Date();
  const scheduled = new Date(mission.scheduled_at);
  const diffMinutes = (scheduled - now) / (1000 * 60);
  
  if (diffMinutes < 0) {
    const overdue = Math.abs(diffMinutes);
    if (overdue < 60) return `${Math.floor(overdue)}m overdue`;
    if (overdue < 1440) return `${Math.floor(overdue / 60)}h overdue`;
    return `${Math.floor(overdue / 1440)}d overdue`;
  }
  
  if (diffMinutes < 60) return `${Math.floor(diffMinutes)}m left`;
  if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h left`;
  return `${Math.floor(diffMinutes / 1440)}d left`;
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  const now = new Date();
  const diffHours = (date - now) / (1000 * 60 * 60);
  
  if (diffHours < 0) {
    return `${Math.abs(Math.floor(diffHours))}h overdue`;
  } else if (diffHours < 24) {
    return `in ${Math.floor(diffHours)}h`;
  } else {
    return date.toLocaleDateString("en-US", {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
};

const startMission = (mission) => {
  emit('start-mission', mission);
};
</script>