<template>
    <DashboardOps>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Validation de Mission</h1>
                            <p class="text-gray-600">
                                {{ mission.mission_type === 'entry' ? 'Entrée' : 'Sortie' }} - 
                                {{ mission.bail_mobilite.tenant_name }}
                            </p>
                        </div>
                        <Link 
                            :href="route('ops.dashboard')"
                            class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                        >
                            Retour au tableau de bord
                        </Link>
                    </div>
                </div>

                <!-- Mission Details -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Détails de la Mission</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Checker</p>
                                <p class="text-sm text-gray-900">{{ mission.agent.name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Type</p>
                                <p class="text-sm text-gray-900">
                                    {{ mission.mission_type === 'entry' ? 'Entrée' : 'Sortie' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Adresse</p>
                                <p class="text-sm text-gray-900">{{ mission.address }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-600">Date programmée</p>
                                <p class="text-sm text-gray-900">
                                    {{ mission.scheduled_at ? formatDate(mission.scheduled_at) : 'Non programmée' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Checklist Details -->
                <div v-if="mission.checklist" class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Checklist</h3>
                        
                        <!-- General Info -->
                        <div v-if="mission.checklist.general_info" class="mb-6">
                            <h4 class="font-medium text-gray-900 mb-2">Informations Générales</h4>
                            <div class="bg-gray-50 p-4 rounded-md">
                                <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ JSON.stringify(mission.checklist.general_info, null, 2) }}</pre>
                            </div>
                        </div>

                        <!-- Photos -->
                        <div v-if="mission.checklist.items && mission.checklist.items.length > 0" class="mb-6">
                            <h4 class="font-medium text-gray-900 mb-2">Photos</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div 
                                    v-for="item in mission.checklist.items" 
                                    :key="item.id"
                                    v-if="item.photos && item.photos.length > 0"
                                >
                                    <div class="space-y-2">
                                        <p class="text-xs font-medium text-gray-600">{{ item.item_name }}</p>
                                        <div 
                                            v-for="photo in item.photos" 
                                            :key="photo.id"
                                            class="aspect-square bg-gray-200 rounded-md overflow-hidden"
                                        >
                                            <img 
                                                :src="`/storage/${photo.photo_path}`" 
                                                :alt="item.item_name"
                                                class="w-full h-full object-cover"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Status -->
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-600">Statut actuel</p>
                            <span 
                                :class="getChecklistStatusClass(mission.checklist.status)"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            >
                                {{ getChecklistStatusLabel(mission.checklist.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Validation Form -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Validation</h3>
                        
                        <form @submit.prevent="submitValidation">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Décision de validation
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            v-model="form.validation_status" 
                                            value="approved"
                                            class="mr-2"
                                        />
                                        <span class="text-sm text-gray-900">Approuver</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            v-model="form.validation_status" 
                                            value="rejected"
                                            class="mr-2"
                                        />
                                        <span class="text-sm text-gray-900">Rejeter</span>
                                    </label>
                                </div>
                                <div v-if="errors.validation_status" class="text-red-600 text-sm mt-1">
                                    {{ errors.validation_status }}
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Commentaires (optionnel)
                                </label>
                                <textarea 
                                    v-model="form.validation_comments"
                                    rows="4"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ajoutez des commentaires sur votre décision..."
                                ></textarea>
                                <div v-if="errors.validation_comments" class="text-red-600 text-sm mt-1">
                                    {{ errors.validation_comments }}
                                </div>
                            </div>

                            <div class="flex space-x-4">
                                <button 
                                    type="submit"
                                    :disabled="processing || !form.validation_status"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    {{ processing ? 'Traitement...' : 'Valider la décision' }}
                                </button>
                                <Link 
                                    :href="route('ops.dashboard')"
                                    class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                                >
                                    Annuler
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </DashboardOps>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import DashboardOps from '@/Layouts/DashboardOps.vue'

const props = defineProps({
    mission: Object,
    errors: Object
})

const form = reactive({
    validation_status: '',
    validation_comments: ''
})

const processing = ref(false)

const submitValidation = async () => {
    processing.value = true
    
    try {
        await router.post(route('ops.missions.validate.submit', props.mission.id), form)
    } catch (error) {
        console.error('Validation error:', error)
    } finally {
        processing.value = false
    }
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getChecklistStatusClass = (status) => {
    const classes = {
        'pending_validation': 'bg-yellow-100 text-yellow-800',
        'validated': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getChecklistStatusLabel = (status) => {
    const labels = {
        'pending_validation': 'En attente de validation',
        'validated': 'Validée',
        'rejected': 'Rejetée'
    }
    return labels[status] || status
}
</script>