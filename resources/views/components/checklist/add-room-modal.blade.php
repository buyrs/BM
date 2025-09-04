@props([
    'show' => 'showAddRoomModal'
])

<div 
    x-show="{{ $show }}"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div 
            x-show="{{ $show }}"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            @click="$dispatch('close')"
        ></div>

        <!-- Modal panel -->
        <div 
            x-show="{{ $show }}"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
        >
            <form @submit.prevent="addRoom">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-primary-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Ajouter une pièce
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Ajoutez une nouvelle pièce à inspecter dans le checklist.
                                </p>
                            </div>
                            
                            <!-- Form Fields -->
                            <div class="mt-4 space-y-4">
                                <!-- Room Type -->
                                <div>
                                    <label for="room-type" class="block text-sm font-medium text-gray-700">
                                        Type de pièce
                                    </label>
                                    <select 
                                        id="room-type"
                                        x-model="roomData.type"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"
                                    >
                                        <option value="">Sélectionner un type</option>
                                        <option value="bedroom">Chambre</option>
                                        <option value="living_room">Salon</option>
                                        <option value="kitchen">Cuisine</option>
                                        <option value="bathroom">Salle de bain</option>
                                        <option value="toilet">Toilettes</option>
                                        <option value="dining_room">Salle à manger</option>
                                        <option value="office">Bureau</option>
                                        <option value="storage">Rangement</option>
                                        <option value="balcony">Balcon</option>
                                        <option value="terrace">Terrasse</option>
                                        <option value="garage">Garage</option>
                                        <option value="basement">Sous-sol</option>
                                        <option value="attic">Grenier</option>
                                        <option value="other">Autre</option>
                                    </select>
                                </div>
                                
                                <!-- Room Name -->
                                <div>
                                    <label for="room-name" class="block text-sm font-medium text-gray-700">
                                        Nom de la pièce
                                    </label>
                                    <input 
                                        type="text"
                                        id="room-name"
                                        x-model="roomData.name"
                                        placeholder="Ex: Chambre principale, Cuisine, etc."
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"
                                        required
                                    />
                                </div>
                                
                                <!-- Custom Type (if other is selected) -->
                                <div x-show="roomData.type === 'other'">
                                    <label for="custom-type" class="block text-sm font-medium text-gray-700">
                                        Type personnalisé
                                    </label>
                                    <input 
                                        type="text"
                                        id="custom-type"
                                        x-model="roomData.customType"
                                        placeholder="Précisez le type de pièce"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"
                                    />
                                </div>
                                
                                <!-- Room Description -->
                                <div>
                                    <label for="room-description" class="block text-sm font-medium text-gray-700">
                                        Description (optionnel)
                                    </label>
                                    <textarea 
                                        id="room-description"
                                        x-model="roomData.description"
                                        rows="3"
                                        placeholder="Description de la pièce, particularités, etc."
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm"
                                    ></textarea>
                                </div>
                            </div>
                            
                            <!-- Error Message -->
                            <div x-show="hasError" class="mt-4 text-sm text-red-600" x-text="errorMessage"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Actions -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button 
                        type="submit"
                        :disabled="!isFormValid || isSubmitting"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span x-show="!isSubmitting">Ajouter la pièce</span>
                        <span x-show="isSubmitting">Ajout en cours...</span>
                    </button>
                    <button 
                        type="button"
                        @click="$dispatch('close')"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('addRoomModal', () => ({
        roomData: {
            type: '',
            name: '',
            customType: '',
            description: ''
        },
        isSubmitting: false,
        hasError: false,
        errorMessage: '',
        
        get isFormValid() {
            return this.roomData.type && this.roomData.name && 
                   (this.roomData.type !== 'other' || this.roomData.customType);
        },
        
        addRoom() {
            if (!this.isFormValid) {
                this.showError('Veuillez remplir tous les champs requis');
                return;
            }
            
            this.isSubmitting = true;
            this.hasError = false;
            
            try {
                const roomData = {
                    type: this.roomData.type,
                    name: this.roomData.name,
                    customType: this.roomData.customType,
                    description: this.roomData.description
                };
                
                // Emit event to parent component
                this.$dispatch('room-added', roomData);
                
                // Reset form
                this.resetForm();
                
                // Close modal
                this.$dispatch('close');
                
            } catch (error) {
                console.error('Error adding room:', error);
                this.showError('Erreur lors de l\'ajout de la pièce');
            } finally {
                this.isSubmitting = false;
            }
        },
        
        resetForm() {
            this.roomData = {
                type: '',
                name: '',
                customType: '',
                description: ''
            };
            this.hasError = false;
            this.errorMessage = '';
        },
        
        showError(message) {
            this.hasError = true;
            this.errorMessage = message;
        }
    }));
});
</script>
