<template>
    <Head title="Admin Dashboard" />

    <DashboardAdmin>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-text-primary">
                        Welcome back, {{ $page.props.auth.user.name }}!
                    </h2>
                    <p class="text-text-secondary mt-1">
                        Here's what's happening with your properties today.
                    </p>
                </div>
            </div>
        </template>

        <ErrorBoundary fallback-message="Failed to load admin dashboard">
            <LoadingSpinner v-if="loading" message="Loading dashboard..." />
            
            <div v-else class="space-y-8">
                <!-- Error Display -->
                <div v-if="error" class="bg-error-bg border border-error-border text-error-text px-4 py-3 rounded">
                    <strong>Error:</strong> {{ error }}
                </div>

                <!-- Statistics Grid -->
                <LazyDashboardComponents.StatsGrid :stats="safeStats" />

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Activity -->
                    <LazyDashboardComponents.RecentActivity
                        :activities="recentActivities"
                        @refresh="refreshActivities"
                    />

                    <!-- System Health -->
                    <LazyDashboardComponents.SystemHealth
                        :health="systemHealth"
                        @refresh="refreshSystemHealth"
                        @view-error="viewErrorDetails"
                    />
                </div>

                <!-- Checker Management -->
                <LazyDashboardComponents.CheckerManagement
                    :checkers="checkers"
                    @refresh="refreshCheckers"
                    @create="createChecker"
                    @update="updateChecker"
                    @toggle-status="toggleCheckerStatus"
                />

                <!-- Recent Missions Table -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg sm:text-xl font-bold text-text-primary">
                            Recent Missions
                        </h3>
                        <Link 
                            :href="route('missions.index')"
                            class="text-primary hover:text-primary-dark text-sm font-medium"
                        >
                            View All →
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="py-3 px-2 sm:px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Mission ID
                                    </th>
                                    <th class="py-3 px-2 sm:px-4 text-xs font-medium text-text-secondary uppercase tracking-wider hidden sm:table-cell">
                                        Property Address
                                    </th>
                                    <th class="py-3 px-2 sm:px-4 text-xs font-medium text-text-secondary uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="py-3 px-2 sm:px-4 text-xs font-medium text-text-secondary uppercase tracking-wider hidden md:table-cell">
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="mission in safeRecentMissions.slice(0, 5)"
                                    :key="mission.id"
                                    class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-150"
                                >
                                    <td class="py-3 sm:py-4 px-2 sm:px-4 text-sm text-text-primary">
                                        <div class="font-medium">#{{ mission.id }}</div>
                                        <div class="sm:hidden text-xs text-text-secondary mt-1">
                                            {{ mission.address || 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="py-3 sm:py-4 px-2 sm:px-4 text-sm text-text-primary hidden sm:table-cell">
                                        {{ mission.address || 'N/A' }}
                                    </td>
                                    <td class="py-3 sm:py-4 px-2 sm:px-4">
                                        <span
                                            :class="getStatusClass(mission.status)"
                                            class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ formatStatus(mission.status) }}
                                        </span>
                                        <div class="md:hidden text-xs text-text-secondary mt-1">
                                            {{ formatDate(mission.created_at) }}
                                        </div>
                                    </td>
                                    <td class="py-3 sm:py-4 px-2 sm:px-4 text-sm text-text-primary hidden md:table-cell">
                                        {{ formatDate(mission.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="safeRecentMissions.length === 0">
                                    <td colspan="4" class="py-8 text-center text-text-secondary">
                                        No recent missions to display
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </ErrorBoundary>
    </DashboardAdmin>
</template>

<script setup>
import DashboardAdmin from "@/Layouts/DashboardAdmin.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { onMounted, computed, ref } from 'vue';
import ErrorBoundary from '@/Components/ErrorBoundary.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { LazyDashboardComponents } from '@/utils/lazyLoading';
import { validateStats, validateMissions, validateAndFormatDate } from '@/utils/dataValidation';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalMissions: 0,
            assignedMissions: 0,
            completedMissions: 0,
            activeCheckers: 0,
            onlineCheckers: 0,
            missionTrend: 0
        }),
    },
    recentMissions: {
        type: Array,
        default: () => [],
    },
    checkers: {
        type: Array,
        default: () => [],
    },
    recentActivities: {
        type: Array,
        default: () => [],
    },
    systemHealth: {
        type: Object,
        default: () => ({
            database: { status: 'unknown' },
            api: { status: 'unknown' },
            storage: { status: 'unknown' },
            queue: { status: 'unknown' },
            recent_errors: [],
            performance: null
        }),
    },
    error: {
        type: String,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

// Reactive data
const recentActivities = ref(props.recentActivities || []);
const checkers = ref(props.checkers || []);
const systemHealth = ref(props.systemHealth || {});

// Computed properties with safe fallbacks
const safeStats = computed(() => validateStats(props.stats));
const safeRecentMissions = computed(() => validateMissions(props.recentMissions));

onMounted(() => {
    if (process.env.NODE_ENV === 'development') {
        console.log('Admin Dashboard mounted');
        console.log('Props:', props);
        console.log('Stats:', safeStats.value);
        console.log('Recent Missions:', safeRecentMissions.value);
    }
    
    if (props.error) {
        console.error('Dashboard Error:', props.error);
    }

    // Initialize with mock data if not provided
    if (recentActivities.value.length === 0) {
        recentActivities.value = generateMockActivities();
    }
    
    if (Object.keys(systemHealth.value).length === 0) {
        systemHealth.value = generateMockSystemHealth();
    }
});

// Activity management methods
const refreshActivities = async () => {
    try {
        // In a real implementation, this would fetch from an API
        // For now, we'll generate new mock data
        recentActivities.value = generateMockActivities();
    } catch (error) {
        console.error('Failed to refresh activities:', error);
    }
};

const generateMockActivities = () => {
    const activities = [
        {
            id: 1,
            type: 'mission_completed',
            description: 'Mission #123 completed at 15 Rue de la Paix',
            created_at: new Date(Date.now() - 1000 * 60 * 15), // 15 minutes ago
            user: { name: 'John Checker' },
            metadata: { address: '15 Rue de la Paix', duration: '45 minutes' }
        },
        {
            id: 2,
            type: 'mission_assigned',
            description: 'Mission #124 assigned to Marie Dupont',
            created_at: new Date(Date.now() - 1000 * 60 * 30), // 30 minutes ago
            user: { name: 'Admin User' },
            metadata: { address: '22 Avenue des Champs' }
        },
        {
            id: 3,
            type: 'checker_created',
            description: 'New checker account created for Pierre Martin',
            created_at: new Date(Date.now() - 1000 * 60 * 60), // 1 hour ago
            user: { name: 'Admin User' }
        },
        {
            id: 4,
            type: 'mission_incident',
            description: 'Incident reported at Mission #122',
            created_at: new Date(Date.now() - 1000 * 60 * 90), // 1.5 hours ago
            user: { name: 'Sophie Checker' },
            metadata: { address: '8 Boulevard Saint-Germain' }
        },
        {
            id: 5,
            type: 'system_alert',
            description: 'High queue processing time detected',
            created_at: new Date(Date.now() - 1000 * 60 * 120), // 2 hours ago
            user: null
        }
    ];
    return activities;
};

// System health management methods
const refreshSystemHealth = async () => {
    try {
        // In a real implementation, this would fetch from an API
        systemHealth.value = generateMockSystemHealth();
    } catch (error) {
        console.error('Failed to refresh system health:', error);
    }
};

const generateMockSystemHealth = () => {
    return {
        database: {
            status: 'healthy',
            response_time: 45
        },
        api: {
            status: 'healthy',
            active_connections: 23
        },
        storage: {
            status: 'warning',
            disk_usage: '78%'
        },
        queue: {
            status: 'healthy',
            pending_jobs: 5
        },
        recent_errors: [
            {
                id: 1,
                message: 'Failed to process signature upload',
                created_at: new Date(Date.now() - 1000 * 60 * 30),
                context: 'Signature Service'
            }
        ],
        performance: {
            avg_response_time: 245,
            requests_per_minute: 127,
            uptime: 99.8,
            memory_usage: 512
        }
    };
};

const viewErrorDetails = (error) => {
    // In a real implementation, this would show a detailed error modal
    console.log('View error details:', error);
    alert(`Error Details:\n${error.message}\nTime: ${error.created_at}\nContext: ${error.context}`);
};

// Checker management methods
const refreshCheckers = async () => {
    try {
        router.reload({ only: ['checkers'] });
    } catch (error) {
        console.error('Failed to refresh checkers:', error);
    }
};

const createChecker = async (formData) => {
    try {
        await router.post(route('admin.checkers.store'), formData);
    } catch (error) {
        console.error('Failed to create checker:', error);
        throw error;
    }
};

const updateChecker = async (checkerId, formData) => {
    try {
        await router.put(route('admin.checkers.update', checkerId), formData);
    } catch (error) {
        console.error('Failed to update checker:', error);
        throw error;
    }
};

const toggleCheckerStatus = async (checker) => {
    try {
        const newStatus = checker.status === 'active' ? 'inactive' : 'active';
        await router.patch(route('admin.checkers.toggle-status', checker.id), {
            status: newStatus
        });
    } catch (error) {
        console.error('Failed to toggle checker status:', error);
        throw error;
    }
};

// Helper methods for mission table
const getStatusClass = (status) => {
    const statusClasses = {
        completed: "bg-success-bg text-success-text",
        in_progress: "bg-info-bg text-info-text",
        assigned: "bg-warning-bg text-warning-text",
        unassigned: "bg-gray-100 text-gray-800",
        incident: "bg-error-bg text-error-text",
    };
    return statusClasses[status] || "bg-gray-100 text-gray-800";
};

const formatStatus = (status) => {
    const statusLabels = {
        completed: "Completed",
        in_progress: "In Progress",
        assigned: "Assigned",
        unassigned: "Unassigned",
        incident: "Incident",
    };
    return statusLabels[status] || status;
};

const formatDate = (dateString) => {
    return validateAndFormatDate(dateString);
};
</script>