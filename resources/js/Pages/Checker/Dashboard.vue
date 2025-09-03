<template>
    <Head title="Checker Dashboard" />

    <DashboardChecker>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-primary">
                        Welcome back, {{ $page.props.auth.user.name }}!
                    </h2>
                    <p class="text-text-secondary mt-1 text-sm sm:text-base">
                        Here's your mission overview for today.
                    </p>
                </div>
                <!-- Mobile sync indicator -->
                <div class="flex items-center mt-2 sm:mt-0">
                    <div v-if="isOffline" class="flex items-center text-yellow-600 text-sm">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        Offline Mode
                    </div>
                    <div v-else-if="syncStatus === 'syncing'" class="flex items-center text-blue-600 text-sm">
                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Syncing...
                    </div>
                </div>
            </div>
        </template>

        <ErrorBoundary fallback-message="Failed to load checker dashboard">
        <div class="py-3 sm:py-4 lg:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 space-y-3 sm:space-y-4 lg:space-y-6">
                <!-- Urgent Missions Component -->
                <LazyDashboardComponents.UrgentMissions
                    v-if="urgentMissions.length > 0"
                    :missions="urgentMissions"
                    @start-mission="handleStartMission"
                />

                <!-- Statistics Cards Component -->
                <LazyDashboardComponents.StatsCards :stats="dashboardStats" />

                <!-- Main Content Grid - Mobile-first responsive -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
                    <!-- Today's Schedule Component -->
                    <div class="xl:col-span-2 order-2 xl:order-1">
                        <LazyDashboardComponents.TodaySchedule
                            :missions="getTodaySchedule()"
                            @start-mission="handleStartMission"
                            @navigate-to-mission="handleNavigateToMission"
                        />
                    </div>

                    <!-- Quick Actions & Weekly Performance -->
                    <div class="space-y-3 sm:space-y-4 order-1 xl:order-2">
                        <!-- Quick Actions Component -->
                        <LazyDashboardComponents.QuickActions
                            :stats="quickActionStats"
                            @open-camera="handleOpenCamera"
                            @call-support="handleCallSupport"
                            @report-issue="handleReportIssue"
                            @sync-data="handleSyncData"
                        />

                        <!-- Weekly Performance - Hidden on mobile, shown on tablet+ -->
                        <div class="hidden sm:block bg-white rounded-xl shadow-md p-4 lg:p-6">
                            <h3 class="text-lg font-bold text-text-primary mb-4">This Week</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-text-secondary">Missions Completed</span>
                                    <span class="font-semibold text-text-primary">{{ getWeeklyCompleted() }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-text-secondary">Average Rating</span>
                                    <div class="flex items-center">
                                        <span class="font-semibold text-text-primary mr-1">{{ getAverageRating() }}</span>
                                        <div class="flex">
                                            <svg v-for="i in 5" :key="i" class="w-3 h-3 sm:w-4 sm:h-4" :class="i <= Math.floor(getAverageRating()) ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-text-secondary">On-time Rate</span>
                                    <span class="font-semibold text-success-text">{{ getOnTimeRate() }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Completed Missions - Mobile-optimized -->
                <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 lg:p-6">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 lg:mb-6">
                        <h3 class="text-lg sm:text-xl font-bold text-text-primary mb-2 sm:mb-0">Recent Completed</h3>
                        <Link
                            :href="route('missions.completed')"
                            class="text-sm text-primary hover:underline font-medium touch-manipulation"
                        >
                            View All →
                        </Link>
                    </div>
                    
                    <!-- Mobile card view -->
                    <div class="block sm:hidden space-y-3">
                        <div 
                            v-for="mission in getRecentCompleted().slice(0, 3)" 
                            :key="mission.id"
                            class="bg-gray-50 rounded-lg p-3 touch-manipulation"
                            @click="$inertia.visit(route('missions.show', mission.id))"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-text-primary text-sm">{{ mission.address }}</h4>
                                <span class="text-xs bg-success-bg text-success-text px-2 py-1 rounded-full">
                                    {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center text-xs text-text-secondary">
                                <span>{{ formatDate(mission.completed_at) }}</span>
                                <div v-if="mission.rating" class="flex items-center">
                                    <span class="mr-1">{{ mission.rating }}</span>
                                    <div class="flex">
                                        <svg v-for="i in Math.min(mission.rating, 5)" :key="i" class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="getRecentCompleted().length === 0" class="text-center py-6 text-text-secondary">
                            <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm">No completed missions yet</p>
                        </div>
                    </div>
                    
                    <!-- Desktop table view -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="py-3 px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">Property</th>
                                    <th class="py-3 px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">Type</th>
                                    <th class="py-3 px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">Completed</th>
                                    <th class="py-3 px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">Rating</th>
                                    <th class="py-3 px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="mission in getRecentCompleted()"
                                    :key="mission.id"
                                    class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150"
                                >
                                    <td class="py-4 px-4 text-sm text-text-primary">
                                        {{ mission.address }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-text-primary">
                                        {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }}
                                    </td>
                                    <td class="py-4 px-4 text-sm text-text-primary">
                                        {{ formatDate(mission.completed_at) }}
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center">
                                            <span class="text-sm text-text-primary mr-1">{{ mission.rating || 'N/A' }}</span>
                                            <div v-if="mission.rating" class="flex">
                                                <svg v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= mission.rating ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <Link
                                            :href="route('missions.show', mission.id)"
                                            class="text-primary hover:text-accent text-sm font-medium touch-manipulation"
                                        >
                                            View Details
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="getRecentCompleted().length === 0">
                                    <td colspan="5" class="py-8 text-center text-text-secondary">
                                        No completed missions to display
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        </ErrorBoundary>
    </DashboardChecker>
</template>

<script setup>
import DashboardChecker from '@/Layouts/DashboardChecker.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import ErrorBoundary from '@/Components/ErrorBoundary.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import OfflineService from '@/Services/OfflineService.js';
import { LazyDashboardComponents } from '@/utils/lazyLoading';

const props = defineProps({
    assignedMissions: {
        type: Array,
        required: true,
        default: () => [],
    },
    completedMissionsCount: {
        type: Number,
        required: true,
        default: 0,
    },
    pendingChecklistsCount: {
        type: Number,
        required: true,
        default: 0,
    },
    completedMissions: {
        type: Array,
        default: () => [],
    },
    weeklyStats: {
        type: Object,
        default: () => ({
            completed: 0,
            averageRating: 0,
            onTimeRate: 0,
        }),
    },
});

// Reactive state
const isOffline = ref(!navigator.onLine);
const syncStatus = ref('idle'); // 'idle', 'syncing', 'error'

// Computed properties for urgent missions
const urgentMissions = computed(() => {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    return props.assignedMissions.filter(mission => {
        const scheduledDate = new Date(mission.scheduled_at);
        return scheduledDate <= tomorrow && mission.status !== 'completed';
    });
});

// Dashboard stats for components
const dashboardStats = computed(() => ({
    assigned: props.assignedMissions.length,
    completed: props.completedMissionsCount,
    pending: props.pendingChecklistsCount,
    performanceScore: getPerformanceScore(),
    todayCount: getTodayMissions(),
    completionRate: getCompletionRate(),
    trend: getTrend(),
    urgentPending: Math.min(props.pendingChecklistsCount, 3),
}));

const quickActionStats = computed(() => ({
    assignedCount: props.assignedMissions.length,
    pendingChecklists: props.pendingChecklistsCount,
}));

// Helper methods for dashboard calculations
const getTodayMissions = () => {
    const today = new Date().toDateString();
    return props.assignedMissions.filter(mission => 
        new Date(mission.scheduled_at).toDateString() === today
    ).length;
};

const getCompletionRate = () => {
    const totalAssigned = props.assignedMissions.length + props.completedMissionsCount;
    if (totalAssigned === 0) return 0;
    return Math.round((props.completedMissionsCount / totalAssigned) * 100);
};

const getPerformanceScore = () => {
    // Mock performance score based on completion rate and on-time delivery
    const completionRate = getCompletionRate();
    const onTimeRate = getOnTimeRate();
    return Math.round((completionRate + onTimeRate) / 2);
};

const getTodaySchedule = () => {
    const today = new Date().toDateString();
    return props.assignedMissions
        .filter(mission => new Date(mission.scheduled_at).toDateString() === today)
        .sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))
        .slice(0, 5);
};

const getRecentCompleted = () => {
    return props.completedMissions
        .sort((a, b) => new Date(b.completed_at) - new Date(a.completed_at))
        .slice(0, 5);
};

const getWeeklyCompleted = () => {
    return props.weeklyStats.completed || 0;
};

const getAverageRating = () => {
    return props.weeklyStats.averageRating || 4.2;
};

const getOnTimeRate = () => {
    return props.weeklyStats.onTimeRate || 95;
};

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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString("en-US", {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatTime = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleTimeString("en-US", {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getTrend = () => {
    // Mock trend calculation - in real app, compare with previous period
    const completionRate = getCompletionRate();
    if (completionRate > 85) return 'up';
    if (completionRate < 70) return 'down';
    return 'stable';
};

// Event handlers
const handleStartMission = (mission) => {
    // Update mission status and navigate
    router.patch(route('missions.start', mission.id), {}, {
        onSuccess: () => {
            router.visit(route('missions.show', mission.id));
        },
        onError: (errors) => {
            console.error('Failed to start mission:', errors);
        }
    });
};

const handleNavigateToMission = (mission) => {
    router.visit(route('missions.show', mission.id));
};

const handleOpenCamera = () => {
    if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
        // Open camera interface - could be a modal or separate page
        console.log('Opening camera...');
        // In a real implementation, this would open a camera modal
    } else {
        alert('Camera not available on this device');
    }
};

const handleCallSupport = (phoneNumber) => {
    if (phoneNumber) {
        window.location.href = `tel:${phoneNumber}`;
    } else {
        alert('Support: +33 1 23 45 67 89');
    }
};

const handleReportIssue = () => {
    // Navigate to issue reporting page or open modal
    router.visit(route('incidents.create'));
};

const handleSyncData = async () => {
    if (isOffline.value) {
        alert('Cannot sync while offline. Please check your connection.');
        return;
    }
    
    syncStatus.value = 'syncing';
    try {
        await OfflineService.syncPendingData();
        syncStatus.value = 'idle';
        // Refresh the page data
        router.reload({ only: ['assignedMissions', 'completedMissions', 'weeklyStats'] });
    } catch (error) {
        console.error('Sync failed:', error);
        syncStatus.value = 'error';
        setTimeout(() => {
            syncStatus.value = 'idle';
        }, 3000);
    }
};

// Online/offline detection
const handleOnline = () => {
    isOffline.value = false;
    // Auto-sync when coming back online
    handleSyncData();
};

const handleOffline = () => {
    isOffline.value = true;
};

// Lifecycle hooks
onMounted(async () => {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
    
    // Cache missions for offline access
    if (props.assignedMissions.length > 0) {
        await OfflineService.cacheMissions(props.assignedMissions);
    }
    
    // Preload critical missions for offline access
    await OfflineService.preloadCriticalMissions();
    
    // If offline, load cached data
    if (isOffline.value) {
        await loadOfflineData();
    }
});

onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
});

// Load offline data when offline
const loadOfflineData = async () => {
    try {
        const cachedMissions = await OfflineService.getCachedMissions();
        const urgentMissions = await OfflineService.getUrgentCachedMissions();
        const todayMissions = await OfflineService.getTodaysCachedMissions();
        
        console.log('Loaded offline data:', {
            total: cachedMissions.length,
            urgent: urgentMissions.length,
            today: todayMissions.length
        });
        
        // You could update reactive data here if needed
        // This is just for demonstration of offline capability
    } catch (error) {
        console.error('Failed to load offline data:', error);
    }
};
</script>