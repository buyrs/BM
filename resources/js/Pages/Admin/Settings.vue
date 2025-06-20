<template>
    <Head title="Settings" />
    <DashboardAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>
        </template>
        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="saveSettings" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="siteName">
                            Site Name
                        </label>
                        <input v-model="siteName" id="siteName" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" />
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="supportEmail">
                            Support Email
                        </label>
                        <input v-model="supportEmail" id="supportEmail" type="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" />
                    </div>
                    <div class="flex items-center justify-between">
                        <PrimaryButton type="submit">Save Settings</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </DashboardAdmin>
</template>

<script setup>
import DashboardAdmin from '@/Layouts/DashboardAdmin.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const siteName = ref('');
const supportEmail = ref('');

function saveSettings() {
    // Placeholder: Add logic to save settings via API or Inertia
    alert('Settings saved (placeholder)!');
}
</script> 