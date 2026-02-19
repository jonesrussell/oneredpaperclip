<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'story' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status' => ['nullable', 'string', Rule::in([CampaignStatus::Draft->value, CampaignStatus::Active->value])],
        ];
    }
}
