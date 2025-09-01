<template>
    <div class="space-y-6">
        <h4 class="text-lg font-semibold">Contract Signature</h4>

        <!-- Contract Template Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Select Contract Template
            </label>
            <select
                v-model="selectedTemplateId"
                class="block w-full border-gray-300 rounded-md shadow-sm"
                required
            >
                <option value="">Choose a contract template</option>
                <option
                    v-for="template in availableTemplates"
                    :key="template.id"
                    :value="template.id"
                >
                    {{ template.name }} ({{ template.type }})
                </option>
            </select>
        </div>

        <!-- Contract Preview -->
        <div v-if="selectedTemplate" class="border rounded-lg p-4 bg-gray-50">
            <h5 class="font-medium mb-2">Contract Preview</h5>
            <div class="max-h-64 overflow-y-auto bg-white p-4 rounded border">
                <div v-html="selectedTemplate.content" class="prose prose-sm"></div>
            </div>
            
            <!-- Admin Signature Info -->
            <div v-if="selectedTemplate.admin_signature" class="mt-4 p-3 bg-blue-50 rounded">
                <p class="text-sm text-blue-800">
                    <strong>Host Signature:</strong> Signed on {{ formatDate(selectedTemplate.admin_signed_at) }}
                </p>
            </div>
        </div>

        <!-- Tenant Information Confirmation -->
        <div v-if="selectedTemplate" class="space-y-4">
            <h5 class="font-medium">Tenant Information</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <p class="mt-1 p-2 bg-gray-100 rounded">{{ mission.tenant_name }}</p>
                </div>
                <div v-if="mission.tenant_email">
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <p class="mt-1 p-2 bg-gray-100 rounded">{{ mission.tenant_email }}</p>
                </div>
                <div v-if="mission.tenant_phone">
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <p class="mt-1 p-2 bg-gray-100 rounded">{{ mission.tenant_phone }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <p class="mt-1 p-2 bg-gray-100 rounded">{{ mission.address }}</p>
                </div>
            </div>
        </div>

        <!-- Signature Pad -->
        <div v-if="selectedTemplate">
            <SignaturePad
                v-model="tenantSignature"
                label="Tenant Signature"
            />
            <p class="mt-2 text-sm text-gray-600">
                The tenant must sign above to confirm agreement to the contract terms.
            </p>
        </div>

        <!-- Terms Confirmation -->
        <div v-if="selectedTemplate" class="space-y-2">
            <label class="flex items-start">
                <input
                    v-model="termsAccepted"
                    type="checkbox"
                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm"
                />
                <span class="ml-2 text-sm text-gray-700">
                    I confirm that the tenant has read and understood all contract terms and conditions.
                </span>
            </label>
            <label class="flex items-start">
                <input
                    v-model="identityVerified"
                    type="checkbox"
                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm"
                />
                <span class="ml-2 text-sm text-gray-700">
                    I have verified the tenant's identity and contact information.
                </span>
            </label>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-4">
            <button
                type="button"
                @click="$emit('cancelled')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
                Cancel
            </button>
            <button
                type="button"
                @click="submitSignature"
                :disabled="!canSubmit || submitting"
                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 disabled:opacity-50"
            >
                {{ submitting ? 'Processing...' : 'Submit Signature' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import SignaturePad from './SignaturePad.vue'

const props = defineProps({
    mission: Object,
    contractTemplates: Array
})

const emit = defineEmits(['signed', 'cancelled'])

const selectedTemplateId = ref('')
const tenantSignature = ref('')
const termsAccepted = ref(false)
const identityVerified = ref(false)
const submitting = ref(false)

const availableTemplates = computed(() => {
    if (!props.contractTemplates) return []
    
    const missionType = props.mission.mission_type
    return props.contractTemplates.filter(template => 
        template.type === missionType && 
        template.is_active && 
        template.admin_signature
    )
})

const selectedTemplate = computed(() => {
    if (!selectedTemplateId.value) return null
    return availableTemplates.value.find(t => t.id == selectedTemplateId.value)
})

const canSubmit = computed(() => {
    return selectedTemplateId.value && 
           tenantSignature.value && 
           termsAccepted.value && 
           identityVerified.value
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const submitSignature = () => {
    if (!canSubmit.value) return
    
    submitting.value = true
    
    router.post(route('missions.sign-bail-mobilite-contract', props.mission.id), {
        tenant_signature: tenantSignature.value,
        contract_template_id: selectedTemplateId.value
    }, {
        onSuccess: () => {
            emit('signed')
        },
        onError: (errors) => {
            console.error('Contract signing errors:', errors)
        },
        onFinish: () => {
            submitting.value = false
        }
    })
}
</script>