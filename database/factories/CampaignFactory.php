<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Enums\CampaignVisibility;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'status' => CampaignStatus::Active,
            'visibility' => CampaignVisibility::Public,
            'title' => fake()->sentence(4),
            'story' => '<p>'.fake()->paragraph().'</p>',
        ];
    }

    /**
     * Set the campaign as a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Draft,
        ]);
    }

    /**
     * Set the campaign as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Completed,
        ]);
    }

    /**
     * Set the campaign as paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Paused,
        ]);
    }

    /**
     * Set the campaign as unlisted.
     */
    public function unlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => CampaignVisibility::Unlisted,
        ]);
    }
}
