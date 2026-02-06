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
            'internal_code' => 'nullable|string|max:6|regex:/^[a-zA-Z0-9]+$/',
            'internal_name' => 'required|string|max:255',
            'property_address' => 'required|string|max:255',
            'property_type' => 'required|string|in:classic,vip',
            'owner_name' => 'required|string|max:255',
            'owner_address' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:2000',
        ];
    }
}
