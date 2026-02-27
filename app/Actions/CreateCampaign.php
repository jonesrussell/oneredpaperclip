<?php

namespace App\Actions;

use App\Enums\CampaignStatus;
use App\Enums\CampaignVisibility;
use App\Models\Campaign;
use App\Models\Item;
use App\Models\User;
use App\Services\XpService;

class CreateCampaign
{
    public function __construct(
        private XpService $xpService
    ) {}

    /**
     * Create a campaign with start and goal items.
     *
     * @param  array{title?: string|null, story?: string|null, category_id?: int|null, status?: string|null, visibility?: string|null, start_item: array{title: string, description?: string|null}, goal_item: array{title: string, description?: string|null}}  $validated
     */
    public function __invoke(array $validated, User $user): Campaign
    {
        $status = isset($validated['status'])
            ? CampaignStatus::from($validated['status'])
            : CampaignStatus::Draft;
        $visibility = isset($validated['visibility'])
            ? CampaignVisibility::from($validated['visibility'])
            : CampaignVisibility::Public;

        $campaign = Campaign::create([
            'user_id' => $user->id,
            'category_id' => $validated['category_id'] ?? null,
            'status' => $status,
            'visibility' => $visibility,
            'title' => $validated['title'] ?? null,
            'story' => $validated['story'] ?? null,
            'current_item_id' => null,
            'goal_item_id' => null,
        ]);

        $startItem = Item::create([
            'itemable_type' => Campaign::class,
            'itemable_id' => $campaign->id,
            'role' => 'start',
            'title' => $validated['start_item']['title'],
            'description' => $validated['start_item']['description'] ?? null,
        ]);

        $goalItem = Item::create([
            'itemable_type' => Campaign::class,
            'itemable_id' => $campaign->id,
            'role' => 'goal',
            'title' => $validated['goal_item']['title'],
            'description' => $validated['goal_item']['description'] ?? null,
        ]);

        $campaign->update([
            'current_item_id' => $startItem->id,
            'goal_item_id' => $goalItem->id,
        ]);

        $this->xpService->awardCampaignCreation($user);

        return $campaign->load('items');
    }
}
