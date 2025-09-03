<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 lg:gap-4 xl:gap-6">
    <!-- Assigned Missions Card -->
    <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-2 sm:p-3 lg:p-4 xl:p-6 border-l-4 border-warning-border touch-manipulation hover:shadow-lg transition-shadow duration-200">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1">
          <h3 class="text-xs sm:text-sm lg:text-base xl:text-lg font-semibold text-text-secondary mb-1 sm:mb-0">
            Assigned
          </h3>
          <p class="text-xl sm:text-2xl lg:text-3xl xl:text-4xl font-extrabold text-warning-text">
            {{ stats.assigned }}
          </p>
          <div class="flex items-center mt-1 sm:mt-2">
            <span class="text-xs text-text-secondary">
              {{ stats.todayCount }} due today
            </span>
          </div>
        </div>
        <div class="hidden sm:block p-1 sm:p-2 lg:p-3 rounded-full bg-warning-bg mt-2 sm:mt-0 sm:ml-2">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 xl:w-8 xl:h-8 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
      </div>
      
      <!-- Mobile progress indicator -->
      <div class="sm:hidden mt-2">
        <div class="w-full bg-gray-200 rounded-full h-1.5">
          <div 
            class="bg-warning-text h-1.5 rounded-full transition-all duration-300" 
            :style="{ width: `${Math.min((stats.todayCount / Math.max(stats.assigned, 1)) * 100, 100)}%` }"
          />
        </div>
      </div>
    </div>

    <!-- Completed Missions Card -->
    <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-2 sm:p-3 lg:p-4 xl:p-6 border-l-4 border-success-border touch-manipulation hover:shadow-lg transition-shadow duration-200">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1">
          <h3 class="text-xs sm:text-sm lg:text-base xl:text-lg font-semibold text-text-secondary mb-1 sm:mb-0">
            Completed
          </h3>
          <p class="text-xl sm:text-2xl lg:text-3xl xl:text-4xl font-extrabold text-success-text">
            {{ stats.completed }}
          </p>
          <div class="flex items-center mt-1 sm:mt-2">
            <span class="text-xs text-text-secondary">
              {{ stats.completionRate }}% rate
            </span>
          </div>
        </div>
        <div class="hidden sm:block p-1 sm:p-2 lg:p-3 rounded-full bg-success-bg mt-2 sm:mt-0 sm:ml-2">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 xl:w-8 xl:h-8 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
      </div>
      
      <!-- Mobile trend indicator -->
      <div class="sm:hidden mt-2 flex items-center">
        <svg 
          class="w-3 h-3 mr-1" 
          :class="stats.trend === 'up' ? 'text-success-text' : stats.trend === 'down' ? 'text-error-text' : 'text-gray-400'"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path 
            v-if="stats.trend === 'up'"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
          />
          <path 
            v-else-if="stats.trend === 'down'"
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"
          />
          <path 
            v-else
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M5 12h14"
          />
        </svg>
        <span class="text-xs text-text-secondary">vs last week</span>
      </div>
    </div>

    <!-- Pending Checklists Card -->
    <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-2 sm:p-3 lg:p-4 xl:p-6 border-l-4 border-info-border touch-manipulation hover:shadow-lg transition-shadow duration-200">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1">
          <h3 class="text-xs sm:text-sm lg:text-base xl:text-lg font-semibold text-text-secondary mb-1 sm:mb-0">
            Pending
          </h3>
          <p class="text-xl sm:text-2xl lg:text-3xl xl:text-4xl font-extrabold text-info-text">
            {{ stats.pending }}
          </p>
          <div class="flex items-center mt-1 sm:mt-2">
            <span class="text-xs text-text-secondary">
              Checklists
            </span>
          </div>
        </div>
        <div class="hidden sm:block p-1 sm:p-2 lg:p-3 rounded-full bg-info-bg mt-2 sm:mt-0 sm:ml-2">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 xl:w-8 xl:h-8 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
      </div>
      
      <!-- Mobile urgency indicator -->
      <div class="sm:hidden mt-2">
        <div class="flex items-center">
          <div 
            v-for="i in Math.min(stats.pending, 5)" 
            :key="i" 
            class="w-1.5 h-1.5 rounded-full mr-1"
            :class="i <= stats.urgentPending ? 'bg-error-text animate-pulse' : 'bg-info-text'"
          />
          <span v-if="stats.pending > 5" class="text-xs text-text-secondary ml-1">+{{ stats.pending - 5 }}</span>
        </div>
      </div>
    </div>

    <!-- Performance Score Card -->
    <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-2 sm:p-3 lg:p-4 xl:p-6 border-l-4 border-primary touch-manipulation hover:shadow-lg transition-shadow duration-200">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1">
          <h3 class="text-xs sm:text-sm lg:text-base xl:text-lg font-semibold text-text-secondary mb-1 sm:mb-0">
            Score
          </h3>
          <p class="text-xl sm:text-2xl lg:text-3xl xl:text-4xl font-extrabold text-primary">
            {{ stats.performanceScore }}%
          </p>
          <div class="flex items-center mt-1 sm:mt-2">
            <span class="text-xs text-text-secondary">
              Performance
            </span>
          </div>
        </div>
        <div class="hidden sm:block p-1 sm:p-2 lg:p-3 rounded-full bg-secondary mt-2 sm:mt-0 sm:ml-2">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 xl:w-8 xl:h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
      </div>
      
      <!-- Mobile performance ring -->
      <div class="sm:hidden mt-2 flex items-center justify-center">
        <div class="relative w-10 h-10">
          <svg class="w-10 h-10 transform -rotate-90" viewBox="0 0 32 32">
            <circle
              cx="16"
              cy="16"
              r="12"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
              class="text-gray-200"
            />
            <circle
              cx="16"
              cy="16"
              r="12"
              stroke="currentColor"
              stroke-width="2"
              fill="none"
              stroke-linecap="round"
              class="text-primary transition-all duration-500"
              :stroke-dasharray="`${(stats.performanceScore / 100) * 75.4} 75.4`"
            />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-xs font-bold text-primary">{{ stats.performanceScore }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      assigned: 0,
      completed: 0,
      pending: 0,
      performanceScore: 0,
      todayCount: 0,
      completionRate: 0,
      trend: 'stable',
      urgentPending: 0,
    }),
  },
});
</script>