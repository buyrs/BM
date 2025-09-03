<template>
    <Head title="Checker Dashboard" />

    <DashboardChecker>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl lg:text-3xl font-bold text-primary">
                        Welcome back, {{ $page.props.auth.user.name }}!
                    </h2>
                    <p class="text-text-secondary mt-1 text-sm sm:text-base">
                        Here's your mission overview for today.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-6 lg:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                <!-- Priority Missions Alert -->
                <div v-if="urgentMissions.length > 0" class="bg-white rounded-xl shadow-md p-4 sm:p-6 border-l-4 border-error-border">
                    <div class="flex items-start sm:items-center mb-4">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-error-text mr-2 sm:mr-3 flex-shrink-0 mt-0.5 sm:mt-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <h3 class="text-lg sm:text-xl font-bold text-error-text">
                            Urgent Missions Requiring Attention
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        <div v-for="mission in urgentMissions" :key="mission.id" class="bg-error-bg rounded-lg p-4">
                            <h4 class="font-semibold text-error-text">{{ mission.address }}</h4>
                            <p class="text-sm text-error-text">{{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }}</p>
                            <p class="text-xs text-error-text">Due: {{ formatDate(mission.scheduled_at) }}</p>
                            <Link
                                :href="route('missions.show', mission.id)"
                                class="inline-block mt-2 text-xs bg-error-text text-white px-3 py-1 rounded hover:bg-red-700"
                            >
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <!-- Assigned Missions Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-warning-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-text-secondary">
                                    Assigned Missions
                                </h3>
                                <p class="text-4xl font-extrabold text-warning-text mt-2">
                                    {{ assignedMissions.length }}
                                </p>
                                <div class="flex items-center mt-2">
                                    <span class="text-xs text-text-secondary">
                                        {{ getTodayMissions() }} due today
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-warning-bg">
                                <svg class="w-8 h-8 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Missions Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-success-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-text-secondary">
                                    Completed This Month
                                </h3>
                                <p class="text-4xl font-extrabold text-success-text mt-2">
                                    {{ completedMissionsCount }}
                                </p>
                                <div class="flex items-center mt-2">
                                    <span class="text-xs text-text-secondary">
                                        {{ getCompletionRate() }}% completion rate
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-success-bg">
                                <svg class="w-8 h-8 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Checklists Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-info-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-text-secondary">
                                    Pending Checklists
                                </h3>
                                <p class="text-4xl font-extrabold text-info-text mt-2">
                                    {{ pendingChecklistsCount }}
                                </p>
                                <div class="flex items-center mt-2">
                                    <span class="text-xs text-text-secondary">
                                        Awaiting completion
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-info-bg">
                                <svg class="w-8 h-8 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Score Card -->
                    <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-primary">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-text-secondary">
                                    Performance Score
                                </h3>
                                <p class="text-4xl font-extrabold text-primary mt-2">
                                    {{ getPerformanceScore() }}%
                                </p>
                                <div class="flex items-center mt-2">
                                    <span class="text-xs text-text-secondary">
                                        Based on completion time
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-secondary">
                                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                    <!-- Today's Schedule -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-text-primary">Today's Schedule</h3>
                            <Link
                                :href="route('missions.assigned')"
                                class="text-sm text-primary hover:underline font-medium"
                            >
                                View All Missions →
                            </Link>
                        </div>
                        <div class="space-y-4">
                            <div v-for="mission in getTodaySchedule()" :key="mission.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-150">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full" :class="getStatusColor(mission.status)"></div>
                                        <div>
                                            <h4 class="font-semibold text-text-primary">{{ mission.address }}</h4>
                                            <p class="text-sm text-text-secondary">
                                                {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }} • 
                                                {{ formatTime(mission.scheduled_at) }}
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
                                    <Link
                                        :href="route('missions.show', mission.id)"
                                        class="text-primary hover:text-accent text-sm font-medium"
                                    >
                                        View
                                    </Link>
                                </div>
                            </div>
                            <div v-if="getTodaySchedule().length === 0" class="text-center py-8 text-text-secondary">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-lg font-medium">No missions scheduled for today</p>
                                <p class="text-sm">Enjoy your free day!</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions & Stats -->
                    <div class="space-y-6">
                        <!-- Quick Actions -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h3 class="text-lg font-bold text-text-primary mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <Link
                                    :href="route('missions.assigned')"
                                    class="flex items-center p-3 bg-primary text-white rounded-lg hover:bg-accent transition-colors duration-200"
                                >
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    View My Missions
                                </Link>
                                <Link
                                    :href="route('checklists.index')"
                                    class="flex items-center p-3 bg-secondary text-primary rounded-lg hover:bg-gray-100 transition-colors duration-200"
                                >
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Complete Checklists
                                </Link>
                                <Link
                                    :href="route('missions.completed')"
                                    class="flex items-center p-3 bg-secondary text-primary rounded-lg hover:bg-gray-100 transition-colors duration-200"
                                >
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    View History
                                </Link>
                            </div>
                        </div>

                        <!-- Weekly Performance -->
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h3 class="text-lg font-bold text-text-primary mb-4">This Week</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-text-secondary">Missions Completed</span>
                                    <span class="font-semibold text-text-primary">{{ getWeeklyCompleted() }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-text-secondary">Average Rating</span>
                                    <div class="flex items-center">
                                        <span class="font-semibold text-text-primary mr-1">{{ getAverageRating() }}</span>
                                        <div class="flex">
                                            <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= Math.floor(getAverageRating()) ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
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

                <!-- Recent Completed Missions -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-text-primary">Recent Completed Missions</h3>
                        <Link
                            :href="route('missions.completed')"
                            class="text-sm text-primary hover:underline font-medium"
                        >
                            View All →
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
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
                                            class="text-primary hover:text-accent text-sm font-medium"
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
    </DashboardChecker>
</template>

<script setup>
import DashboardChecker from '@/Layouts/DashboardChecker.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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
</script>