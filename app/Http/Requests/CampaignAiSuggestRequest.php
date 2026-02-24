<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CampaignAiSuggestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'context' => ['required', 'string', Rule::in(['start_item', 'goal_item', 'story'])],
            'current_text' => ['nullable', 'string', 'max:2000'],
            'title_hint' => ['nullable', 'string', 'max:255'],
        ];
    }
}
