<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractTemplateRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['entry', 'exit'])],
            'content' => 'required|string|min:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du modèle de contrat est obligatoire.',
            'name.max' => 'Le nom du modèle de contrat ne peut pas dépasser 255 caractères.',
            'type.required' => 'Le type de contrat est obligatoire.',
            'type.in' => 'Le type de contrat doit être "entry" ou "exit".',
            'content.required' => 'Le contenu du contrat est obligatoire.',
            'content.min' => 'Le contenu du contrat doit contenir au moins 10 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom du modèle',
            'type' => 'type de contrat',
            'content' => 'contenu du contrat',
        ];
    }
}