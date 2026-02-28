<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'offered_item' => ['required', 'array'],
            'offered_item.title' => ['required', 'string', 'max:255'],
            'offered_item.description' => ['nullable', 'string', 'max:2000'],
            'offered_item.image' => ['nullable', 'file', 'image', 'max:5120'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'offered_item.title' => 'offered item title',
            'offered_item.description' => 'offered item description',
        ];
    }
}
