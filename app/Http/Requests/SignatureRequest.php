<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignatureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'signature' => [
                'required',
                'string',
                'min:10',
                function ($attribute, $value, $fail) {
                    // Validate that it's a proper base64 image
                    if (strpos($value, 'data:image/') !== 0) {
                        $fail('La signature doit être une image valide.');
                        return;
                    }

                    // Extract the base64 part
                    $base64 = substr($value, strpos($value, ',') + 1);
                    
                    // Validate base64
                    if (!base64_decode($base64, true)) {
                        $fail('La signature doit être une image encodée en base64 valide.');
                        return;
                    }

                    // Check image size (optional - prevent huge signatures)
                    $imageSize = strlen($base64) * 3 / 4; // Approximate size in bytes
                    if ($imageSize > 1024 * 1024) { // 1MB limit
                        $fail('La signature est trop volumineuse. Taille maximale : 1MB.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'signature.required' => 'La signature est obligatoire.',
            'signature.min' => 'La signature doit être valide.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'signature' => 'signature électronique',
        ];
    }
}