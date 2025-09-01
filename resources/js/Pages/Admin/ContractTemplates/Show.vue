<template>
    <Head title="Contract Template Details" />

    <DashboardAdmin>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contract Template Details</h2>
                <div class="flex space-x-2">
                    <button @click="showPreview = true" class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200">
                        Preview
                    </button>
                    <Link v-if="!template.admin_signed_at" :href="route('admin.contract-templates.edit', template.id)">
                        <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Edit
                        </button>
                    </Link>
                    <Link :href="route('admin.contract-templates.index')">
                        <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Back to Templates
                        </button>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Success/Error Messages -->
                <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Template Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Template Information</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                                        <dd class="text-sm text-gray-900">{{ template.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Type</dt>
                                        <dd class="text-sm text-gray-900">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                  :class="template.type === 'entry' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                                                {{ template.type === 'entry' ? 'Entry Contract' : 'Exit Contract' }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="text-sm text-gray-900">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                  :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                                {{ template.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created by</dt>
                                        <dd class="text-sm text-gray-900">{{ template.creator.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created at</dt>
                                        <dd class="text-sm text-gray-900">{{ formatDate(template.created_at) }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Signature Status</h3>
                                <div v-if="template.admin_signed_at" class="space-y-3">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-green-800">Signed by Admin</span>
                                    </div>
                                    <dl class="space-y-2">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Signed at</dt>
                                            <dd class="text-sm text-gray-900">{{ formatDateTime(template.admin_signed_at) }}</dd>
                                        </div>
                                    </dl>
                                    <div class="mt-4">
                                        <button @click="toggleActive" 
                                                :class="[
                                                    'px-4 py-2 text-sm font-medium rounded-md',
                                                    template.is_active 
                                                        ? 'text-red-700 bg-red-100 border border-red-300 hover:bg-red-200'
                                                        : 'text-green-700 bg-green-100 border border-green-300 hover:bg-green-200'
                                                ]"
                                                :disabled="processing">
                                            {{ template.is_active ? 'Deactivate' : 'Activate' }} Template
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="space-y-3">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-yellow-800">Awaiting Admin Signature</span>
                                    </div>
                                    <p class="text-sm text-gray-600">This template must be signed before it can be activated and used.</p>
                                    <div class="mt-4">
                                        <button @click="showSignature = true" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                                            Sign Template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contract Content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Contract Content</h3>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <pre class="whitespace-pre-wrap text-sm text-gray-700 font-mono">{{ template.content }}</pre>
                        </div>
                    </div>
                </div>

                <!-- Admin Signature Display -->
                <div v-if="template.admin_signature" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Admin Signature</h3>
                        <div class="border-2 border-gray-200 rounded-md p-4 bg-gray-50">
                            <img :src="template.admin_signature" alt="Admin Signature" class="max-w-xs h-auto">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature Modal -->
        <Modal :show="showSignature" @close="showSignature = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Sign Contract Template</h3>
                <p class="text-sm text-gray-600 mb-6">Please provide your electronic signature to activate this contract template.</p>
                
                <SignaturePad 
                    v-model="signatureData"
                    label="Admin Signature"
                    class="mb-6"
                />

                <div class="flex items-center justify-end space-x-4">
                    <button @click="showSignature = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button @click="submitSignature" :disabled="!signatureData || processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50">
                        Sign Template
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Preview Modal -->
        <Modal :show="showPreview" @close="showPreview = false" max-width="4xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Contract Preview</h3>
                    <button @click="showPreview = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div v-if="previewData" class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-md">
                        <h4 class="font-medium text-gray-900 mb-2">Sample Data Used:</h4>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div v-for="(value, key) in previewData.sample_data" :key="key">
                                <span class="font-mono text-xs bg-white px-2 py-1 rounded">{{key}}</span>: {{ value }}
                            </div>
                        </div>
                    </div>
                    
                    <ContractPreview 
                        :content="previewData.content"
                        :sample-data="previewData.sample_data"
                        :admin-signature="previewData.admin_signature"
                        :admin-signature-date="previewData.sample_data.admin_signature_date"
                        :show-tenant-signature="true"
                    />

                </div>
                
                <div v-else class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>
            </div>
        </Modal>
    </DashboardAdmin>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue'
import Modal from '@/Components/Modal.vue'
import SignaturePad from '@/Components/SignaturePad.vue'
import ContractPreview from '@/Components/ContractPreview.vue'

const props = defineProps({
    template: {
        type: Object,
        required: true
    }
})

const showSignature = ref(false)
const showPreview = ref(false)
const signatureData = ref('')
const processing = ref(false)
const previewData = ref(null)

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const submitSignature = async () => {
    if (!signatureData.value) return
    
    processing.value = true
    
    try {
        await router.post(route('admin.contract-templates.sign', props.template.id), {
            signature: signatureData.value
        }, {
            onSuccess: () => {
                showSignature.value = false
                signatureData.value = ''
            },
            onFinish: () => {
                processing.value = false
            }
        })
    } catch (error) {
        processing.value = false
    }
}

const toggleActive = async () => {
    processing.value = true
    
    try {
        await router.patch(route('admin.contract-templates.toggle-active', props.template.id), {}, {
            onFinish: () => {
                processing.value = false
            }
        })
    } catch (error) {
        processing.value = false
    }
}

const loadPreview = async () => {
    try {
        const response = await fetch(route('admin.contract-templates.preview', props.template.id))
        previewData.value = await response.json()
    } catch (error) {
        console.error('Failed to load preview:', error)
    }
}

watch(showPreview, (newValue) => {
    if (newValue) {
        previewData.value = null
        loadPreview()
    }
})
</script>