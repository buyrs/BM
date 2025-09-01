<template>
    <div class="contract-preview">
        <div class="bg-white border-2 border-gray-200 rounded-lg p-6 max-h-96 overflow-y-auto">
            <div class="prose prose-sm max-w-none">
                <pre class="whitespace-pre-wrap text-sm text-gray-700 font-sans leading-relaxed">{{ processedContent }}</pre>
            </div>
            
            <!-- Admin Signature Section -->
            <div v-if="adminSignature" class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-sm font-medium text-gray-900 mb-2">Host/Admin Signature:</p>
                        <img :src="adminSignature" alt="Admin Signature" class="max-w-xs h-auto border border-gray-300 rounded">
                        <p class="text-xs text-gray-500 mt-1">Signed on: {{ adminSignatureDate }}</p>
                    </div>
                    
                    <div v-if="showTenantSignature">
                        <p class="text-sm font-medium text-gray-900 mb-2">Tenant Signature:</p>
                        <div class="w-48 h-24 border-2 border-dashed border-gray-300 rounded flex items-center justify-center">
                            <span class="text-xs text-gray-400">Tenant signature will appear here</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">To be signed by tenant</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    content: {
        type: String,
        required: true
    },
    sampleData: {
        type: Object,
        default: () => ({})
    },
    adminSignature: {
        type: String,
        default: null
    },
    adminSignatureDate: {
        type: String,
        default: null
    },
    showTenantSignature: {
        type: Boolean,
        default: true
    }
})

const processedContent = computed(() => {
    let content = props.content
    
    // Replace placeholders with sample data
    Object.entries(props.sampleData).forEach(([key, value]) => {
        const placeholder = `{{${key}}}`
        content = content.replace(new RegExp(placeholder, 'g'), value)
    })
    
    return content
})
</script>

<style scoped>
.contract-preview {
    font-family: 'Times New Roman', serif;
}

.prose pre {
    background: transparent;
    padding: 0;
    margin: 0;
    border: none;
    font-family: inherit;
}
</style>