<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'Electronics',
            'Books',
            'Art',
            'Collectibles',
            'Home & Garden',
            'Sports',
            'Clothing',
            'Toys & Games',
            'Other',
        ];

        foreach ($names as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
