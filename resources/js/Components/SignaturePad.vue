<template>
    <div class="signature-pad-container">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">{{ label }}</label>
            <div class="mt-1 border-2 border-gray-300 rounded-md">
                <VueSignaturePad
                    ref="signaturePad"
                    :options="options"
                    class="w-full h-48 bg-white"
                />
            </div>
        </div>
        <div class="flex space-x-4">
            <button
                type="button"
                @click="clear"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
                Clear
            </button>
            <button
                type="button"
                @click="save"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700"
            >
                Save Signature
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import VueSignaturePad from 'vue-signature-pad'

const props = defineProps({
    label: {
        type: String,
        required: true
    },
    modelValue: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const signaturePad = ref(null)
const options = {
    backgroundColor: 'rgb(255, 255, 255)',
    penColor: 'rgb(0, 0, 0)'
}

const clear = () => {
    signaturePad.value.clearSignature()
    emit('update:modelValue', '')
}

const save = () => {
    if (signaturePad.value.isEmpty()) {
        return
    }
    const { data } = signaturePad.value.saveSignature()
    emit('update:modelValue', data)
}
</script> 