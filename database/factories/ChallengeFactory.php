<?php

namespace Database\Factories;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use App\Enums\ItemRole;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Challenge>
 */
class ChallengeFactory extends Factory
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
            'status' => ChallengeStatus::Active,
            'visibility' => ChallengeVisibility::Public,
            'title' => fake()->sentence(4),
            'story' => '<p>'.fake()->paragraph().'</p>',
        ];
    }

    /**
     * Set the challenge as a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChallengeStatus::Draft,
        ]);
    }

    /**
     * Set the challenge as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChallengeStatus::Completed,
        ]);
    }

    /**
     * Set the challenge as paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChallengeStatus::Paused,
        ]);
    }

    /**
     * Set the challenge as unlisted.
     */
    public function unlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => ChallengeVisibility::Unlisted,
        ]);
    }

    /**
     * Create challenge with start and goal items, setting current_item_id.
     */
    public function withItems(): static
    {
        return $this->afterCreating(function (Challenge $challenge) {
            $startItem = Item::create([
                'itemable_type' => Challenge::class,
                'itemable_id' => $challenge->id,
                'role' => ItemRole::Start,
                'title' => fake()->words(3, true),
                'description' => fake()->sentence(),
            ]);

            $goalItem = Item::create([
                'itemable_type' => Challenge::class,
                'itemable_id' => $challenge->id,
                'role' => ItemRole::Goal,
                'title' => fake()->words(3, true),
                'description' => fake()->sentence(),
            ]);

            $challenge->update([
                'current_item_id' => $startItem->id,
                'goal_item_id' => $goalItem->id,
            ]);
        });
    }
}
