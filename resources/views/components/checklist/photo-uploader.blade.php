@props([
    'roomKey' => 'general',
    'existingPhotos' => [],
    'required' => false,
    'maxFiles' => 10,
    'maxFileSize' => 10, // MB
    'allowedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
])

<div 
    x-data="photoUploader({
        roomKey: '{{ $roomKey }}',
        existingPhotos: @js($existingPhotos),
        required: {{ $required ? 'true' : 'false' }},
        maxFiles: {{ $maxFiles }},
        maxFileSize: {{ $maxFileSize }},
        allowedTypes: @js($allowedTypes)
    })"
    class="space-y-4"
>
    <!-- Upload Area -->
    <div 
        class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors"
        :class="{ 'border-red-300 bg-red-50': hasError, 'border-primary bg-primary-50': isDragOver }"
        @dragover.prevent="isDragOver = true"
        @dragleave.prevent="isDragOver = false"
        @drop.prevent="handleDrop"
    >
        <input 
            type="file"
            x-ref="fileInput"
            @change="handleFileSelect"
            :accept="allowedTypes.join(',')"
            multiple
            class="hidden"
        />
        
        <div class="space-y-2">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            
            <div class="text-sm text-gray-600">
                <button 
                    type="button"
                    @click="$refs.fileInput.click()"
                    class="font-medium text-primary hover:text-primary-dark"
                >
                    Cliquez pour télécharger
                </button>
                ou glissez-déposez vos photos ici
            </div>
            
            <p class="text-xs text-gray-500">
                PNG, JPG, GIF jusqu'à {{ $maxFileSize }}MB (max {{ $maxFiles }} fichiers)
                <span x-show="required" class="text-red-500">*</span>
            </p>
        </div>
    </div>
    
    <!-- Error Message -->
    <div x-show="hasError" class="text-sm text-red-600" x-text="errorMessage"></div>
    
    <!-- Upload Progress -->
    <div x-show="isUploading" class="space-y-2">
        <div class="text-sm text-gray-600">Téléchargement en cours...</div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
                class="bg-primary h-2 rounded-full transition-all duration-300"
                :style="`width: ${uploadProgress}%`"
            ></div>
        </div>
    </div>
    
    <!-- Photo Grid -->
    <div x-show="allPhotos.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <!-- Existing Photos -->
        <template x-for="(photo, index) in existingPhotos" :key="`existing-${index}`">
            <div class="relative group">
                <img 
                    :src="getPhotoUrl(photo)" 
                    :alt="`Photo ${index + 1}`"
                    class="w-full h-24 object-cover rounded-lg border border-gray-200"
                    @error="handleImageError"
                />
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 rounded-lg flex items-center justify-center">
                    <button 
                        type="button"
                        @click="removeExistingPhoto(index)"
                        class="opacity-0 group-hover:opacity-100 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-all duration-200"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="absolute bottom-1 left-1 bg-blue-500 text-white text-xs px-2 py-1 rounded">
                    Existant
                </div>
            </div>
        </template>
        
        <!-- New Photos -->
        <template x-for="(photo, index) in newPhotos" :key="`new-${index}`">
            <div class="relative group">
                <img 
                    :src="photo.preview" 
                    :alt="`Nouvelle photo ${index + 1}`"
                    class="w-full h-24 object-cover rounded-lg border border-gray-200"
                    @error="handleImageError"
                />
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 rounded-lg flex items-center justify-center">
                    <button 
                        type="button"
                        @click="removeNewPhoto(index)"
                        class="opacity-0 group-hover:opacity-100 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-all duration-200"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="absolute bottom-1 left-1 bg-green-500 text-white text-xs px-2 py-1 rounded">
                    Nouveau
                </div>
                
                <!-- Upload Progress for individual photo -->
                <div x-show="photo.uploading" class="absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center">
                    <div class="text-white text-xs">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mx-auto mb-1"></div>
                        Téléchargement...
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Photo Count -->
    <div x-show="allPhotos.length > 0" class="text-sm text-gray-500">
        <span x-text="allPhotos.length"></span> photo(s) sélectionnée(s)
        <span x-show="maxFiles > 0">(max {{ $maxFiles }})</span>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('photoUploader', (config) => ({
        roomKey: config.roomKey,
        existingPhotos: config.existingPhotos || [],
        required: config.required,
        maxFiles: config.maxFiles,
        maxFileSize: config.maxFileSize * 1024 * 1024, // Convert to bytes
        allowedTypes: config.allowedTypes,
        
        // State
        newPhotos: [],
        isUploading: false,
        uploadProgress: 0,
        isDragOver: false,
        hasError: false,
        errorMessage: '',
        
        get allPhotos() {
            return [...this.existingPhotos, ...this.newPhotos];
        },
        
        init() {
            this.validatePhotos();
        },
        
        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.processFiles(files);
            event.target.value = ''; // Reset input
        },
        
        handleDrop(event) {
            this.isDragOver = false;
            const files = Array.from(event.dataTransfer.files);
            this.processFiles(files);
        },
        
        async processFiles(files) {
            this.hasError = false;
            this.errorMessage = '';
            
            // Validate file count
            if (this.allPhotos.length + files.length > this.maxFiles) {
                this.showError(`Maximum ${this.maxFiles} fichiers autorisés`);
                return;
            }
            
            // Validate each file
            const validFiles = [];
            for (const file of files) {
                if (!this.isValidFile(file)) {
                    continue;
                }
                validFiles.push(file);
            }
            
            if (validFiles.length === 0) {
                return;
            }
            
            // Process valid files
            this.isUploading = true;
            this.uploadProgress = 0;
            
            try {
                for (let i = 0; i < validFiles.length; i++) {
                    const file = validFiles[i];
                    const photoData = await this.processFile(file);
                    this.newPhotos.push(photoData);
                    
                    // Update progress
                    this.uploadProgress = Math.round(((i + 1) / validFiles.length) * 100);
                }
                
                this.validatePhotos();
                this.emitPhotosChanged();
            } catch (error) {
                console.error('Error processing files:', error);
                this.showError('Erreur lors du traitement des fichiers');
            } finally {
                this.isUploading = false;
                this.uploadProgress = 0;
            }
        },
        
        async processFile(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    resolve({
                        file: file,
                        preview: e.target.result,
                        uploading: false,
                        uploaded: false
                    });
                };
                
                reader.onerror = () => {
                    reject(new Error('Failed to read file'));
                };
                
                reader.readAsDataURL(file);
            });
        },
        
        isValidFile(file) {
            // Check file type
            if (!this.allowedTypes.includes(file.type)) {
                this.showError(`Type de fichier non autorisé: ${file.name}`);
                return false;
            }
            
            // Check file size
            if (file.size > this.maxFileSize) {
                this.showError(`Fichier trop volumineux: ${file.name} (max ${this.maxFileSize / (1024 * 1024)}MB)`);
                return false;
            }
            
            return true;
        },
        
        removeExistingPhoto(index) {
            this.existingPhotos.splice(index, 1);
            this.validatePhotos();
            this.emitPhotosChanged();
        },
        
        removeNewPhoto(index) {
            this.newPhotos.splice(index, 1);
            this.validatePhotos();
            this.emitPhotosChanged();
        },
        
        getPhotoUrl(photo) {
            if (typeof photo === 'string') {
                return photo;
            }
            if (photo.url) {
                return photo.url;
            }
            if (photo.photo_path) {
                return `/storage/${photo.photo_path}`;
            }
            if (photo.preview) {
                return photo.preview;
            }
            return '';
        },
        
        handleImageError(event) {
            console.warn('Error loading image:', event.target.src);
            event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y3ZjdmNyIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SW1hZ2U8L3RleHQ+PC9zdmc+';
        },
        
        validatePhotos() {
            if (this.required && this.allPhotos.length === 0) {
                this.showError('Au moins une photo est requise');
                return false;
            }
            
            this.hasError = false;
            this.errorMessage = '';
            return true;
        },
        
        showError(message) {
            this.hasError = true;
            this.errorMessage = message;
        },
        
        emitPhotosChanged() {
            // Emit event to parent component
            this.$dispatch('photos-changed', {
                roomKey: this.roomKey,
                photos: this.allPhotos,
                newPhotos: this.newPhotos,
                existingPhotos: this.existingPhotos
            });
        }
    }));
});
</script>
