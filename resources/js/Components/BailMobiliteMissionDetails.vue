<template>
    <div class="space-y-6">
        <!-- Bail Mobilité Information -->
        <div v-if="mission.bail_mobilite" class="bg-blue-50 p-4 rounded-lg">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">Bail Mobilité Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-blue-700">Mission Type</label>
                    <p class="mt-1 text-blue-900">{{ mission.mission_type === 'entry' ? 'Entrée' : 'Sortie' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-blue-700">BM Status</label>
                    <span :class="getBMStatusClass(mission.bail_mobilite.status)" class="text-sm">
                        {{ mission.bail_mobilite.status }}
                    </span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-blue-700">Start Date</label>
                    <p class="mt-1 text-blue-900">{{ formatDate(mission.bail_mobilite.start_date) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-blue-700">End Date</label>
                    <p class="mt-1 text-blue-900">{{ formatDate(mission.bail_mobilite.end_date) }}</p>
                </div>
                <div v-if="mission.scheduled_time" class="md:col-span-2">
                    <label class="block text-sm font-medium text-blue-700">Scheduled Time</label>
                    <p class="mt-1 text-blue-900">{{ mission.scheduled_time }}</p>
                </div>
            </div>
        </div>

        <!-- Checklist Section -->
        <div v-if="canManageChecklist" class="bg-white border rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Checklist</h3>
            
            <!-- Checklist Status -->
            <div v-if="mission.checklist" class="mb-4">
                <span :class="getChecklistStatusClass(mission.checklist.status)" class="text-sm">
                    {{ mission.checklist.status }}
                </span>
                <p v-if="mission.checklist.ops_validation_comments" class="mt-2 text-sm text-gray-600">
                    <strong>Ops Comments:</strong> {{ mission.checklist.ops_validation_comments }}
                </p>
            </div>

            <!-- Checklist Actions for Checker -->
            <div v-if="isChecker && canEditChecklist" class="space-y-4">
                <button
                    @click="showChecklistForm = !showChecklistForm"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    {{ mission.checklist ? 'Edit Checklist' : 'Start Checklist' }}
                </button>

                <!-- Checklist Form -->
                <div v-if="showChecklistForm" class="border rounded-lg p-4 bg-gray-50">
                    <ChecklistForm
                        :mission="mission"
                        :initial-data="mission.checklist"
                        @submitted="handleChecklistSubmitted"
                        @cancelled="showChecklistForm = false"
                    />
                </div>
            </div>

            <!-- Checklist Validation for Ops -->
            <div v-if="isOps && mission.checklist && mission.checklist.status === 'pending_validation'" class="space-y-4">
                <h4 class="font-medium">Validate Checklist</h4>
                <div class="space-y-2">
                    <button
                        @click="validateChecklist('approved')"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 mr-2"
                    >
                        Approve
                    </button>
                    <button
                        @click="showValidationForm = true"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                    >
                        Reject
                    </button>
                </div>

                <!-- Validation Form -->
                <div v-if="showValidationForm" class="border rounded-lg p-4 bg-gray-50">
                    <form @submit.prevent="validateChecklist('rejected')">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Comments</label>
                            <textarea
                                v-model="validationComments"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                placeholder="Explain why the checklist is being rejected..."
                            ></textarea>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                            >
                                Reject with Comments
                            </button>
                            <button
                                type="button"
                                @click="showValidationForm = false"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contract Signature Section -->
        <div v-if="canManageSignature" class="bg-white border rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Contract Signature</h3>
            
            <!-- Signature Status -->
            <div v-if="signatureStatus" class="mb-4">
                <div class="flex items-center space-x-4">
                    <span :class="signatureStatus.complete ? 'text-green-600' : 'text-yellow-600'">
                        {{ signatureStatus.complete ? 'Contract Signed' : 'Signature Pending' }}
                    </span>
                    <div v-if="signatureStatus.pdf_generated" class="text-sm text-gray-600">
                        <a :href="contractPdfUrl" target="_blank" class="text-blue-600 hover:underline">
                            View Contract PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Signature Form for Checker -->
            <div v-if="isChecker && canSign" class="space-y-4">
                <button
                    @click="showSignatureForm = !showSignatureForm"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                >
                    {{ signatureStatus?.tenant_signed ? 'Update Signature' : 'Sign Contract' }}
                </button>

                <!-- Signature Form -->
                <div v-if="showSignatureForm" class="border rounded-lg p-4 bg-gray-50">
                    <ContractSignatureForm
                        :mission="mission"
                        :contract-templates="contractTemplates"
                        @signed="handleContractSigned"
                        @cancelled="showSignatureForm = false"
                    />
                </div>
            </div>
        </div>

        <!-- Photos Section -->
        <div v-if="mission.checklist && mission.checklist.items" class="bg-white border rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Photos</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div
                    v-for="item in mission.checklist.items"
                    :key="item.id"
                    v-if="item.photos && item.photos.length > 0"
                    class="space-y-2"
                >
                    <h4 class="text-sm font-medium">{{ item.item_name }}</h4>
                    <div
                        v-for="photo in item.photos"
                        :key="photo.id"
                        class="aspect-square bg-gray-100 rounded-lg overflow-hidden"
                    >
                        <img
                            :src="`/storage/${photo.photo_path}`"
                            :alt="item.item_name"
                            class="w-full h-full object-cover cursor-pointer"
                            @click="openPhotoModal(photo)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ChecklistForm from './ChecklistForm.vue'
import ContractSignatureForm from './ContractSignatureForm.vue'

const props = defineProps({
    mission: Object,
    contractTemplates: Array
})

const page = usePage()
const user = computed(() => page.props.auth.user)
const isChecker = computed(() => user.value.roles.includes('checker'))
const isOps = computed(() => user.value.roles.includes('ops'))

const showChecklistForm = ref(false)
const showSignatureForm = ref(false)
const showValidationForm = ref(false)
const validationComments = ref('')

const canManageChecklist = computed(() => {
    return props.mission.bail_mobilite_id && (isChecker.value || isOps.value)
})

const canEditChecklist = computed(() => {
    if (!isChecker.value || props.mission.agent_id !== user.value.id) return false
    return ['assigned', 'in_progress'].includes(props.mission.status)
})

const canManageSignature = computed(() => {
    return props.mission.bail_mobilite_id && (isChecker.value || isOps.value)
})

const canSign = computed(() => {
    if (!isChecker.value || props.mission.agent_id !== user.value.id) return false
    return props.mission.checklist && props.mission.checklist.status === 'validated'
})

const signatureStatus = computed(() => {
    if (!props.mission.bail_mobilite?.signatures) return null
    const signatureType = props.mission.mission_type
    const signature = props.mission.bail_mobilite.signatures.find(s => s.signature_type === signatureType)
    return signature?.validation_status || null
})

const contractPdfUrl = computed(() => {
    if (!signatureStatus.value?.pdf_generated) return null
    const signatureType = props.mission.mission_type
    const signature = props.mission.bail_mobilite.signatures.find(s => s.signature_type === signatureType)
    return signature?.contract_pdf_path ? `/storage/${signature.contract_pdf_path}` : null
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const getBMStatusClass = (status) => {
    const classes = {
        assigned: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        incident: 'bg-red-100 text-red-800'
    }
    return `px-3 py-1 rounded-full ${classes[status] || 'bg-gray-100 text-gray-800'}`
}

const getChecklistStatusClass = (status) => {
    const classes = {
        pending_validation: 'bg-yellow-100 text-yellow-800',
        validated: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800'
    }
    return `px-3 py-1 rounded-full ${classes[status] || 'bg-gray-100 text-gray-800'}`
}

const handleChecklistSubmitted = () => {
    showChecklistForm.value = false
    router.reload({ only: ['mission'] })
}

const handleContractSigned = () => {
    showSignatureForm.value = false
    router.reload({ only: ['mission'] })
}

const validateChecklist = (status) => {
    router.post(route('missions.validate-bail-mobilite-checklist', props.mission.id), {
        validation_status: status,
        validation_comments: validationComments.value
    }, {
        onSuccess: () => {
            showValidationForm.value = false
            validationComments.value = ''
        }
    })
}

const openPhotoModal = (photo) => {
    // This would open a modal to view the photo in full size
    // Implementation depends on your modal system
    console.log('Open photo modal for:', photo)
}
</script>