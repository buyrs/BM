<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
    <!-- Total Missions Card -->
    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-info-border">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-sm sm:text-lg font-semibold text-text-secondary">
            Total Missions
          </h3>
          <p class="text-2xl sm:text-4xl font-extrabold text-info-text mt-1 sm:mt-2">
            {{ stats.totalMissions }}
          </p>
          <div v-if="stats.missionTrend" class="flex items-center mt-2">
            <svg 
              :class="[
                'w-4 h-4 mr-1',
                stats.missionTrend > 0 ? 'text-success-text' : 'text-error-text'
              ]"
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                :d="stats.missionTrend > 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'"
              />
            </svg>
            <span 
              :class="[
                'text-sm font-medium',
                stats.missionTrend > 0 ? 'text-success-text' : 'text-error-text'
              ]"
            >
              {{ Math.abs(stats.missionTrend) }}%
            </span>
          </div>
        </div>
        <div class="p-2 sm:p-3 rounded-full bg-info-bg">
          <svg class="w-6 h-6 sm:w-8 sm:h-8 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
      </div>
    </div>
    
    <!-- Assigned Missions Card -->
    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-warning-border">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-sm sm:text-lg font-semibold text-text-secondary">
            Assigned Missions
          </h3>
          <p class="text-2xl sm:text-4xl font-extrabold text-warning-text mt-1 sm:mt-2">
            {{ stats.assignedMissions }}
          </p>
          <p class="text-xs text-text-secondary mt-1">
            {{ getAssignmentRate() }}% of total
          </p>
        </div>
        <div class="p-2 sm:p-3 rounded-full bg-warning-bg">
          <svg class="w-6 h-6 sm:w-8 sm:h-8 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
        </div>
      </div>
    </div>
    
    <!-- Completed Missions Card -->
    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-success-border">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-sm sm:text-lg font-semibold text-text-secondary">
            Completed Missions
          </h3>
          <p class="text-2xl sm:text-4xl font-extrabold text-success-text mt-1 sm:mt-2">
            {{ stats.completedMissions }}
          </p>
          <p class="text-xs text-text-secondary mt-1">
            {{ getCompletionRate() }}% completion rate
          </p>
        </div>
        <div class="p-2 sm:p-3 rounded-full bg-success-bg">
          <svg class="w-6 h-6 sm:w-8 sm:h-8 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
      </div>
    </div>

    <!-- Active Checkers Card -->
    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-primary">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-sm sm:text-lg font-semibold text-text-secondary">
            Active Checkers
          </h3>
          <p class="text-2xl sm:text-4xl font-extrabold text-primary mt-1 sm:mt-2">
            {{ stats.activeCheckers }}
          </p>
          <p class="text-xs text-text-secondary mt-1">
            {{ stats.onlineCheckers }} online now
          </p>
        </div>
        <div class="p-2 sm:p-3 rounded-full bg-secondary">
          <svg class="w-6 h-6 sm:w-8 sm:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      totalMissions: 0,
      assignedMissions: 0,
      completedMissions: 0,
      activeCheckers: 0,
      onlineCheckers: 0,
      missionTrend: 0
    })
  }
});

const getCompletionRate = () => {
  if (!props.stats.totalMissions || props.stats.totalMissions === 0) return 0;
  return Math.round((props.stats.completedMissions / props.stats.totalMissions) * 100);
};

const getAssignmentRate = () => {
  if (!props.stats.totalMissions || props.stats.totalMissions === 0) return 0;
  return Math.round((props.stats.assignedMissions / props.stats.totalMissions) * 100);
};
</script>