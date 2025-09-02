<template>
    <Head title="Admin Dashboard" />

    <DashboardAdmin>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">
                        Welcome back, Admin!
                    </h2>
                    <p class="text-gray-600 mt-1">
                        Here's what's happening with your properties today.
                    </p>
                </div>
                <button
                    class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-colors duration-200 flex items-center gap-2"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Schedule New Mission
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Missions Requiring Attention -->
                <div
                    class="bg-card rounded-xl shadow p-6 border border-red-200"
                >
                    <h3
                        class="text-xl font-bold text-red-700 flex items-center mb-4"
                    >
                        <svg
                            class="w-6 h-6 mr-2"
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
                        Missions Requiring Attention
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Link
                            href="#"
                            class="block p-4 bg-red-50 rounded-lg hover:bg-red-100 transition-colors duration-200"
                        >
                            <div class="flex items-center text-red-600">
                                <svg
                                    class="w-6 h-6 mr-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <h4 class="font-semibold">Overdue Missions</h4>
                            </div>
                            <p class="text-3xl font-bold text-red-800 mt-2">
                                {{ getOverdueMissions() }}
                            </p>
                            <p class="text-xs text-red-600">View Details</p>
                        </Link>
                        <Link
                            href="#"
                            class="block p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors duration-200"
                        >
                            <div class="flex items-center text-orange-600">
                                <svg
                                    class="w-6 h-6 mr-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                    />
                                </svg>
                                <h4 class="font-semibold">
                                    Unassigned Missions
                                </h4>
                            </div>
                            <p class="text-3xl font-bold text-orange-800 mt-2">
                                {{ getUnassignedMissions() }}
                            </p>
                            <p class="text-xs text-orange-600">View Details</p>
                        </Link>
                        <Link
                            href="#"
                            class="block p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors duration-200"
                        >
                            <div class="flex items-center text-yellow-600">
                                <svg
                                    class="w-6 h-6 mr-3"
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
                                <h4 class="font-semibold">
                                    Critical Incidents
                                </h4>
                            </div>
                            <p class="text-3xl font-bold text-yellow-800 mt-2">
                                {{ getCriticalIncidents() }}
                            </p>
                            <p class="text-xs text-yellow-600">View Details</p>
                        </Link>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
                >
                    <div
                        class="bg-card rounded-xl shadow p-6 border-l-4 border-[var(--primary-color)] hover:shadow-lg transition-all duration-200 cursor-pointer group"
                        @click="navigateToMissions"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary group-hover:text-primary transition-colors">
                                    Active Missions
                                </h3>
                                <p class="text-4xl font-extrabold text-primary mt-2">
                                    {{ stats.totalMissions || 0 }}
                                </p>
                                <div class="flex items-center mt-2 text-sm">
                                    <span :class="getMissionTrendClass()" class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="getMissionTrend() >= 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                        </svg>
                                        {{ Math.abs(getMissionTrend()) }}%
                                    </span>
                                    <span class="ml-2 text-secondary">vs last month</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div
                        class="bg-card rounded-xl shadow p-6 border-l-4 border-yellow-400 hover:shadow-lg transition-all duration-200 cursor-pointer group"
                        @click="navigateToIncidents"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary group-hover:text-primary transition-colors">
                                    Open Incidents
                                </h3>
                                <p class="text-4xl font-extrabold text-primary mt-2">
                                    {{ getOpenIncidents() }}
                                </p>
                                <div class="flex items-center mt-2 text-sm">
                                    <span :class="getIncidentTrendClass()" class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="getIncidentTrendValue() <= 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                        </svg>
                                        {{ Math.abs(getIncidentTrendValue()) }}%
                                    </span>
                                    <span class="ml-2 text-secondary">vs last month</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div
                        class="bg-card rounded-xl shadow p-6 border-l-4 border-green-500 hover:shadow-lg transition-all duration-200 cursor-pointer group"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary group-hover:text-primary transition-colors">
                                    Avg. Completion Time
                                </h3>
                                <p class="text-4xl font-extrabold text-primary mt-2">
                                    {{ getAvgCompletionTime() }}
                                </p>
                                <div class="flex items-center mt-2 text-sm">
                                    <span class="text-green-500 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                        </svg>
                                        15%
                                    </span>
                                    <span class="ml-2 text-secondary">improvement</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/50">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-card rounded-xl shadow p-6 border-l-4 border-purple-500 hover:shadow-lg transition-all duration-200 cursor-pointer group"
                        @click="navigateToCheckers"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-secondary group-hover:text-primary transition-colors">
                                    Active Checkers
                                </h3>
                                <p class="text-4xl font-extrabold text-primary mt-2">
                                    {{ stats.activeCheckers || 0 }}
                                </p>
                                <div class="flex items-center mt-2 text-sm">
                                    <span class="text-blue-500 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        {{ stats.onlineCheckers || 0 }}
                                    </span>
                                    <span class="ml-2 text-secondary">online now</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/50">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Recent Incidents -->
                    <div class="lg:col-span-2 bg-card rounded-xl shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-primary">
                                Recent Incidents
                            </h3>
                            <Link
                                href="#"
                                class="text-sm font-medium text-[var(--primary-color)] hover:text-[var(--accent-color)]"
                                >View all</Link
                            >
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-custom">
                                        <th
                                            class="py-3 px-4 text-xs font-medium text-secondary uppercase tracking-wider"
                                        >
                                            Incident ID
                                        </th>
                                        <th
                                            class="py-3 px-4 text-xs font-medium text-secondary uppercase tracking-wider"
                                        >
                                            Property Address
                                        </th>
                                        <th
                                            class="py-3 px-4 text-xs font-medium text-secondary uppercase tracking-wider"
                                        >
                                            Status
                                        </th>
                                        <th
                                            class="py-3 px-4 text-xs font-medium text-secondary uppercase tracking-wider"
                                        >
                                            Reported Date
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="mission in recentMissions.slice(
                                            0,
                                            3
                                        )"
                                        :key="mission.id"
                                        class="border-b border-custom hover:bg-table-hover transition-colors duration-150"
                                    >
                                        <td
                                            class="py-4 px-4 text-sm text-secondary"
                                        >
                                            #{{ mission.id }}
                                        </td>
                                        <td
                                            class="py-4 px-4 text-sm text-secondary"
                                        >
                                            {{ mission.address }}
                                        </td>
                                        <td class="py-4 px-4">
                                            <span
                                                :class="
                                                    getStatusClass(
                                                        mission.status
                                                    )
                                                "
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                            >
                                                {{ mission.status }}
                                            </span>
                                        </td>
                                        <td
                                            class="py-4 px-4 text-sm text-secondary"
                                        >
                                            {{ formatDate(mission.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="recentMissions.length === 0">
                                        <td
                                            colspan="4"
                                            class="py-8 text-center text-secondary"
                                        >
                                            No recent incidents to display
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Analytics -->
                    <div class="bg-card rounded-xl shadow p-6">
                        <h3 class="text-xl font-bold text-primary mb-4">
                            Analytics
                        </h3>
                        <div class="mb-6">
                            <div class="flex justify-between items-baseline">
                                <p class="text-sm font-medium text-secondary">
                                    Mission Completion Rate
                                </p>
                                <p class="text-2xl font-bold text-green-600">
                                    {{ getCompletionRate() }}%
                                </p>
                            </div>
                            <div
                                class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mt-2"
                            >
                                <div
                                    class="bg-green-500 h-2.5 rounded-full"
                                    :style="{
                                        width: getCompletionRate() + '%',
                                    }"
                                ></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-baseline">
                                <p class="text-sm font-medium text-secondary">
                                    Incident Trends
                                </p>
                                <p class="text-2xl font-bold text-red-500">
                                    {{ getIncidentTrends() }}
                                </p>
                            </div>
                            <div
                                class="h-40 mt-2 flex items-end justify-center"
                            >
                                <svg
                                    class="w-full h-full"
                                    fill="none"
                                    preserveAspectRatio="none"
                                    viewBox="0 0 472 150"
                                >
                                    <path
                                        d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25"
                                        stroke="var(--primary-color)"
                                        stroke-linecap="round"
                                        stroke-width="3"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link
                        :href="route('admin.checkers')"
                        class="bg-card rounded-xl shadow p-6 hover:shadow-lg transition-shadow duration-200"
                    >
                        <div class="flex items-center">
                            <div
                                class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/50"
                            >
                                <svg
                                    class="w-6 h-6 text-blue-600 dark:text-blue-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold text-primary">
                                    Manage Checkers
                                </h4>
                                <p class="text-sm text-secondary">
                                    View and manage checker accounts
                                </p>
                            </div>
                        </div>
                    </Link>

                    <Link
                        :href="route('admin.analytics.data')"
                        class="bg-card rounded-xl shadow p-6 hover:shadow-lg transition-shadow duration-200"
                    >
                        <div class="flex items-center">
                            <div
                                class="p-3 rounded-full bg-green-100 dark:bg-green-900/50"
                            >
                                <svg
                                    class="w-6 h-6 text-green-600 dark:text-green-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold text-primary">
                                    Analytics
                                </h4>
                                <p class="text-sm text-secondary">
                                    View detailed analytics and reports
                                </p>
                            </div>
                        </div>
                    </Link>

                    <Link
                        :href="route('missions.create')"
                        class="bg-card rounded-xl shadow p-6 hover:shadow-lg transition-shadow duration-200"
                    >
                        <div class="flex items-center">
                            <div
                                class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/50"
                            >
                                <svg
                                    class="w-6 h-6 text-purple-600 dark:text-purple-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold text-primary">
                                    Create Mission
                                </h4>
                                <p class="text-sm text-secondary">
                                    Schedule a new property mission
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import DashboardAdmin from "@/Layouts/DashboardAdmin.vue";
import { Head, Link } from "@inertiajs/vue3";
import { onMounted } from "vue";
import { useTheme } from "@/Composables/useTheme";

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    recentMissions: {
        type: Array,
        required: true,
        default: () => [],
    },
});

// Helper methods for dashboard calculations
const getOverdueMissions = () => {
    // Calculate overdue missions based on current date
    const now = new Date();
    return props.recentMissions.filter(
        (mission) =>
            new Date(mission.scheduled_at) < now &&
            mission.status !== "completed"
    ).length;
};

const getUnassignedMissions = () => {
    return props.recentMissions.filter(
        (mission) => mission.status === "unassigned"
    ).length;
};

const getCriticalIncidents = () => {
    return props.recentMissions.filter(
        (mission) => mission.status === "incident"
    ).length;
};

const getOpenIncidents = () => {
    return props.recentMissions.filter(
        (mission) =>
            mission.status === "incident" || mission.status === "in_progress"
    ).length;
};

const getAvgCompletionTime = () => {
    const completedMissions = props.recentMissions.filter(
        (mission) => mission.status === "completed"
    );
    if (completedMissions.length === 0) return "0 days";

    // Simplified calculation - in real app, you'd calculate actual completion times
    return "2 days";
};

const getCompletionRate = () => {
    if (props.stats.totalMissions === 0) return 0;
    return Math.round(
        (props.stats.completedMissions / props.stats.totalMissions) * 100
    );
};

const getIncidentTrends = () => {
    return getCriticalIncidents();
};

// Enhanced trend calculations
const getMissionTrend = () => {
    // Simulate trend calculation - in real app, this would come from props
    return props.stats.missionTrend || 12;
};

const getMissionTrendClass = () => {
    const trend = getMissionTrend();
    return trend >= 0 ? 'text-green-500' : 'text-red-500';
};

const getIncidentTrendValue = () => {
    // Simulate trend calculation - in real app, this would come from props
    return props.stats.incidentTrend || -8;
};

const getIncidentTrendClass = () => {
    const trend = getIncidentTrendValue();
    return trend <= 0 ? 'text-green-500' : 'text-red-500';
};

// Navigation methods
const navigateToMissions = () => {
    window.location.href = route('missions.index');
};

const navigateToIncidents = () => {
    window.location.href = route('incidents.index');
};

const navigateToCheckers = () => {
    window.location.href = route('admin.checkers');
};

const getStatusClass = (status) => {
    const statusClasses = {
        completed:
            "bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300",
        in_progress:
            "bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300",
        assigned:
            "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300",
        unassigned:
            "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300",
        incident:
            "bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300",
    };
    return (
        statusClasses[status] ||
        "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
    );
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("fr-FR");
};

const { initializeTheme } = useTheme();

onMounted(() => {
    initializeTheme();
});
</script>
