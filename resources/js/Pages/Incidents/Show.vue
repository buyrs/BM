<template>
    <DashboardOps>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <Link
                            :href="route('ops.incidents.index')"
                            class="inline-flex items-center text-[var(--text-muted-color)] hover:text-[var(--text-color)] transition-colors"
                        >
                            <span class="material-symbols-outlined mr-1"
                                >arrow_back</span
                            >
                            <span class="text-sm">Retour aux incidents</span>
                        </Link>
                    </div>
                    <h2
                        class="text-3xl font-bold tracking-tight text-[var(--text-color)]"
                    >
                        Incident #{{ incident.id }}
                    </h2>
                    <p class="text-[var(--text-muted-color)] mt-1">
                        {{ incident.title }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        v-if="incident.status === 'open'"
                        @click="updateStatus('in_progress')"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 text-sm font-medium shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <span class="material-symbols-outlined mr-2 text-base"
                            >play_arrow</span
                        >
                        Prendre en charge
                    </button>
                    <button
                        v-if="['open', 'in_progress'].includes(incident.status)"
                        @click="showResolveModal = true"
                        class="inline-flex items-center justify-center rounded-lg bg-green-500 hover:bg-green-600 text-white px-4 py-2 text-sm font-medium shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                        <span class="material-symbols-outlined mr-2 text-base"
                            >check_circle</span
                        >
                        Résoudre
                    </button>
                    <button
                        v-if="incident.status === 'resolved'"
                        @click="updateStatus('closed')"
                        class="inline-flex items-center justify-center rounded-lg bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 text-sm font-medium shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    >
                        <span class="material-symbols-outlined mr-2 text-base"
                            >close</span
                        >
                        Fermer
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Incident Details -->
                <div
                    class="rounded-xl border border-[var(--border-color)] bg-[var(--card-bg-color)] shadow-sm p-6"
                >
                    <div class="flex items-center gap-3 mb-6">
                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-full"
                            :class="getSeverityIconClass(incident.severity)"
                        >
                            <span
                                class="material-symbols-outlined text-sm"
                                :class="getSeverityIconColor(incident.severity)"
                            >
                                {{ getSeverityIcon(incident.severity) }}
                            </span>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-semibold text-[var(--text-color)]"
                            >
                                Détails de l'incident
                            </h3>
                            <p class="text-sm text-[var(--text-muted-color)]">
                                {{ getTypeLabel(incident.type) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-[var(--text-muted-color)] mb-1"
                                    >Sévérité</label
                                >
                                <span
                                    :class="
                                        getSeverityBadgeClass(incident.severity)
                                    "
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                >
                                    {{ getSeverityLabel(incident.severity) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Sévérité</label
                            >
                            <span
                                :class="
                                    getSeverityBadgeClass(incident.severity)
                                "
                                class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                            >
                                {{ getSeverityLabel(incident.severity) }}
                            </span>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Statut</label
                            >
                            <span
                                :class="getStatusBadgeClass(incident.status)"
                                class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                            >
                                {{ getStatusLabel(incident.status) }}
                            </span>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Détecté le</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ formatDate(incident.detected_at) }}
                            </p>
                        </div>
                        <div v-if="incident.resolved_at">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Résolu le</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ formatDate(incident.resolved_at) }}
                            </p>
                        </div>
                        <div v-if="incident.resolved_by">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Résolu par</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ incident.resolved_by.name }}
                            </p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Description</label
                        >
                        <div class="bg-gray-50 rounded-md p-3">
                            <p
                                class="text-sm text-gray-900 whitespace-pre-wrap"
                            >
                                {{ incident.description }}
                            </p>
                        </div>
                    </div>

                    <div v-if="incident.resolution_notes" class="mb-6">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Notes de résolution</label
                        >
                        <div class="bg-green-50 rounded-md p-3">
                            <p
                                class="text-sm text-gray-900 whitespace-pre-wrap"
                            >
                                {{ incident.resolution_notes }}
                            </p>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div v-if="incident.metadata" class="mb-6">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Informations techniques</label
                        >
                        <div class="bg-gray-50 rounded-md p-3">
                            <pre class="text-xs text-gray-600">{{
                                JSON.stringify(incident.metadata, null, 2)
                            }}</pre>
                        </div>
                    </div>
                </div>

                <!-- Corrective Actions -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Tâches correctives
                        </h3>
                        <button
                            @click="showCreateActionModal = true"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                        >
                            Créer une tâche
                        </button>
                    </div>

                    <div
                        v-if="
                            incident.corrective_actions &&
                            incident.corrective_actions.length > 0
                        "
                        class="space-y-4"
                    >
                        <div
                            v-for="action in incident.corrective_actions"
                            :key="action.id"
                            class="border border-gray-200 rounded-lg p-4"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-medium text-gray-900">
                                    {{ action.title }}
                                </h4>
                                <div class="flex space-x-2">
                                    <span
                                        :class="
                                            getPriorityBadgeClass(
                                                action.priority
                                            )
                                        "
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ getPriorityLabel(action.priority) }}
                                    </span>
                                    <span
                                        :class="
                                            getActionStatusBadgeClass(
                                                action.status
                                            )
                                        "
                                        class="px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            getActionStatusLabel(action.status)
                                        }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">
                                {{ action.description }}
                            </p>
                            <div
                                class="flex justify-between items-center text-xs text-gray-500"
                            >
                                <div>
                                    <span v-if="action.assigned_to"
                                        >Assigné à:
                                        {{ action.assigned_to.name }}</span
                                    >
                                    <span v-if="action.due_date">
                                        • Échéance:
                                        {{ formatDate(action.due_date) }}</span
                                    >
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        v-if="action.status === 'pending'"
                                        @click="
                                            updateActionStatus(
                                                action.id,
                                                'in_progress'
                                            )
                                        "
                                        class="text-blue-600 hover:text-blue-900"
                                    >
                                        Commencer
                                    </button>
                                    <button
                                        v-if="
                                            ['pending', 'in_progress'].includes(
                                                action.status
                                            )
                                        "
                                        @click="
                                            updateActionStatus(
                                                action.id,
                                                'completed'
                                            )
                                        "
                                        class="text-green-600 hover:text-green-900"
                                    >
                                        Terminer
                                    </button>
                                    <button
                                        v-if="action.status !== 'cancelled'"
                                        @click="
                                            updateActionStatus(
                                                action.id,
                                                'cancelled'
                                            )
                                        "
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        Aucune tâche corrective créée pour cet incident.
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Bail Mobilité Info -->
                <div
                    v-if="incident.bail_mobilite"
                    class="bg-white shadow rounded-lg p-6"
                >
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Bail Mobilité
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Locataire</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ incident.bail_mobilite.tenant_name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Adresse</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ incident.bail_mobilite.address }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Statut BM</label
                            >
                            <span
                                class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"
                            >
                                {{ incident.bail_mobilite.status }}
                            </span>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Période</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{
                                    formatDate(
                                        incident.bail_mobilite.start_date
                                    )
                                }}
                                -
                                {{
                                    formatDate(incident.bail_mobilite.end_date)
                                }}
                            </p>
                        </div>
                        <div class="pt-3">
                            <Link
                                :href="
                                    route(
                                        'ops.bail-mobilites.show',
                                        incident.bail_mobilite.id
                                    )
                                "
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                Voir le Bail Mobilité →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Mission Info -->
                <div
                    v-if="incident.mission"
                    class="bg-white shadow rounded-lg p-6"
                >
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Mission associée
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Type</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ incident.mission.mission_type }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Statut</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ incident.mission.status }}
                            </p>
                        </div>
                        <div v-if="incident.mission.agent">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Checker</label
                            >
                            <p class="mt-1 text-sm text-gray-900">
                                {{ incident.mission.agent.name }}
                            </p>
                        </div>
                        <div class="pt-3">
                            <Link
                                :href="
                                    route('missions.show', incident.mission.id)
                                "
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                Voir la mission →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Actions rapides
                    </h3>
                    <div class="space-y-2">
                        <button
                            v-if="incident.status === 'open'"
                            @click="updateStatus('in_progress')"
                            class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                        >
                            Prendre en charge
                        </button>
                        <button
                            v-if="
                                ['open', 'in_progress'].includes(
                                    incident.status
                                )
                            "
                            @click="showResolveModal = true"
                            class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm"
                        >
                            Marquer comme résolu
                        </button>
                        <button
                            v-if="incident.status === 'resolved'"
                            @click="updateStatus('closed')"
                            class="w-full bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm"
                        >
                            Fermer l'incident
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resolve Modal -->
        <Modal :show="showResolveModal" @close="showResolveModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Résoudre l'incident
                </h3>
                <form @submit.prevent="resolveIncident">
                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Notes de résolution</label
                        >
                        <textarea
                            v-model="resolveForm.resolution_notes"
                            rows="4"
                            class="w-full border-gray-300 rounded-md"
                            placeholder="Décrivez comment l'incident a été résolu..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button
                            type="button"
                            @click="showResolveModal = false"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Résoudre
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Create Action Modal -->
        <Modal
            :show="showCreateActionModal"
            @close="showCreateActionModal = false"
        >
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Créer une tâche corrective
                </h3>
                <form @submit.prevent="createCorrectiveAction">
                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Titre</label
                        >
                        <input
                            type="text"
                            v-model="actionForm.title"
                            class="w-full border-gray-300 rounded-md"
                            required
                        />
                    </div>
                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Description</label
                        >
                        <textarea
                            v-model="actionForm.description"
                            rows="3"
                            class="w-full border-gray-300 rounded-md"
                            required
                        ></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Priorité</label
                            >
                            <select
                                v-model="actionForm.priority"
                                class="w-full border-gray-300 rounded-md"
                            >
                                <option value="low">Faible</option>
                                <option value="medium">Moyen</option>
                                <option value="high">Élevé</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Assigné à</label
                            >
                            <select
                                v-model="actionForm.assigned_to"
                                class="w-full border-gray-300 rounded-md"
                            >
                                <option value="">Non assigné</option>
                                <option
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Échéance</label
                        >
                        <input
                            type="datetime-local"
                            v-model="actionForm.due_date"
                            class="w-full border-gray-300 rounded-md"
                        />
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button
                            type="button"
                            @click="showCreateActionModal = false"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardOps>
</template>

<script setup>
import { ref, reactive } from "vue";
import { Link, router } from "@inertiajs/vue3";
import DashboardOps from "@/Layouts/DashboardOps.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    incident: Object,
    users: Array,
});

const showResolveModal = ref(false);
const showCreateActionModal = ref(false);

const resolveForm = reactive({
    resolution_notes: "",
});

const actionForm = reactive({
    title: "",
    description: "",
    priority: "medium",
    assigned_to: "",
    due_date: "",
});

const updateStatus = (status) => {
    router.patch(
        route("ops.incidents.update-status", props.incident.id),
        {
            status: status,
            resolution_notes:
                status === "resolved" ? resolveForm.resolution_notes : null,
        },
        {
            preserveState: true,
            onSuccess: () => {
                showResolveModal.value = false;
                resolveForm.resolution_notes = "";
            },
        }
    );
};

const resolveIncident = () => {
    updateStatus("resolved");
};

const createCorrectiveAction = () => {
    router.post(
        route("ops.incidents.create-corrective-action", props.incident.id),
        actionForm,
        {
            preserveState: true,
            onSuccess: () => {
                showCreateActionModal.value = false;
                Object.assign(actionForm, {
                    title: "",
                    description: "",
                    priority: "medium",
                    assigned_to: "",
                    due_date: "",
                });
            },
        }
    );
};

const updateActionStatus = (actionId, status) => {
    router.patch(
        route("ops.corrective-actions.update", actionId),
        {
            status: status,
        },
        {
            preserveState: true,
        }
    );
};

const getSeverityBadgeClass = (severity) => {
    const classes = {
        low: "bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300",
        medium: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300",
        high: "bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300",
        critical:
            "bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300",
    };
    return (
        classes[severity] ||
        "bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300"
    );
};

const getStatusBadgeClass = (status) => {
    const classes = {
        open: "bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300",
        in_progress:
            "bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300",
        resolved:
            "bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300",
        closed: "bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300",
    };
    return (
        classes[status] ||
        "bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300"
    );
};

const getPriorityBadgeClass = (priority) => {
    const classes = {
        low: "bg-green-100 text-green-800",
        medium: "bg-yellow-100 text-yellow-800",
        high: "bg-orange-100 text-orange-800",
        urgent: "bg-red-100 text-red-800",
    };
    return classes[priority] || "bg-gray-100 text-gray-800";
};

const getActionStatusBadgeClass = (status) => {
    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        in_progress: "bg-blue-100 text-blue-800",
        completed: "bg-green-100 text-green-800",
        cancelled: "bg-gray-100 text-gray-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getSeverityLabel = (severity) => {
    const labels = {
        low: "Faible",
        medium: "Moyen",
        high: "Élevé",
        critical: "Critique",
    };
    return labels[severity] || severity;
};

const getStatusLabel = (status) => {
    const labels = {
        open: "Ouvert",
        in_progress: "En cours",
        resolved: "Résolu",
        closed: "Fermé",
    };
    return labels[status] || status;
};

const getTypeLabel = (type) => {
    const labels = {
        missing_checklist: "Checklist manquante",
        incomplete_checklist: "Checklist incomplète",
        missing_tenant_signature: "Signature locataire manquante",
        missing_required_photos: "Photos obligatoires manquantes",
        missing_contract_signature: "Signature contrat manquante",
        keys_not_returned: "Clés non remises",
        overdue_mission: "Mission en retard",
        validation_timeout: "Délai de validation dépassé",
    };
    return labels[type] || type;
};

const getPriorityLabel = (priority) => {
    const labels = {
        low: "Faible",
        medium: "Moyen",
        high: "Élevé",
        urgent: "Urgent",
    };
    return labels[priority] || priority;
};

const getActionStatusLabel = (status) => {
    const labels = {
        pending: "En attente",
        in_progress: "En cours",
        completed: "Terminé",
        cancelled: "Annulé",
    };
    return labels[status] || status;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("fr-FR", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Enhanced helper methods for modern design
const getSeverityIcon = (severity) => {
    const icons = {
        low: "info",
        medium: "warning",
        high: "error",
        critical: "dangerous",
    };
    return icons[severity] || "warning";
};

const getSeverityIconClass = (severity) => {
    const classes = {
        low: "bg-green-100 dark:bg-green-900/50",
        medium: "bg-yellow-100 dark:bg-yellow-900/50",
        high: "bg-orange-100 dark:bg-orange-900/50",
        critical: "bg-red-100 dark:bg-red-900/50",
    };
    return classes[severity] || "bg-gray-100 dark:bg-gray-900/50";
};

const getSeverityIconColor = (severity) => {
    const colors = {
        low: "text-green-600 dark:text-green-400",
        medium: "text-yellow-600 dark:text-yellow-400",
        high: "text-orange-600 dark:text-orange-400",
        critical: "text-red-600 dark:text-red-400",
    };
    return colors[severity] || "text-gray-600 dark:text-gray-400";
};
</script>
