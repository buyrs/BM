<template>
  <div class="bg-white rounded-lg sm:rounded-xl shadow-md p-3 sm:p-4 lg:p-6">
    <div class="flex items-center justify-between mb-3 sm:mb-4">
      <h3 class="text-base sm:text-lg font-bold text-text-primary">Quick Actions</h3>
      <div class="flex items-center text-xs text-text-secondary">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Fast Access
      </div>
    </div>
    
    <!-- Primary actions with enhanced mobile design -->
    <div class="space-y-2 sm:space-y-3 mb-4 sm:mb-6">
      <Link
        :href="route('missions.assigned')"
        class="flex items-center p-3 sm:p-4 bg-gradient-to-r from-primary to-accent text-white rounded-lg hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] touch-manipulation group"
      >
        <div class="p-2 bg-white bg-opacity-20 rounded-lg mr-3 flex-shrink-0 group-hover:bg-opacity-30 transition-all duration-200">
          <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
        </div>
        <div class="flex-1">
          <span class="font-semibold text-sm sm:text-base">View My Missions</span>
          <p class="text-xs opacity-90 mt-1">
            {{ stats.assignedCount }} assigned
            <span v-if="stats.assignedCount > 0" class="ml-1 px-1.5 py-0.5 bg-white bg-opacity-20 rounded-full text-xs">
              {{ stats.assignedCount }}
            </span>
          </p>
        </div>
        <svg class="w-5 h-5 opacity-70 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </Link>
      
      <Link
        :href="route('checklists.index')"
        class="flex items-center p-3 sm:p-4 bg-white border-2 border-primary text-primary rounded-lg hover:bg-primary hover:text-white hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] touch-manipulation group"
      >
        <div class="p-2 bg-primary bg-opacity-10 group-hover:bg-white group-hover:bg-opacity-20 rounded-lg mr-3 flex-shrink-0 transition-all duration-200">
          <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div class="flex-1">
          <span class="font-semibold text-sm sm:text-base">Complete Checklists</span>
          <p class="text-xs group-hover:text-white group-hover:opacity-90 text-text-secondary mt-1">
            {{ stats.pendingChecklists }} pending
            <span v-if="stats.pendingChecklists > 0" class="ml-1 px-1.5 py-0.5 bg-primary bg-opacity-20 group-hover:bg-white group-hover:bg-opacity-20 rounded-full text-xs">
              {{ stats.pendingChecklists }}
            </span>
          </p>
        </div>
        <svg class="w-5 h-5 opacity-70 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </Link>
    </div>
    
    <!-- Enhanced secondary actions grid -->
    <div class="grid grid-cols-2 gap-2 sm:gap-3">
      <Link
        :href="route('missions.completed')"
        class="flex flex-col items-center p-3 bg-gradient-to-br from-success-bg to-green-100 rounded-lg hover:shadow-md transition-all duration-200 transform hover:scale-105 active:scale-95 touch-manipulation group"
      >
        <div class="p-2 bg-success-text bg-opacity-10 group-hover:bg-opacity-20 rounded-lg mb-2 transition-all duration-200">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <span class="text-xs font-medium text-center text-success-text">View History</span>
      </Link>
      
      <button
        @click="openCamera"
        class="flex flex-col items-center p-3 bg-gradient-to-br from-info-bg to-blue-100 rounded-lg hover:shadow-md transition-all duration-200 transform hover:scale-105 active:scale-95 touch-manipulation group"
      >
        <div class="p-2 bg-info-text bg-opacity-10 group-hover:bg-opacity-20 rounded-lg mb-2 transition-all duration-200">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <span class="text-xs font-medium text-center text-info-text">Quick Photo</span>
      </button>
      
      <button
        @click="callSupport"
        class="flex flex-col items-center p-3 bg-gradient-to-br from-warning-bg to-yellow-100 rounded-lg hover:shadow-md transition-all duration-200 transform hover:scale-105 active:scale-95 touch-manipulation group"
      >
        <div class="p-2 bg-warning-text bg-opacity-10 group-hover:bg-opacity-20 rounded-lg mb-2 transition-all duration-200">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
        </div>
        <span class="text-xs font-medium text-center text-warning-text">Call Support</span>
      </button>
      
      <button
        @click="reportIssue"
        class="flex flex-col items-center p-3 bg-gradient-to-br from-error-bg to-red-100 rounded-lg hover:shadow-md transition-all duration-200 transform hover:scale-105 active:scale-95 touch-manipulation group"
      >
        <div class="p-2 bg-error-text bg-opacity-10 group-hover:bg-opacity-20 rounded-lg mb-2 transition-all duration-200">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-error-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
        </div>
        <span class="text-xs font-medium text-center text-error-text">Report Issue</span>
      </button>
    </div>
    
    <!-- Enhanced emergency contact -->
    <div class="mt-4 sm:mt-6 p-3 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-lg">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="p-1 bg-red-100 rounded-full mr-2">
            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-red-800">Emergency</span>
            <p class="text-xs text-red-600">24/7 Support</p>
          </div>
        </div>
        <a
          href="tel:+33123456789"
          class="text-sm bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-all duration-200 touch-manipulation transform hover:scale-105 active:scale-95 font-medium shadow-md"
        >
          📞 Call Now
        </a>
      </div>
    </div>
    
    <!-- Enhanced offline actions -->
    <div v-if="isOffline" class="mt-4 p-3 bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg animate-pulse">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="p-1 bg-yellow-100 rounded-full mr-2">
            <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <div>
            <span class="text-sm font-medium text-yellow-800">You're offline</span>
            <p class="text-xs text-yellow-600">Limited functionality</p>
          </div>
        </div>
        <button
          @click="syncData"
          class="text-sm bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-all duration-200 touch-manipulation transform hover:scale-105 active:scale-95 font-medium shadow-md"
        >
          🔄 Sync Data
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      assignedCount: 0,
      pendingChecklists: 0,
    }),
  },
});

const emit = defineEmits(['open-camera', 'call-support', 'report-issue', 'sync-data']);

const isOffline = ref(!navigator.onLine);

const openCamera = () => {
  if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
    emit('open-camera');
  } else {
    alert('Camera not available on this device');
  }
};

const callSupport = () => {
  // Try to make a phone call, fallback to showing number
  const phoneNumber = '+33123456789';
  if ('tel:' in window.location) {
    window.location.href = `tel:${phoneNumber}`;
  } else {
    emit('call-support', phoneNumber);
  }
};

const reportIssue = () => {
  emit('report-issue');
};

const syncData = () => {
  emit('sync-data');
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