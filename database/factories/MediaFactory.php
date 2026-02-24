<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->uuid().'.jpg';

        return [
            'model_type' => Item::class,
            'model_id' => Item::factory(),
            'collection_name' => 'images',
            'file_name' => $fileName,
            'disk' => 'public',
            'path' => 'items/'.$fileName,
            'size' => fake()->numberBetween(10000, 5000000),
        ];
    }
}
