@props([
    'mission' => null,
    'checklist' => null,
    'schema' => null,
    'mode' => 'edit' // 'edit', 'view', 'create'
])

<div 
    x-data="checklistForm({
        mission: @js($mission),
        checklist: @js($checklist),
        schema: @js($schema ?? $checklist?->getDefaultStructure()),
        mode: '{{ $mode }}'
    })"
    class="space-y-6"
>
    <!-- Progress Steps -->
    <div class="flex justify-between items-center mb-8">
        <template x-for="(step, index) in steps" :key="step">
            <div class="flex items-center" :class="{ 'text-primary': currentStep >= index, 'text-gray-400': currentStep < index }">
                <div 
                    class="w-8 h-8 rounded-full flex items-center justify-center border-2"
                    :class="{
                        'border-primary bg-primary text-white': currentStep >= index,
                        'border-gray-400': currentStep < index
                    }"
                >
                    <span x-text="index + 1"></span>
                </div>
                <div class="ml-2" x-text="step"></div>
                <div 
                    x-show="index < steps.length - 1"
                    class="w-16 h-1 mx-4"
                    :class="{
                        'bg-primary': currentStep > index,
                        'bg-gray-300': currentStep <= index
                    }"
                ></div>
            </div>
        </template>
    </div>

    <!-- Form Content -->
    <form @submit.prevent="submitForm" class="space-y-8">
        <!-- Step 1: General Information -->
        <div x-show="currentStep === 0" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Informations Générales</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="(field, key) in schema.general_info" :key="key">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            <span x-text="getFieldLabel(key)"></span>
                            <span x-show="isFieldRequired(key)" class="text-red-500">*</span>
                        </label>
                        
                        <!-- Text Input -->
                        <input 
                            x-show="field.type === 'text'"
                            type="text"
                            x-model="formData.general_info[key]"
                            :placeholder="field.placeholder || ''"
                            :required="isFieldRequired(key)"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                        />
                        
                        <!-- Select Input -->
                        <select 
                            x-show="field.type === 'select'"
                            x-model="formData.general_info[key]"
                            :required="isFieldRequired(key)"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                        >
                            <option value="">Sélectionner...</option>
                            <template x-for="option in field.options" :key="option.value">
                                <option :value="option.value" x-text="option.label"></option>
                            </template>
                        </select>
                        
                        <!-- Textarea -->
                        <textarea 
                            x-show="field.type === 'textarea'"
                            x-model="formData.general_info[key]"
                            :placeholder="field.placeholder || ''"
                            :required="isFieldRequired(key)"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                        ></textarea>
                    </div>
                </template>
            </div>
        </div>

        <!-- Step 2: Rooms -->
        <div x-show="currentStep === 1" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Pièces</h3>
                <button 
                    type="button"
                    @click="showAddRoomModal = true"
                    class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark"
                >
                    Ajouter une pièce
                </button>
            </div>
            
            <div class="space-y-4">
                <template x-for="(room, roomKey) in formData.rooms" :key="roomKey">
                    <div class="border border-gray-200 rounded-lg p-4 space-y-4 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-lg capitalize" x-text="getRoomLabel(roomKey)"></h4>
                            <div class="flex space-x-2">
                                <span class="text-sm text-gray-500" x-text="getRoomCompletionStatus(roomKey)"></span>
                                <button 
                                    type="button"
                                    @click="removeRoom(roomKey)"
                                    class="text-red-600 hover:text-red-800"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="(item, itemKey) in room" :key="itemKey">
                                <div x-show="itemKey !== 'comments'" class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700 capitalize">
                                        <span x-text="getItemLabel(itemKey)"></span>
                                        <span x-show="isItemRequired(itemKey)" class="text-red-500">*</span>
                                    </label>
                                    
                                    <select 
                                        x-model="formData.rooms[roomKey][itemKey]"
                                        :required="isItemRequired(itemKey)"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                                    >
                                        <option value="">Sélectionner...</option>
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Bon</option>
                                        <option value="fair">Correct</option>
                                        <option value="poor">Mauvais</option>
                                        <option value="damaged">Endommagé</option>
                                        <option value="broken">Cassé</option>
                                    </select>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Room Comments -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Commentaires pour <span x-text="getRoomLabel(roomKey)"></span>
                            </label>
                            <textarea 
                                x-model="formData.rooms[roomKey].comments"
                                rows="2"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                                placeholder="Observations particulières..."
                            ></textarea>
                        </div>
                        
                        <!-- Photo Upload for Room -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Photos pour <span x-text="getRoomLabel(roomKey)"></span>
                            </label>
                            <x-checklist.photo-uploader 
                                :room-key="roomKey"
                                :existing-photos="[]"
                                :required="false"
                            />
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Step 3: Utilities -->
        <div x-show="currentStep === 2" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Utilitaires</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="(utility, key) in schema.utilities" :key="key">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            <span x-text="getFieldLabel(key)"></span>
                            <span x-show="isFieldRequired(key)" class="text-red-500">*</span>
                        </label>
                        
                        <template x-if="utility.type === 'object'">
                            <div class="space-y-2">
                                <template x-for="(subField, subKey) in utility.fields" :key="subKey">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600" x-text="getFieldLabel(subKey)"></label>
                                        
                                        <input 
                                            x-show="subField.type === 'text'"
                                            type="text"
                                            x-model="formData.utilities[key][subKey]"
                                            :placeholder="subField.placeholder || ''"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm"
                                        />
                                        
                                        <select 
                                            x-show="subField.type === 'select'"
                                            x-model="formData.utilities[key][subKey]"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm"
                                        >
                                            <option value="">Sélectionner...</option>
                                            <template x-for="option in subField.options" :key="option.value">
                                                <option :value="option.value" x-text="option.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                </template>
                            </div>
                        </template>
                        
                        <template x-if="utility.type !== 'object'">
                            <input 
                                type="text"
                                x-model="formData.utilities[key]"
                                :placeholder="utility.placeholder || ''"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                            />
                        </template>
                    </div>
                </template>
            </div>
            
            <!-- Photo Upload for Utilities -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Photos des compteurs</label>
                <x-checklist.photo-uploader 
                    room-key="utilities"
                    :existing-photos="[]"
                    :required="true"
                />
            </div>
        </div>

        <!-- Step 4: Signatures -->
        <div x-show="currentStep === 3" class="bg-white overflow-hidden shadow-md rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Signatures</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tenant Signature -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">Signature du Locataire</h4>
                    <x-checklist.signature-pad 
                        signature-type="tenant"
                        :existing-signature="$checklist?->tenant_signature"
                        :required="true"
                    />
                </div>
                
                <!-- Agent Signature -->
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">Signature de l'Agent</h4>
                    <x-checklist.signature-pad 
                        signature-type="agent"
                        :existing-signature="$checklist?->agent_signature"
                        :required="true"
                    />
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between">
            <button 
                type="button"
                @click="previousStep"
                x-show="currentStep > 0"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
                Précédent
            </button>
            
            <div class="flex space-x-4 ml-auto">
                <button 
                    type="button"
                    @click="saveDraft"
                    :disabled="isSubmitting"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                >
                    <span x-show="!isSubmitting">Sauvegarder comme brouillon</span>
                    <span x-show="isSubmitting">Sauvegarde...</span>
                </button>
                
                <button 
                    type="button"
                    @click="nextStep"
                    x-show="currentStep < steps.length - 1"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark"
                >
                    Suivant
                </button>
                
                <button 
                    type="submit"
                    x-show="currentStep === steps.length - 1"
                    :disabled="!isFormValid || isSubmitting"
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 disabled:opacity-50"
                >
                    <span x-show="!isSubmitting">Soumettre le checklist</span>
                    <span x-show="isSubmitting">Soumission...</span>
                </button>
            </div>
        </div>
    </form>

    <!-- Add Room Modal -->
    <x-checklist.add-room-modal 
        :show="'showAddRoomModal'"
        @room-added="addRoom"
    />
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('checklistForm', (config) => ({
        mission: config.mission,
        checklist: config.checklist,
        schema: config.schema,
        mode: config.mode,
        
        // Form state
        currentStep: 0,
        isSubmitting: false,
        showAddRoomModal: false,
        
        // Form data
        formData: {
            general_info: {},
            rooms: {},
            utilities: {},
            tenant_signature: null,
            agent_signature: null
        },
        
        // Steps configuration
        steps: ['Informations Générales', 'Pièces', 'Utilitaires', 'Signatures'],
        
        init() {
            this.initializeFormData();
            this.setupEventListeners();
        },
        
        initializeFormData() {
            // Initialize general_info
            if (this.schema.general_info) {
                Object.keys(this.schema.general_info).forEach(key => {
                    this.formData.general_info[key] = this.checklist?.general_info?.[key] || '';
                });
            }
            
            // Initialize rooms
            if (this.schema.rooms) {
                Object.keys(this.schema.rooms).forEach(roomKey => {
                    this.formData.rooms[roomKey] = this.checklist?.rooms?.[roomKey] || this.getDefaultRoomStructure();
                });
            }
            
            // Initialize utilities
            if (this.schema.utilities) {
                Object.keys(this.schema.utilities).forEach(key => {
                    if (this.schema.utilities[key].type === 'object') {
                        this.formData.utilities[key] = this.checklist?.utilities?.[key] || {};
                        Object.keys(this.schema.utilities[key].fields).forEach(subKey => {
                            this.formData.utilities[key][subKey] = this.checklist?.utilities?.[key]?.[subKey] || '';
                        });
                    } else {
                        this.formData.utilities[key] = this.checklist?.utilities?.[key] || '';
                    }
                });
            }
            
            // Initialize signatures
            this.formData.tenant_signature = this.checklist?.tenant_signature || null;
            this.formData.agent_signature = this.checklist?.agent_signature || null;
        },
        
        setupEventListeners() {
            // Listen for signature updates
            this.$watch('formData.tenant_signature', (value) => {
                this.validateStep(3);
            });
            
            this.$watch('formData.agent_signature', (value) => {
                this.validateStep(3);
            });
        },
        
        getDefaultRoomStructure() {
            return {
                walls: '',
                floor: '',
                ceiling: '',
                windows: '',
                doors: '',
                lighting: '',
                heating: '',
                comments: ''
            };
        },
        
        getFieldLabel(key) {
            return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        
        getRoomLabel(roomKey) {
            return roomKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        
        getItemLabel(itemKey) {
            const labels = {
                walls: 'Murs',
                floor: 'Sol',
                ceiling: 'Plafond',
                windows: 'Fenêtres',
                doors: 'Portes',
                lighting: 'Éclairage',
                heating: 'Chauffage'
            };
            return labels[itemKey] || itemKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        },
        
        isFieldRequired(key) {
            return this.schema.general_info?.[key]?.required || false;
        },
        
        isItemRequired(itemKey) {
            const requiredItems = ['walls', 'floor', 'ceiling'];
            return requiredItems.includes(itemKey);
        },
        
        getRoomCompletionStatus(roomKey) {
            const room = this.formData.rooms[roomKey];
            if (!room) return '0%';
            
            const requiredFields = ['walls', 'floor', 'ceiling'];
            const completedFields = requiredFields.filter(field => room[field]);
            const percentage = Math.round((completedFields.length / requiredFields.length) * 100);
            
            return `${percentage}%`;
        },
        
        validateStep(stepIndex) {
            // Add validation logic for each step
            return true;
        },
        
        nextStep() {
            if (this.validateStep(this.currentStep)) {
                this.currentStep++;
            }
        },
        
        previousStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },
        
        addRoom(roomData) {
            const roomKey = roomData.name.toLowerCase().replace(/\s+/g, '_');
            this.formData.rooms[roomKey] = {
                ...this.getDefaultRoomStructure(),
                type: roomData.type
            };
            this.showAddRoomModal = false;
        },
        
        removeRoom(roomKey) {
            delete this.formData.rooms[roomKey];
        },
        
        saveDraft() {
            this.submitForm(true);
        },
        
        async submitForm(isDraft = false) {
            this.isSubmitting = true;
            
            try {
                const formData = new FormData();
                formData.append('general_info', JSON.stringify(this.formData.general_info));
                formData.append('rooms', JSON.stringify(this.formData.rooms));
                formData.append('utilities', JSON.stringify(this.formData.utilities));
                formData.append('tenant_signature', this.formData.tenant_signature || '');
                formData.append('agent_signature', this.formData.agent_signature || '');
                formData.append('is_draft', isDraft);
                
                const response = await fetch(`/checklists/${this.mission.id}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        if (isDraft) {
                            this.showNotification('Brouillon sauvegardé avec succès', 'success');
                        } else {
                            this.showNotification('Checklist soumis avec succès', 'success');
                            // Redirect or show success message
                        }
                    } else {
                        this.showNotification(result.message || 'Erreur lors de la soumission', 'error');
                    }
                } else {
                    this.showNotification('Erreur lors de la soumission', 'error');
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                this.showNotification('Erreur lors de la soumission', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },
        
        showNotification(message, type) {
            // Implement notification system
            console.log(`${type.toUpperCase()}: ${message}`);
        },
        
        get isFormValid() {
            // Add comprehensive form validation
            return this.formData.tenant_signature && this.formData.agent_signature;
        }
    }));
});
</script>
