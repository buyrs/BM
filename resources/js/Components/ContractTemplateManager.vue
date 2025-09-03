<template>
  <div class="contract-template-manager">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Contract Template Management</h2>
        <p class="text-gray-600 mt-1">Create, edit, and manage contract templates for bail mobilité agreements</p>
      </div>
      <div class="flex space-x-3">
        <SecondaryButton @click="showFilters = !showFilters">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
          </svg>
          Filters
        </SecondaryButton>
        <PrimaryButton @click="showCreateModal = true">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Create Template
        </PrimaryButton>
      </div>
    </div>

    <!-- Filters -->
    <div v-if="showFilters" class="bg-gray-50 p-4 rounded-lg mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <TextInput
            v-model="filters.search"
            placeholder="Search templates..."
            class="w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
          <select v-model="filters.type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
            <option value="">All Types</option>
            <option value="entry">Entry</option>
            <option value="exit">Exit</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="signed">Signed</option>
            <option value="unsigned">Unsigned</option>
          </select>
        </div>
        <div class="flex items-end">
          <SecondaryButton @click="clearFilters" class="w-full">Clear Filters</SecondaryButton>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Total Templates</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Active</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.active }}</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Signed</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.signed }}</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-red-100 rounded-lg">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Needs Signature</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.needsSignature }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Templates Table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Contract Templates</h3>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Template
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Signature
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Usage
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Created
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="template in filteredTemplates" :key="template.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ template.name }}</div>
                  <div class="text-sm text-gray-500">{{ template.creator?.name }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="template.type === 'entry' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
                  {{ template.type === 'entry' ? 'Entry' : 'Exit' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                  {{ template.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <svg v-if="template.admin_signed_at" class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                  <svg v-else class="w-4 h-4 text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                  <span class="text-xs text-gray-600">
                    {{ template.admin_signed_at ? 'Signed' : 'Pending' }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ template.usage_count || 0 }} contracts
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(template.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                  <button @click="viewTemplate(template)" class="text-blue-600 hover:text-blue-900">
                    View
                  </button>
                  <button v-if="!template.admin_signed_at" @click="editTemplate(template)" class="text-indigo-600 hover:text-indigo-900">
                    Edit
                  </button>
                  <button v-if="!template.admin_signed_at" @click="signTemplate(template)" class="text-green-600 hover:text-green-900">
                    Sign
                  </button>
                  <button v-if="template.admin_signed_at" @click="createVersion(template)" class="text-purple-600 hover:text-purple-900">
                    Version
                  </button>
                  <button @click="previewTemplate(template)" class="text-gray-600 hover:text-gray-900">
                    Preview
                  </button>
                  <button v-if="canDelete(template)" @click="deleteTemplate(template)" class="text-red-600 hover:text-red-900">
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Empty State -->
      <div v-if="filteredTemplates.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No contract templates</h3>
        <p class="mt-1 text-sm text-gray-500">Get started by creating a new contract template.</p>
        <div class="mt-6">
          <PrimaryButton @click="showCreateModal = true">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Template
          </PrimaryButton>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Modal :show="showCreateModal || showEditModal" @close="closeModal" max-width="4xl">
      <div class="p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-medium text-gray-900">
            {{ showCreateModal ? 'Create Contract Template' : 'Edit Contract Template' }}
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitTemplate">
          <div class="space-y-6">
            <!-- Template Name -->
            <div>
              <InputLabel for="name" value="Template Name" />
              <TextInput
                id="name"
                v-model="templateForm.name"
                type="text"
                class="mt-1 block w-full"
                placeholder="e.g., Standard Entry Contract 2024"
                required
              />
              <InputError class="mt-2" :message="templateForm.errors?.name" />
            </div>

            <!-- Template Type -->
            <div>
              <InputLabel for="type" value="Contract Type" />
              <select
                id="type"
                v-model="templateForm.type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                required
              >
                <option value="">Select contract type</option>
                <option value="entry">Entry Contract</option>
                <option value="exit">Exit Contract</option>
              </select>
              <InputError class="mt-2" :message="templateForm.errors?.type" />
            </div>

            <!-- Contract Content with Rich Text Editor -->
            <div>
              <InputLabel for="content" value="Contract Content" />
              <RichTextEditor
                v-model="templateForm.content"
                label=""
                placeholder="Enter the legal content of the contract..."
                help="Use the toolbar to format text and insert placeholders for dynamic content."
                :error="templateForm.errors?.content"
                class="mt-1"
              />
              
              <!-- Placeholder Help -->
              <div class="mt-3 p-4 bg-blue-50 rounded-md">
                <h4 class="text-sm font-medium text-blue-900 mb-2">Available Placeholders:</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                  <div v-for="placeholder in placeholders" :key="placeholder.key" class="text-xs">
                    <code class="bg-white px-2 py-1 rounded border">{{ formatPlaceholder(placeholder.key) }}</code>
                    <div class="text-blue-700 mt-1">{{ placeholder.description }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-4 mt-8">
            <SecondaryButton @click="closeModal" type="button">Cancel</SecondaryButton>
            <PrimaryButton :disabled="templateForm.processing">
              {{ showCreateModal ? 'Create Template' : 'Update Template' }}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Signature Modal -->
    <Modal :show="showSignatureModal" @close="showSignatureModal = false" max-width="2xl">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Sign Contract Template</h3>
        <p class="text-sm text-gray-600 mb-6">
          Please provide your electronic signature to activate this contract template.
        </p>
        
        <SignaturePad 
          v-model="signatureData"
          label="Admin Signature"
          class="mb-6"
        />

        <div class="flex items-center justify-end space-x-4">
          <SecondaryButton @click="showSignatureModal = false">Cancel</SecondaryButton>
          <PrimaryButton @click="submitSignature" :disabled="!signatureData || signingInProgress">
            Sign Template
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Preview Modal -->
    <Modal :show="showPreviewModal" @close="showPreviewModal = false" max-width="4xl">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Contract Preview</h3>
          <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600">
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
          
          <div class="border border-gray-200 rounded-lg p-6 bg-white">
            <div v-html="previewData.content" class="prose prose-sm max-w-none"></div>
            
            <div v-if="previewData.admin_signature" class="mt-6 pt-6 border-t border-gray-200">
              <h4 class="font-medium text-gray-900 mb-2">Admin Signature</h4>
              <img :src="previewData.admin_signature" alt="Admin Signature" class="max-w-xs h-auto border border-gray-300 rounded">
            </div>
          </div>
        </div>
        
        <div v-else class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import SignaturePad from '@/Components/SignaturePad.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
  templates: {
    type: Array,
    default: () => []
  }
})

// Reactive state
const showFilters = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showSignatureModal = ref(false)
const showPreviewModal = ref(false)
const signingInProgress = ref(false)
const signatureData = ref('')
const previewData = ref(null)
const currentTemplate = ref(null)

// Filters
const filters = ref({
  search: '',
  type: '',
  status: ''
})

// Template form
const templateForm = ref({
  name: '',
  type: '',
  content: '',
  processing: false,
  errors: {}
})

// Placeholders for contract content
const placeholders = [
  { key: 'tenant_name', description: 'Tenant Name' },
  { key: 'tenant_email', description: 'Tenant Email' },
  { key: 'tenant_phone', description: 'Tenant Phone' },
  { key: 'address', description: 'Property Address' },
  { key: 'start_date', description: 'Start Date' },
  { key: 'end_date', description: 'End Date' },
  { key: 'admin_name', description: 'Admin Name' },
  { key: 'admin_signature_date', description: 'Admin Signature Date' }
]

// Computed properties
const stats = computed(() => {
  const templates = props.templates
  return {
    total: templates.length,
    active: templates.filter(t => t.is_active).length,
    signed: templates.filter(t => t.admin_signed_at).length,
    needsSignature: templates.filter(t => !t.admin_signed_at).length
  }
})

const filteredTemplates = computed(() => {
  let filtered = props.templates

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(template => 
      template.name.toLowerCase().includes(search) ||
      template.creator?.name.toLowerCase().includes(search)
    )
  }

  if (filters.value.type) {
    filtered = filtered.filter(template => template.type === filters.value.type)
  }

  if (filters.value.status) {
    switch (filters.value.status) {
      case 'active':
        filtered = filtered.filter(template => template.is_active)
        break
      case 'inactive':
        filtered = filtered.filter(template => !template.is_active)
        break
      case 'signed':
        filtered = filtered.filter(template => template.admin_signed_at)
        break
      case 'unsigned':
        filtered = filtered.filter(template => !template.admin_signed_at)
        break
    }
  }

  return filtered
})

// Methods
const clearFilters = () => {
  filters.value = {
    search: '',
    type: '',
    status: ''
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatPlaceholder = (key) => {
  return `{{${key}}}`
}

const canDelete = (template) => {
  return !template.admin_signed_at && (template.usage_count || 0) === 0
}

const viewTemplate = (template) => {
  router.visit(route('admin.contract-templates.show', template.id))
}

const editTemplate = (template) => {
  currentTemplate.value = template
  templateForm.value = {
    name: template.name,
    type: template.type,
    content: template.content,
    processing: false,
    errors: {}
  }
  showEditModal.value = true
}

const signTemplate = (template) => {
  currentTemplate.value = template
  signatureData.value = ''
  showSignatureModal.value = true
}

const createVersion = (template) => {
  if (confirm('Create a new version of this contract template? The current version will be deactivated.')) {
    router.post(route('admin.contract-templates.create-version', template.id))
  }
}

const previewTemplate = async (template) => {
  currentTemplate.value = template
  showPreviewModal.value = true
  previewData.value = null
  
  try {
    const response = await fetch(route('admin.contract-templates.preview', template.id))
    previewData.value = await response.json()
  } catch (error) {
    console.error('Failed to load preview:', error)
  }
}

const deleteTemplate = (template) => {
  if (confirm('Are you sure you want to delete this contract template? This action cannot be undone.')) {
    router.delete(route('admin.contract-templates.destroy', template.id))
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  currentTemplate.value = null
  templateForm.value = {
    name: '',
    type: '',
    content: '',
    processing: false,
    errors: {}
  }
}

const submitTemplate = () => {
  templateForm.value.processing = true
  templateForm.value.errors = {}

  const data = {
    name: templateForm.value.name,
    type: templateForm.value.type,
    content: templateForm.value.content
  }

  if (showCreateModal.value) {
    router.post(route('admin.contract-templates.store'), data, {
      onSuccess: () => {
        closeModal()
      },
      onError: (errors) => {
        templateForm.value.errors = errors
      },
      onFinish: () => {
        templateForm.value.processing = false
      }
    })
  } else {
    router.patch(route('admin.contract-templates.update', currentTemplate.value.id), data, {
      onSuccess: () => {
        closeModal()
      },
      onError: (errors) => {
        templateForm.value.errors = errors
      },
      onFinish: () => {
        templateForm.value.processing = false
      }
    })
  }
}

const submitSignature = () => {
  if (!signatureData.value || !currentTemplate.value) return
  
  signingInProgress.value = true
  
  router.post(route('admin.contract-templates.sign', currentTemplate.value.id), {
    signature: signatureData.value
  }, {
    onSuccess: () => {
      showSignatureModal.value = false
      signatureData.value = ''
      currentTemplate.value = null
    },
    onFinish: () => {
      signingInProgress.value = false
    }
  })
}
</script>

<style scoped>
.contract-template-manager {
  @apply max-w-7xl mx-auto;
}
</style>