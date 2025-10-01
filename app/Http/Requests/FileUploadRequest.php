<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,csv,xlsx'
            ],
            'property_id' => 'nullable|exists:properties,id',
            'mission_id' => 'nullable|exists:missions,id',
            'checklist_id' => 'nullable|exists:checklists,id',
            'is_public' => 'boolean',
            'disk' => 'string|in:local,public,s3',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'File size cannot exceed 10MB.',
            'file.mimes' => 'File type not supported. Allowed types: jpg, jpeg, png, gif, pdf, doc, docx, txt, csv, xlsx.',
            'property_id.exists' => 'Selected property does not exist.',
            'mission_id.exists' => 'Selected mission does not exist.',
            'checklist_id.exists' => 'Selected checklist does not exist.',
        ];
    }
}
