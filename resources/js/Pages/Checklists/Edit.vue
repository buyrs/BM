<template>
    <DashboardChecker>
        <template #header>
            <h2 class="font-semibold text-xl text-text-primary leading-tight">
                {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }} Checklist
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="saveChecklist" class="space-y-8">
                    <!-- Progress Steps -->
                    <div class="flex justify-between items-center mb-8">
                        <div
                            v-for="(step, index) in steps"
                            :key="step"
                            class="flex items-center"
                            :class="{ 'text-primary': currentStep >= index, 'text-gray-400': currentStep < index }"
                        >
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center border-2"
                                :class="{
                                    'border-primary bg-primary text-white': currentStep >= index,
                                    'border-gray-400': currentStep < index
                                }"
                            >
                                {{ index + 1 }}
                            </div>
                            <div class="ml-2">{{ step }}</div>
                            <div
                                v-if="index < steps.length - 1"
                                class="w-16 h-1 mx-4"
                                :class="{
                                    'bg-primary': currentStep > index,
                                    'bg-gray-300': currentStep <= index
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- Step 1: General Information -->
                    <div v-show="currentStep === 0" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4 text-text-primary">General Information</h3>
                        
                        <!-- Heating System -->
                        <div class="mb-6">
                            <h4 class="font-medium mb-2 text-text-primary">Heating System</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Type</label>
                                    <input
                                        v-model="form.general_info.heating.type"
                                        type="text"
                                        class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Condition</label>
                                    <ConditionSelector v-model="form.general_info.heating.condition" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Comments</label>
                                    <textarea
                                        v-model="form.general_info.heating.comment"
                                        rows="2"
                                        class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Hot Water -->
                        <div class="mb-6">
                            <h4 class="font-medium mb-2 text-text-primary">Hot Water System</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Type</label>
                                    <input
                                        v-model="form.general_info.hot_water.type"
                                        type="text"
                                        class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Condition</label>
                                    <ConditionSelector v-model="form.general_info.hot_water.condition" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Comments</label>
                                    <textarea
                                        v-model="form.general_info.hot_water.comment"
                                        rows="2"
                                        class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Keys -->
                        <div>
                            <h4 class="font-medium mb-2 text-text-primary">Keys</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Number of Keys</label>
                                    <input
                                        v-model.number="form.general_info.keys.count"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Condition</label>
                                    <ConditionSelector v-model="form.general_info.keys.condition" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-text-secondary">Comments</label>
                                    <textarea
                                        v-model="form.general_info.keys.comment"
                                        rows="2"
                                        class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Rooms -->
                    <div v-show="currentStep === 1" class="space-y-6">
                        <!-- Room Controls -->
                        <div class="bg-white overflow-hidden shadow-md rounded-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-text-primary">Rooms</h3>
                                <PrimaryButton
                                    type="button"
                                    @click="showAddRoomModal = true"
                                >
                                    Add Room
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Room List -->
                        <div
                            v-for="(room, roomName) in form.rooms"
                            :key="roomName"
                            class="bg-white overflow-hidden shadow-md rounded-lg p-6"
                        >
                            <h4 class="font-semibold text-lg mb-4 capitalize text-text-primary">{{ roomName.replace('_', ' ') }}</h4>
                            
                            <div class="space-y-4">
                                <div v-for="(value, element) in room" :key="element" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="col-span-1">
                                        <label class="block text-sm font-medium text-text-secondary capitalize">{{ element.replace('_', ' ') }}</label>
                                    </div>
                                    <div class="col-span-1">
                                        <ConditionSelector v-model="form.rooms[roomName][element]" />
                                    </div>
                                    <div class="col-span-2">
                                        <PhotoUploader
                                            :photos="photos[`${roomName}_${element}`]"
                                            @upload="handlePhotoUpload($event, roomName, element)"
                                            @delete="handlePhotoDelete($event, roomName, element)"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Utilities -->
                    <div v-show="currentStep === 2" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4 text-text-primary">Utilities</h3>
                        
                        <div class="space-y-6">
                            <div v-for="(meter, meterName) in form.utilities" :key="meterName">
                                <h4 class="font-medium mb-2 capitalize text-text-primary">{{ meterName.replace('_', ' ') }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-text-secondary">Meter Number</label>
                                        <input
                                            v-model="meter.number"
                                            type="text"
                                            class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-text-secondary">Reading</label>
                                        <input
                                            v-model="meter.reading"
                                            type="text"
                                            class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Signatures -->
                    <div v-show="currentStep === 3" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4 text-text-primary">Signatures</h3>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-2">Tenant Signature</label>
                                <SignaturePad v-model="form.tenant_signature" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-text-secondary mb-2">Agent Signature</label>
                                <SignaturePad v-model="form.agent_signature" />
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex justify-between">
                        <SecondaryButton
                            type="button"
                            v-if="currentStep > 0"
                            @click="currentStep--"
                        >
                            Previous
                        </SecondaryButton>
                        <div class="flex space-x-4">
                            <SecondaryButton
                                type="submit"
                                @click="isDraft = true"
                            >
                                Save Draft
                            </SecondaryButton>
                            <PrimaryButton
                                v-if="currentStep < steps.length - 1"
                                type="button"
                                @click="currentStep++"
                            >
                                Next
                            </PrimaryButton>
                            <PrimaryButton
                                v-else
                                type="submit"
                                @click="isDraft = false"
                                class="bg-success-border hover:bg-success-text"
                            >
                                Complete Checklist
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Room Modal -->
        <Modal :show="showAddRoomModal" @close="showAddRoomModal = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4 text-text-primary">Add New Room</h3>
                <form @submit.prevent="addRoom" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-text-secondary">Room Type</label>
                        <select
                            v-model="newRoom.type"
                            class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                        >
                            <option value="bedroom">Bedroom</option>
                            <option value="bathroom">Bathroom</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-secondary">Room Name</label>
                        <input
                            v-model="newRoom.name"
                            type="text"
                            class="mt-1 block w-full bg-white border-gray-200 rounded-md shadow-sm p-md focus:ring-2 focus:ring-primary focus:border-transparent"
                            :placeholder="newRoom.type === 'bedroom' ? 'e.g. Bedroom 1' : 'e.g. Bathroom 1'"
                        >
                    </div>
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton
                            type="button"
                            @click="showAddRoomModal = false"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                        >
                            Add Room
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </DashboardChecker>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DashboardChecker from '@/Layouts/DashboardChecker.vue'
import ConditionSelector from '@/Components/ConditionSelector.vue'
import PhotoUploader from '@/Components/PhotoUploader.vue'
import SignaturePad from '@/Components/SignaturePad.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    mission: Object,
    checklist: Object
})

const steps = ['General Information', 'Rooms', 'Utilities', 'Signatures']
const currentStep = ref(0)
const showAddRoomModal = ref(false)
const isDraft = ref(true)

const form = useForm({
    general_info: props.checklist.general_info,
    rooms: props.checklist.rooms,
    utilities: props.checklist.utilities,
    tenant_signature: props.checklist.tenant_signature,
    agent_signature: props.checklist.agent_signature,
    items: [],
    is_draft: true
})

const photos = reactive({})

const newRoom = reactive({
    type: 'bedroom',
    name: ''
})

const addRoom = () => {
    const roomKey = newRoom.name.toLowerCase().replace(' ', '_')
    const roomTemplate = {
        type: newRoom.type,
        walls: null,
        floor: null,
        ceiling: null,
        windows: null,
        electrical: null,
        heating: null
    }

    // Add bathroom-specific elements
    if (newRoom.type === 'bathroom') {
        roomTemplate.plumbing = null
        roomTemplate.ventilation = null
        roomTemplate.fixtures = null
    }

    form.rooms[roomKey] = roomTemplate
    showAddRoomModal.value = false
    newRoom.name = ''
}

const handlePhotoUpload = async (file, roomName, element) => {
    const formData = new FormData()
    formData.append('photo', file)
    
    try {
        const response = await axios.post(route('checklist.upload-photo'), formData)
        if (!photos[`${roomName}_${element}`]) {
            photos[`${roomName}_${element}`] = []
        }
        photos[`${roomName}_${element}`].push(response.data)
    } catch (error) {
        console.error('Error uploading photo:', error)
    }
}

const handlePhotoDelete = async (photoId, roomName, element) => {
    try {
        await axios.delete(route('checklist.delete-photo', photoId))
        photos[`${roomName}_${element}`] = photos[`${roomName}_${element}`].filter(p => p.id !== photoId)
    } catch (error) {
        console.error('Error deleting photo:', error)
    }
}

const saveChecklist = () => {
    form.is_draft = isDraft.value
    form.post(route('checklist.store', props.mission.id), {
        onSuccess: () => {
            if (!isDraft.value) {
                window.location = route('missions.show', props.mission.id)
            }
        }
    })
}
</script>