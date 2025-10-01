<?php

namespace App\Http\Requests;

use App\Services\FileSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SecureFileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $fileSecurityService = app(FileSecurityService::class);
        $category = $this->route('category', 'default');
        $allowedTypes = $fileSecurityService->getAllowedTypes($category);
        
        // Convert MIME types to extensions for validation
        $allowedExtensions = $this->mimeTypesToExtensions($allowedTypes);
        
        return [
            'files' => 'required|array|max:10',
            'files.*' => [
                'required',
                'file',
                'max:' . ($this->getMaxFileSizeInKb($category)),
                Rule::in($allowedExtensions)->message('The file type is not allowed.')
            ],
            'category' => 'sometimes|string|in:images,documents,archives',
            'property_id' => 'sometimes|integer|exists:properties,id',
            'mission_id' => 'sometimes|integer|exists:missions,id',
            'description' => 'sometimes|string|max:500'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'files.required' => 'At least one file must be uploaded.',
            'files.array' => 'Files must be provided as an array.',
            'files.max' => 'You can upload a maximum of 10 files at once.',
            'files.*.required' => 'Each file is required.',
            'files.*.file' => 'Each upload must be a valid file.',
            'files.*.max' => 'Each file must not exceed the maximum allowed size.',
            'property_id.exists' => 'The selected property does not exist.',
            'mission_id.exists' => 'The selected mission does not exist.',
            'description.max' => 'The description must not exceed 500 characters.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fileSecurityService = app(FileSecurityService::class);
            $category = $this->input('category', 'default');
            
            if ($this->hasFile('files')) {
                foreach ($this->file('files') as $index => $file) {
                    if (!$file) continue;
                    
                    $validation = $fileSecurityService->validateFile($file, $category);
                    
                    if (!$validation['valid']) {
                        foreach ($validation['errors'] as $error) {
                            $validator->errors()->add("files.{$index}", $error);
                        }
                    }
                }
            }
        });
    }

    /**
     * Convert MIME types to file extensions.
     */
    private function mimeTypesToExtensions(array $mimeTypes): array
    {
        $mimeToExtension = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'image/svg+xml' => ['svg'],
            'application/pdf' => ['pdf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
            'text/plain' => ['txt'],
            'text/csv' => ['csv'],
            'application/zip' => ['zip'],
            'application/x-rar-compressed' => ['rar'],
            'application/x-7z-compressed' => ['7z']
        ];

        $extensions = [];
        foreach ($mimeTypes as $mimeType) {
            if (isset($mimeToExtension[$mimeType])) {
                $extensions = array_merge($extensions, $mimeToExtension[$mimeType]);
            }
        }

        return array_unique($extensions);
    }

    /**
     * Get maximum file size in KB for category.
     */
    private function getMaxFileSizeInKb(string $category): int
    {
        $maxSizes = [
            'images' => 10240, // 10MB
            'documents' => 51200, // 50MB
            'archives' => 102400, // 100MB
            'default' => 5120 // 5MB
        ];

        return $maxSizes[$category] ?? $maxSizes['default'];
    }
}