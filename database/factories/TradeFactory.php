<?php

namespace Database\Factories;

use App\Enums\TradeStatus;
use App\Models\Campaign;
use App\Models\Item;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'offer_id' => Offer::factory(),
            'position' => 1,
            'offered_item_id' => Item::factory()->offered(),
            'received_item_id' => Item::factory(),
            'status' => TradeStatus::PendingConfirmation,
            'confirmed_by_offerer_at' => null,
            'confirmed_by_owner_at' => null,
        ];
    }

    /**
     * Set the trade as completed (both parties confirmed).
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TradeStatus::Completed,
            'confirmed_by_offerer_at' => now(),
            'confirmed_by_owner_at' => now(),
        ]);
    }

    /**
     * Set the trade as disputed.
     */
    public function disputed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TradeStatus::Disputed,
        ]);
    }
}
