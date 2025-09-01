<template>
    <Head title="Contract Templates" />

    <DashboardAdmin>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Contract Templates</h2>
                <Link :href="route('admin.contract-templates.create')">
                    <PrimaryButton>Create Template</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success/Error Messages -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ $page.props.flash.error }}
                </div>

                <!-- Templates List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="templates.data.length === 0" class="text-center py-8">
                            <p class="text-gray-500 text-lg">No contract templates found.</p>
                            <Link :href="route('admin.contract-templates.create')" class="mt-4 inline-block">
                                <PrimaryButton>Create Your First Template</PrimaryButton>
                            </Link>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Signed
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
                                    <tr v-for="template in templates.data" :key="template.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ template.name }}
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
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                  :class="template.admin_signed_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                                {{ template.admin_signed_at ? 'Signed' : 'Unsigned' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(template.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <Link :href="route('admin.contract-templates.show', template.id)"
                                                  class="text-indigo-600 hover:text-indigo-900">
                                                View
                                            </Link>
                                            <Link v-if="!template.admin_signed_at"
                                                  :href="route('admin.contract-templates.edit', template.id)"
                                                  class="text-blue-600 hover:text-blue-900">
                                                Edit
                                            </Link>
                                            <button v-if="template.admin_signed_at"
                                                    @click="createVersion(template)"
                                                    class="text-green-600 hover:text-green-900">
                                                New Version
                                            </button>
                                            <button v-if="!hasSignatures(template)"
                                                    @click="deleteTemplate(template)"
                                                    class="text-red-600 hover:text-red-900">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="templates.links.length > 3" class="mt-6">
                            <nav class="flex justify-center">
                                <div class="flex space-x-1">
                                    <Link v-for="link in templates.links" :key="link.label"
                                          :href="link.url"
                                          :class="[
                                              'px-3 py-2 text-sm font-medium rounded-md',
                                              link.active 
                                                  ? 'bg-indigo-600 text-white' 
                                                  : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
                                          ]"
                                          v-html="link.label">
                                    </Link>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

const props = defineProps({
    templates: {
        type: Object,
        required: true
    }
})

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const hasSignatures = (template) => {
    // This would need to be passed from the backend
    // For now, assume templates with admin_signed_at have signatures
    return template.admin_signed_at !== null
}

const createVersion = (template) => {
    if (confirm('Create a new version of this contract template?')) {
        router.post(route('admin.contract-templates.create-version', template.id))
    }
}

const deleteTemplate = (template) => {
    if (confirm('Are you sure you want to delete this contract template?')) {
        router.delete(route('admin.contract-templates.destroy', template.id))
    }
}
</script>