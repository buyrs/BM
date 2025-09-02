<template>
    <div>
        <Head title="Ops Dashboard" />

        <DashboardOps>
            <template #header>
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                        Tableau de Bord Ops
                    </h2>
                    <div class="flex items-center space-x-3">
                        <!-- View Toggle -->
                        <div class="flex bg-gray-100 rounded-lg p-1">
                            <button
                                @click="currentView = 'overview'"
                                :class="[
                                    'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                                    currentView === 'overview' 
                                        ? 'bg-white text-gray-900 shadow-sm' 
                                        : 'text-gray-600 hover:text-gray-900'
                                ]"
                            >
                                Vue d'ensemble
                            </button>
                            <button
                                @click="currentView = 'kanban'"
                                :class="[
                                    'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                                    currentView === 'kanban' 
                                        ? 'bg-white text-gray-900 shadow-sm' 
                                        : 'text-gray-600 hover:text-gray-900'
                                ]"
                            >
                                Kanban
                            </button>
                            <button
                                @click="currentView = 'analytics'"
                                :class="[
                                    'px-3 py-1 text-sm font-medium rounded-md transition-colors',
                                    currentView === 'analytics' 
                                        ? 'bg-white text-gray-900 shadow-sm' 
                                        : 'text-gray-600 hover:text-gray-900'
                                ]"
                            >
                                Analyses
                            </button>
                        </div>
                        
                        <!-- Export Button -->
                        <div class="relative">
                            <button
                                @click="showExportMenu = !showExportMenu"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Exporter
                            </button>
                            
                            <!-- Export Menu -->
                            <div v-if="showExportMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                <div class="py-1">
                                    <button
                                        @click="exportData('csv')"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        Exporter en CSV
                                    </button>
                                    <button
                                        @click="exportData('json')"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        Exporter en JSON
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <Link
                            :href="route('ops.bail-mobilites.create')"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nouveau Bail Mobilité
                        </Link>
                    </div>
                </div>
            </template>

            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <!-- Filters -->
                    <div v-if="currentView !== 'overview'" class="bg-white rounded-xl shadow-sm p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex-1 min-w-64">
                                <input
                                    v-model="filters.search"
                                    @input="debouncedSearch"
                                    type="text"
                                    placeholder="Rechercher par nom, adresse, email..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                            
                            <select
                                v-model="filters.status"
                                @change="applyFilters"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="assigned">Assigné</option>
                                <option value="in_progress">En Cours</option>
                                <option value="completed">Terminé</option>
                                <option value="incident">Incident</option>
                            </select>
                            
                            <select
                                v-model="filters.ops_user_id"
                                @change="applyFilters"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option value="">Tous les Ops</option>
                                <option v-for="user in opsUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                            
                            <select
                                v-model="filters.checker_id"
                                @change="applyFilters"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option value="">Tous les checkers</option>
                                <option v-for="checker in checkers" :key="checker.id" :value="checker.id">
                                    {{ checker.name }}
                                </option>
                            </select>
                            
                            <input
                                v-model="filters.date_from"
                                @change="applyFilters"
                                type="date"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            
                            <input
                                v-model="filters.date_to"
                                @change="applyFilters"
                                type="date"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            
                            <button
                                @click="resetFilters"
                                class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900"
                            >
                                Réinitialiser
                            </button>
                        </div>
                    </div>

                    <!-- Overview View -->
                    <div v-if="currentView === 'overview'">
                        <!-- Enhanced Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Assignés</p>
                                        <p class="text-2xl font-semibold text-gray-900">{{ metrics.basic.assigned }}</p>
                                        <div class="flex items-center mt-2">
                                            <span :class="getChangeClass(metrics.current_month.created - metrics.last_month.created)" class="text-xs font-medium">
                                                {{ getChangeText(metrics.current_month.created - metrics.last_month.created) }}
                                            </span>
                                            <span class="text-xs text-gray-500 ml-1">vs mois dernier</span>
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-full bg-yellow-100">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">En Cours</p>
                                        <p class="text-2xl font-semibold text-gray-900">{{ metrics.basic.in_progress }}</p>
                                        <div class="flex items-center mt-2">
                                            <span class="text-xs text-gray-600">Durée moy: {{ metrics.average_duration }}j</span>
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-full bg-blue-100">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Terminés</p>
                                        <p class="text-2xl font-semibold text-gray-900">{{ metrics.basic.completed }}</p>
                                        <div class="flex items-center mt-2">
                                            <span :class="getChangeClass(metrics.current_month.completed - metrics.last_month.completed)" class="text-xs font-medium">
                                                {{ getChangeText(metrics.current_month.completed - metrics.last_month.completed) }}
                                            </span>
                                            <span class="text-xs text-gray-500 ml-1">ce mois</span>
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-full bg-green-100">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-600">Incidents</p>
                                        <p class="text-2xl font-semibold text-gray-900">{{ metrics.basic.incident }}</p>
                                        <div class="flex items-center mt-2">
                                            <span class="text-xs text-gray-600">Taux: {{ metrics.incident_rate }}%</span>
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-full bg-red-100">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Ouverts:</span>
                                        <span class="font-medium text-red-600">{{ metrics.incidents?.total_open || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Critiques:</span>
                                        <span class="font-medium text-red-800">{{ metrics.incidents?.critical_open || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Aujourd'hui:</span>
                                        <span class="font-medium">{{ metrics.incidents?.detected_today || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Cette semaine:</span>
                                        <span class="font-medium">{{ metrics.incidents?.detected_this_week || 0 }}</span>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <Link 
                                        :href="route('ops.incidents.index')"
                                        class="text-sm text-indigo-600 hover:text-indigo-900 font-medium"
                                    >
                                        Gérer les incidents →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications and Actions -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Pending Notifications -->
                            <NotificationPanel :notifications="pendingNotifications" />

                            <!-- Missions for Validation -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Missions à Valider</h3>
                                <div class="space-y-3">
                                    <div v-for="mission in missionsForValidation" :key="mission.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ mission.bail_mobilite.tenant_name }}</p>
                                            <p class="text-xs text-gray-500">{{ mission.type === 'checkin' ? 'Entrée' : 'Sortie' }} - {{ mission.agent.name }}</p>
                                        </div>
                                        <Link
                                            :href="route('ops.bail-mobilites.show', mission.bail_mobilite.id)"
                                            class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                        >
                                            Valider
                                        </Link>
                                    </div>
                                    <div v-if="missionsForValidation.length === 0" class="text-center py-4 text-gray-500">
                                        Aucune mission à valider
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Checker Performance -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance des Checkers (ce mois)</h3>
                            <div class="space-y-3">
                                <div v-for="checker in metrics.checker_performance" :key="checker.name" class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-900">{{ checker.name }}</span>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 mr-3">{{ checker.missions_completed }} missions</span>
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div 
                                                class="bg-primary-600 h-2 rounded-full" 
                                                :style="{ width: (checker.missions_completed / Math.max(...metrics.checker_performance.map(c => c.missions_completed)) * 100) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="metrics.checker_performance.length === 0" class="text-center py-4 text-gray-500">
                                    Aucune donnée de performance disponible
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Quick View -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Missions Aujourd'hui</h3>
                                <Link
                                    :href="route('ops.calendar')"
                                    class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                                >
                                    Voir Calendrier →
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <div v-for="mission in todayMissions" :key="mission.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ mission.tenant_name }}</p>
                                        <p class="text-xs text-gray-500">{{ mission.type === 'entry' ? 'Entrée' : 'Sortie' }} - {{ mission.scheduled_time || 'Non programmé' }}</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span :class="[
                                            'text-xs px-2 py-1 rounded-full',
                                            mission.status === 'assigned' ? 'bg-yellow-100 text-yellow-800' :
                                            mission.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                            mission.status === 'completed' ? 'bg-green-100 text-green-800' :
                                            'bg-gray-100 text-gray-800'
                                        ]">
                                            {{ mission.status }}
                                        </span>
                                        <Link
                                            :href="route('ops.calendar', { date: mission.scheduled_date })"
                                            class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                        >
                                            Voir
                                        </Link>
                                    </div>
                                </div>
                                <div v-if="todayMissions.length === 0" class="text-center py-4 text-gray-500">
                                    Aucune mission aujourd'hui
                                </div>
                            </div>
                        </div>

                        <!-- Ending Soon -->
                        <div class="bg-white rounded-xl shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Se Terminent Bientôt (10 jours)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="bailMobilite in endingSoon" :key="bailMobilite.id" class="border border-orange-200 rounded-lg p-4 bg-orange-50">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900">{{ bailMobilite.tenant_name }}</h4>
                                        <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">
                                            {{ getRemainingDays(bailMobilite.end_date) }} jours
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">{{ bailMobilite.address }}</p>
                                    <p class="text-xs text-gray-500 mb-3">Fin: {{ formatDate(bailMobilite.end_date) }}</p>
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('ops.bail-mobilites.show', bailMobilite.id)"
                                            class="text-xs bg-primary-600 text-white px-3 py-1 rounded hover:bg-primary-700"
                                        >
                                            Voir Détails
                                        </Link>
                                        <button
                                            v-if="!bailMobilite.exit_mission.agent_id"
                                            @click="assignExit(bailMobilite)"
                                            class="text-xs bg-orange-600 text-white px-3 py-1 rounded hover:bg-orange-700"
                                        >
                                            Assigner Sortie
                                        </button>
                                    </div>
                                </div>
                                <div v-if="endingSoon.length === 0" class="col-span-full text-center py-8 text-gray-500">
                                    Aucun Bail Mobilité se terminant bientôt
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kanban View -->
                    <div v-if="currentView === 'kanban'" class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 min-h-screen">
                            <!-- Assigned Column -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">Assigné</h3>
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ kanbanData.assigned?.length || 0 }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <BailMobiliteCard
                                        v-for="bm in kanbanData.assigned"
                                        :key="bm.id"
                                        :bail-mobilite="bm"
                                        @view-details="viewBailMobilite"
                                        @assign-entry="handleAssignEntry"
                                        @assign-exit="handleAssignExit"
                                        @handle-incident="handleIncident"
                                    />
                                </div>
                            </div>

                            <!-- In Progress Column -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">En Cours</h3>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ kanbanData.in_progress?.length || 0 }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <BailMobiliteCard
                                        v-for="bm in kanbanData.in_progress"
                                        :key="bm.id"
                                        :bail-mobilite="bm"
                                        @view-details="viewBailMobilite"
                                        @assign-entry="handleAssignEntry"
                                        @assign-exit="handleAssignExit"
                                        @handle-incident="handleIncident"
                                    />
                                </div>
                            </div>

                            <!-- Completed Column -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">Terminé</h3>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ kanbanData.completed?.length || 0 }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <BailMobiliteCard
                                        v-for="bm in kanbanData.completed"
                                        :key="bm.id"
                                        :bail-mobilite="bm"
                                        @view-details="viewBailMobilite"
                                        @assign-entry="handleAssignEntry"
                                        @assign-exit="handleAssignExit"
                                        @handle-incident="handleIncident"
                                    />
                                </div>
                            </div>

                            <!-- Incident Column -->
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="font-semibold text-gray-900">Incident</h3>
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full">
                                        {{ kanbanData.incident?.length || 0 }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <BailMobiliteCard
                                        v-for="bm in kanbanData.incident"
                                        :key="bm.id"
                                        :bail-mobilite="bm"
                                        @view-details="viewBailMobilite"
                                        @assign-entry="handleAssignEntry"
                                        @assign-exit="handleAssignExit"
                                        @handle-incident="handleIncident"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics View -->
                    <div v-if="currentView === 'analytics'" class="space-y-6">
                        <!-- Trends Charts -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Monthly Trends -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tendances Mensuelles</h3>
                                <div class="h-64 flex items-end justify-between space-x-2">
                                    <div v-for="month in performanceTrends.monthly" :key="month.month" class="flex-1 flex flex-col items-center">
                                        <div class="w-full space-y-1">
                                            <div 
                                                class="bg-green-500 rounded-t"
                                                :style="{ height: Math.max(4, (month.completed / Math.max(...performanceTrends.monthly.map(m => m.completed)) * 200)) + 'px' }"
                                                :title="`Terminés: ${month.completed}`"
                                            ></div>
                                            <div 
                                                class="bg-blue-500"
                                                :style="{ height: Math.max(4, (month.created / Math.max(...performanceTrends.monthly.map(m => m.created)) * 200)) + 'px' }"
                                                :title="`Créés: ${month.created}`"
                                            ></div>
                                            <div 
                                                class="bg-red-500 rounded-b"
                                                :style="{ height: Math.max(4, (month.incidents / Math.max(...performanceTrends.monthly.map(m => m.incidents)) * 200)) + 'px' }"
                                                :title="`Incidents: ${month.incidents}`"
                                            ></div>
                                        </div>
                                        <span class="text-xs text-gray-600 mt-2 transform -rotate-45 origin-left">{{ month.month }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-center space-x-4 mt-4">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-green-500 rounded mr-2"></div>
                                        <span class="text-xs text-gray-600">Terminés</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-blue-500 rounded mr-2"></div>
                                        <span class="text-xs text-gray-600">Créés</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-red-500 rounded mr-2"></div>
                                        <span class="text-xs text-gray-600">Incidents</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Weekly Trends -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tendances Hebdomadaires (Mois Actuel)</h3>
                                <div class="h-64 flex items-end justify-between space-x-4">
                                    <div v-for="week in performanceTrends.weekly" :key="week.week" class="flex-1 flex flex-col items-center">
                                        <div class="w-full space-y-1">
                                            <div 
                                                class="bg-green-500 rounded-t"
                                                :style="{ height: Math.max(4, (week.completed / Math.max(...performanceTrends.weekly.map(w => w.completed)) * 200)) + 'px' }"
                                                :title="`Terminés: ${week.completed}`"
                                            ></div>
                                            <div 
                                                class="bg-blue-500"
                                                :style="{ height: Math.max(4, (week.created / Math.max(...performanceTrends.weekly.map(w => w.created)) * 200)) + 'px' }"
                                                :title="`Créés: ${week.created}`"
                                            ></div>
                                            <div 
                                                class="bg-red-500 rounded-b"
                                                :style="{ height: Math.max(4, (week.incidents / Math.max(...performanceTrends.weekly.map(w => w.incidents)) * 200)) + 'px' }"
                                                :title="`Incidents: ${week.incidents}`"
                                            ></div>
                                        </div>
                                        <span class="text-xs text-gray-600 mt-2">{{ week.week }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Analytics -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Key Metrics -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Métriques Clés</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Durée moyenne</span>
                                        <span class="font-semibold">{{ metrics.average_duration }} jours</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Taux d'incidents</span>
                                        <span class="font-semibold text-red-600">{{ metrics.incident_rate }}%</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Total ce mois</span>
                                        <span class="font-semibold">{{ metrics.current_month.created }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Terminés ce mois</span>
                                        <span class="font-semibold text-green-600">{{ metrics.current_month.completed }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Distribution -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Répartition des Statuts</h3>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-yellow-500 rounded mr-2"></div>
                                            <span class="text-sm text-gray-600">Assigné</span>
                                        </div>
                                        <span class="font-semibold">{{ metrics.basic.assigned }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-blue-500 rounded mr-2"></div>
                                            <span class="text-sm text-gray-600">En Cours</span>
                                        </div>
                                        <span class="font-semibold">{{ metrics.basic.in_progress }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-green-500 rounded mr-2"></div>
                                            <span class="text-sm text-gray-600">Terminé</span>
                                        </div>
                                        <span class="font-semibold">{{ metrics.basic.completed }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-red-500 rounded mr-2"></div>
                                            <span class="text-sm text-gray-600">Incident</span>
                                        </div>
                                        <span class="font-semibold">{{ metrics.basic.incident }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Performers -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Checkers</h3>
                                <div class="space-y-3">
                                    <div v-for="(checker, index) in metrics.checker_performance.slice(0, 5)" :key="checker.name" class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="w-6 h-6 bg-primary-100 text-primary-800 text-xs font-medium rounded-full flex items-center justify-center mr-3">
                                                {{ index + 1 }}
                                            </span>
                                            <span class="text-sm font-medium text-gray-900">{{ checker.name }}</span>
                                        </div>
                                        <span class="text-sm text-gray-600">{{ checker.missions_completed }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DashboardOps>
    </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, reactive, onMounted, computed } from 'vue'
import DashboardOps from '@/Layouts/DashboardOps.vue'
import NotificationPanel from '@/Components/NotificationPanel.vue'
import BailMobiliteCard from '@/Components/BailMobiliteCard.vue'
// Simple debounce function
const debounce = (func, wait) => {
    let timeout
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

const props = defineProps({
    metrics: Object,
    kanbanData: Object,
    pendingNotifications: Array,
    missionsForValidation: Array,
    endingSoon: Array,
    notificationStats: Object,
    performanceTrends: Object,
})

// Reactive state
const currentView = ref('overview')
const showExportMenu = ref(false)
const kanbanData = ref(props.kanbanData || { assigned: [], in_progress: [], completed: [], incident: [] })
const opsUsers = ref([])
const checkers = ref([])

const filters = reactive({
    search: '',
    status: '',
    ops_user_id: '',
    checker_id: '',
    date_from: '',
    date_to: '',
    incident_only: false,
    ending_soon: false,
})

// Computed properties
const debouncedSearch = debounce(() => {
    applyFilters()
}, 300)

// Methods
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}

const getRemainingDays = (endDate) => {
    const today = new Date()
    const end = new Date(endDate)
    const diffTime = end - today
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return Math.max(0, diffDays)
}

const getStatusClass = (status) => {
    const classes = {
        assigned: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        incident: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
    const labels = {
        assigned: 'Assigné',
        in_progress: 'En Cours',
        completed: 'Terminé',
        incident: 'Incident'
    }
    return labels[status] || status
}

const getChangeClass = (change) => {
    if (change > 0) return 'text-green-600'
    if (change < 0) return 'text-red-600'
    return 'text-gray-600'
}

const getChangeText = (change) => {
    if (change > 0) return `+${change}`
    return change.toString()
}

const assignExit = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'assign_exit' }
    })
}

const viewBailMobilite = (bailMobilite) => {
    const id = typeof bailMobilite === 'object' ? bailMobilite.id : bailMobilite
    router.visit(route('ops.bail-mobilites.show', id))
}

const handleAssignEntry = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'assign_entry' }
    })
}

const handleAssignExit = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'assign_exit' }
    })
}

const handleIncident = (bailMobilite) => {
    router.visit(route('ops.bail-mobilites.show', bailMobilite.id), {
        data: { action: 'handle_incident' }
    })
}

const applyFilters = async () => {
    if (currentView.value === 'kanban') {
        await loadKanbanData()
    }
}

const resetFilters = () => {
    Object.keys(filters).forEach(key => {
        if (typeof filters[key] === 'boolean') {
            filters[key] = false
        } else {
            filters[key] = ''
        }
    })
    applyFilters()
}

const loadKanbanData = async () => {
    try {
        const response = await fetch(route('ops.api.kanban-data', filters))
        const data = await response.json()
        kanbanData.value = data.kanbanData
    } catch (error) {
        console.error('Error loading kanban data:', error)
    }
}

const loadFilterOptions = async () => {
    try {
        const [opsResponse, checkersResponse] = await Promise.all([
            fetch(route('ops.api.ops-users')),
            fetch(route('ops.api.checkers'))
        ])
        
        opsUsers.value = await opsResponse.json()
        checkers.value = await checkersResponse.json()
    } catch (error) {
        console.error('Error loading filter options:', error)
    }
}

const exportData = async (format) => {
    showExportMenu.value = false
    
    try {
        const params = new URLSearchParams({
            format,
            ...filters
        })
        
        const response = await fetch(route('ops.api.export') + '?' + params)
        
        if (format === 'csv') {
            const blob = await response.blob()
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = `bail_mobilites_${new Date().toISOString().split('T')[0]}.csv`
            document.body.appendChild(a)
            a.click()
            window.URL.revokeObjectURL(url)
            document.body.removeChild(a)
        } else {
            const data = await response.json()
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = `bail_mobilites_${new Date().toISOString().split('T')[0]}.json`
            document.body.appendChild(a)
            a.click()
            window.URL.revokeObjectURL(url)
            document.body.removeChild(a)
        }
    } catch (error) {
        console.error('Error exporting data:', error)
        alert('Erreur lors de l\'export des données')
    }
}

// Lifecycle
onMounted(() => {
    loadFilterOptions()
    
    // Close export menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            showExportMenu.value = false
        }
    })
})
</script>