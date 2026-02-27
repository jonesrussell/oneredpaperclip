<?php

namespace App\Http\Requests;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChallengeRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in([ChallengeStatus::Draft->value, ChallengeStatus::Active->value])],
            'visibility' => ['nullable', 'string', Rule::in(array_map(fn ($c) => $c->value, ChallengeVisibility::cases()))],
            'start_item' => ['required', 'array'],
            'start_item.title' => ['required', 'string', 'max:255'],
            'start_item.description' => ['nullable', 'string', 'max:2000'],
            'goal_item' => ['required', 'array'],
            'goal_item.title' => ['required', 'string', 'max:255'],
            'goal_item.description' => ['nullable', 'string', 'max:2000'],
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
            'start_item.title' => 'start item title',
            'start_item.description' => 'start item description',
            'goal_item.title' => 'goal item title',
            'goal_item.description' => 'goal item description',
        ];
    }
}
