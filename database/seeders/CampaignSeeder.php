<?php

namespace Database\Seeders;

use App\Actions\CreateCampaign;
use App\Enums\CampaignStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $categories = Category::all()->keyBy('id');

        if ($users->isEmpty()) {
            return;
        }

        $campaigns = [
            [
                'title' => 'One red paperclip to a house',
                'story' => 'Inspired by Kyle MacDonald — start with one red paperclip and trade up to a house.',
                'category' => 'Collectibles',
                'status' => CampaignStatus::Active,
                'start' => ['title' => 'One red paperclip', 'description' => 'A single standard red paperclip.'],
                'goal' => ['title' => 'A house', 'description' => 'A real house (or equivalent value).'],
            ],
            [
                'title' => 'Book swap to first edition',
                'story' => 'Trading paperbacks until I reach a first edition classic.',
                'category' => 'Books',
                'status' => CampaignStatus::Active,
                'start' => ['title' => 'Used paperback novel', 'description' => 'Good condition mass-market paperback.'],
                'goal' => ['title' => 'First edition classic', 'description' => 'First edition of a classic novel.'],
            ],
            [
                'title' => 'Sketch to oil painting',
                'story' => 'Trading art piece by piece to reach an original oil painting.',
                'category' => 'Art',
                'status' => CampaignStatus::Active,
                'start' => ['title' => 'Pencil sketch', 'description' => 'Original small pencil sketch on paper.'],
                'goal' => ['title' => 'Original oil painting', 'description' => 'Original oil on canvas.'],
            ],
            [
                'title' => 'Old phone to latest smartphone',
                'story' => 'Trading up through phones until I get the latest model.',
                'category' => 'Electronics',
                'status' => CampaignStatus::Draft,
                'start' => ['title' => 'Older smartphone', 'description' => 'Working smartphone, a few years old.'],
                'goal' => ['title' => 'Latest flagship smartphone', 'description' => 'Current-year flagship smartphone.'],
            ],
            [
                'title' => 'Garden seeds to greenhouse',
                'story' => 'From a packet of seeds to a small greenhouse.',
                'category' => 'Home & Garden',
                'status' => CampaignStatus::Active,
                'start' => ['title' => 'Packet of vegetable seeds', 'description' => 'Unopened packet of heirloom seeds.'],
                'goal' => ['title' => 'Small greenhouse', 'description' => 'Small backyard greenhouse or equivalent.'],
            ],
            [
                'title' => 'Trading card to signed memorabilia',
                'story' => 'Sports trading cards, trade by trade, toward signed memorabilia.',
                'category' => 'Collectibles',
                'status' => CampaignStatus::Active,
                'start' => ['title' => 'Common trading card', 'description' => 'Single common sports trading card.'],
                'goal' => ['title' => 'Signed memorabilia', 'description' => 'Authenticated signed sports memorabilia.'],
            ],
        ];

        $createCampaign = app(CreateCampaign::class);

        foreach ($campaigns as $index => $data) {
            $user = $users->get($index % $users->count());
            $category = $categories->firstWhere('name', $data['category']);

            $createCampaign([
                'title' => $data['title'],
                'story' => $data['story'],
                'category_id' => $category?->id,
                'status' => $data['status']->value,
                'visibility' => 'public',
                'start_item' => [
                    'title' => $data['start']['title'],
                    'description' => $data['start']['description'] ?? null,
                ],
                'goal_item' => [
                    'title' => $data['goal']['title'],
                    'description' => $data['goal']['description'] ?? null,
                ],
            ], $user);
        }
    }
}
