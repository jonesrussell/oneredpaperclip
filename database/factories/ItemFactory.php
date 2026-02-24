<?php

namespace Database\Factories;

use App\Enums\ItemRole;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'itemable_type' => Campaign::class,
            'itemable_id' => Campaign::factory(),
            'role' => ItemRole::Start,
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Set the item role to goal.
     */
    public function goal(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ItemRole::Goal,
        ]);
    }

    /**
     * Set the item role to offered.
     */
    public function offered(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ItemRole::Offered,
        ]);
    }
}
