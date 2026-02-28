<?php

namespace Database\Factories;

use App\Enums\OfferStatus;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'challenge_id' => Challenge::factory(),
            'from_user_id' => User::factory(),
            'offered_item_id' => Item::factory()->offered(),
            'for_challenge_item_id' => null,
            'message' => fake()->optional()->sentence(),
            'status' => OfferStatus::Pending,
            'expires_at' => null,
        ];
    }

    /**
     * Set the offer as accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatus::Accepted,
        ]);
    }

    /**
     * Set the offer as declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatus::Declined,
        ]);
    }

    /**
     * Set the offer as withdrawn.
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OfferStatus::Withdrawn,
        ]);
    }
}
