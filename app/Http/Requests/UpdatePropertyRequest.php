<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $propertyId = $this->route('property') ? $this->route('property')->id : null;
        
        return [
            'internal_code' => 'nullable|string|max:50|unique:properties,internal_code,' . $propertyId . '|regex:/^[a-zA-Z0-9_-]+$/',
            'property_address' => 'required|string|max:255|unique:properties,property_address,' . $propertyId,
            'owner_name' => 'nullable|string|max:255',
            'owner_address' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:2000',
        ];
    }
}
