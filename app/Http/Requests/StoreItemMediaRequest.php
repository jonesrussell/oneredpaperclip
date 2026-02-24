<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only the challenge (campaign) owner can add media to an item.
     */
    public function authorize(): bool
    {
        $item = $this->route('item');
        $item->loadMissing('itemable');

        $campaign = $item->itemable;
        if (! $campaign instanceof Campaign) {
            return false;
        }

        return $campaign->user_id === $this->user()?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:5120'],
        ];
    }
}
