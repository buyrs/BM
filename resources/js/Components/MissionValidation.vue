<template>
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Validation de Mission</h3>
                <p class="text-sm text-gray-600">
                    {{ mission.mission_type === 'entry' ? 'Entrée' : 'Sortie' }} - 
                    {{ mission.tenant_name || mission.bail_mobilite?.tenant_name }}
                </p>
            </div>
            <span :class="getStatusClass(mission.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                {{ formatStatus(mission.status) }}
            </span>
        </div>

        <!-- Mission Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Checker</label>
                <p class="mt-1 text-sm text-gray-900">{{ mission.agent?.name || 'Non assigné' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Adresse</label>
                <p class="mt-1 text-sm text-gray-900">{{ mission.address }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Date programmée</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(mission.scheduled_at) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Complétée le</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(mission.completed_at) }}</p>
            </div>
        </div>

        <!-- Checklist Summary -->
        <div v-if="mission.checklist" class="mb-6">
            <h4 class="text-md font-medium text-gray-900 mb-3">Résumé de la Checklist</h4>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ mission.checklist.items_count || 0 }}</div>
                        <div class="text-sm text-gray-600">Éléments vérifiés</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ mission.checklist.photos_count || 0 }}</div>
                        <div class="text-sm text-gray-600">Photos prises</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ getIssuesCount() }}</div>
                        <div class="text-sm text-gray-600">Problèmes signalés</div>
                    </div>
                </div>
                
                <!-- Issues Summary -->
                <div v-if="hasIssues()" class="border-t pt-4">
                    <h5 class="font-medium text-gray-900 mb-2">Problèmes identifiés:</h5>
                    <div class="space-y-2">
                        <div v-for="issue in getIssuesList()" :key="issue.id" class="flex items-center space-x-2">
                            <div :class="getIssueClass(issue.severity)" class="w-3 h-3 rounded-full"></div>
                            <span class="text-sm text-gray-700">{{ issue.description }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photos Preview -->
        <div v-if="mission.checklist?.photos && mission.checklist.photos.length > 0" class="mb-6">
            <h4 class="text-md font-medium text-gray-900 mb-3">Photos de la mission</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div 
                    v-for="photo in mission.checklist.photos.slice(0, 8)" 
                    :key="photo.id"
                    class="aspect-square bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:opacity-75 transition-opacity"
                    @click="openPhotoModal(photo)"
                >
                    <img 
                        :src="getPhotoUrl(photo)" 
                        :alt="photo.description || 'Photo de mission'"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    />
                </div>
                <div 
                    v-if="mission.checklist.photos.length > 8"
                    class="aspect-square bg-gray-100 rounded-lg flex items-center justify-center cursor-pointer hover:bg-gray-200 transition-colors"
                    @click="viewAllPhotos"
                >
                    <div class="text-center">
                        <div class="text-lg font-semibold text-gray-600">+{{ mission.checklist.photos.length - 8 }}</div>
                        <div class="text-xs text-gray-500">plus</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Validation Form -->
        <div class="border-t pt-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Décision de validation</h4>
            
            <form @submit.prevent="submitValidation" class="space-y-4">
                <!-- Validation Decision -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Votre décision</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50" :class="{ 'border-green-500 bg-green-50': form.validation_status === 'approved' }">
                            <input 
                                type="radio" 
                                v-model="form.validation_status" 
                                value="approved"
                                class="mr-3 text-green-600 focus:ring-green-500"
                            />
                            <div>
                                <div class="font-medium text-green-800">Approuver la mission</div>
                                <div class="text-sm text-green-600">La mission est conforme et peut être validée</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50" :class="{ 'border-red-500 bg-red-50': form.validation_status === 'rejected' }">
                            <input 
                                type="radio" 
                                v-model="form.validation_status" 
                                value="rejected"
                                class="mr-3 text-red-600 focus:ring-red-500"
                            />
                            <div>
                                <div class="font-medium text-red-800">Rejeter la mission</div>
                                <div class="text-sm text-red-600">La mission nécessite des corrections</div>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50" :class="{ 'border-yellow-500 bg-yellow-50': form.validation_status === 'needs_review' }">
                            <input 
                                type="radio" 
                                v-model="form.validation_status" 
                                value="needs_review"
                                class="mr-3 text-yellow-600 focus:ring-yellow-500"
                            />
                            <div>
                                <div class="font-medium text-yellow-800">Demander une révision</div>
                                <div class="text-sm text-yellow-600">Des clarifications sont nécessaires</div>
                            </div>
                        </label>
                    </div>
                    <div v-if="errors.validation_status" class="text-red-600 text-sm mt-1">
                        {{ errors.validation_status }}
                    </div>
                </div>

                <!-- Comments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Commentaires
                        <span v-if="form.validation_status === 'rejected' || form.validation_status === 'needs_review'" class="text-red-500">*</span>
                    </label>
                    <textarea 
                        v-model="form.validation_comments"
                        rows="4"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :placeholder="getCommentsPlaceholder()"
                        :required="form.validation_status === 'rejected' || form.validation_status === 'needs_review'"
                    ></textarea>
                    <div v-if="errors.validation_comments" class="text-red-600 text-sm mt-1">
                        {{ errors.validation_comments }}
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between pt-4">
                    <button 
                        type="button"
                        @click="$emit('cancel')"
                        class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                    >
                        Annuler
                    </button>
                    
                    <button 
                        type="submit"
                        :disabled="processing || !form.validation_status"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        {{ processing ? 'Traitement...' : 'Valider la décision' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    mission: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['validated', 'cancel'])

const processing = ref(false)

const form = reactive({
    validation_status: '',
    validation_comments: ''
})

const formatDate = (dateString) => {
    if (!dateString) return 'Non spécifiée'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatStatus = (status) => {
    const statusMap = {
        'pending_validation': 'En attente de validation',
        'validated': 'Validée',
        'rejected': 'Rejetée',
        'completed': 'Terminée'
    }
    return statusMap[status] || status
}

const getStatusClass = (status) => {
    const classes = {
        'pending_validation': 'bg-yellow-100 text-yellow-800',
        'validated': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'completed': 'bg-blue-100 text-blue-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getIssuesCount = () => {
    if (!props.mission.checklist?.items) return 0
    return props.mission.checklist.items.filter(item => 
        item.condition === 'poor' || item.condition === 'damaged'
    ).length
}

const hasIssues = () => {
    return getIssuesCount() > 0
}

const getIssuesList = () => {
    if (!props.mission.checklist?.items) return []
    return props.mission.checklist.items
        .filter(item => item.condition === 'poor' || item.condition === 'damaged')
        .map(item => ({
            id: item.id,
            description: `${item.item_name}: ${item.condition}`,
            severity: item.condition
        }))
}

const getIssueClass = (severity) => {
    const classes = {
        'poor': 'bg-orange-400',
        'damaged': 'bg-red-500'
    }
    return classes[severity] || 'bg-gray-400'
}

const getPhotoUrl = (photo) => {
    return `/storage/${photo.photo_path}`
}

const getCommentsPlaceholder = () => {
    const placeholders = {
        'approved': 'Commentaires optionnels sur la validation...',
        'rejected': 'Expliquez les raisons du rejet et les corrections nécessaires...',
        'needs_review': 'Précisez les points qui nécessitent une clarification...'
    }
    return placeholders[form.validation_status] || 'Ajoutez vos commentaires...'
}

const openPhotoModal = (photo) => {
    // Implement photo modal functionality
    console.log('Opening photo modal for:', photo)
}

const viewAllPhotos = () => {
    // Implement view all photos functionality
    console.log('View all photos')
}

const submitValidation = async () => {
    processing.value = true
    
    try {
        await router.post(route('ops.missions.validate', props.mission.id), form, {
            onSuccess: () => {
                emit('validated', {
                    mission: props.mission,
                    status: form.validation_status,
                    comments: form.validation_comments
                })
            }
        })
    } catch (error) {
        console.error('Validation error:', error)
    } finally {
        processing.value = false
    }
}
</script>
</template>