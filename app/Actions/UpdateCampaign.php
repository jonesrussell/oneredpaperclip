<?php

namespace App\Actions;

use App\Enums\CampaignStatus;
use App\Enums\CampaignVisibility;
use App\Models\Campaign;

class UpdateCampaign
{
    /**
     * Update campaign and its start/goal items.
     *
     * @param  array{title?: string|null, story?: string|null, category_id?: int|null, status?: string|null, visibility?: string|null, start_item: array{title: string, description?: string|null}, goal_item: array{title: string, description?: string|null}}  $validated
     */
    public function __invoke(Campaign $campaign, array $validated): Campaign
    {
        $status = isset($validated['status'])
            ? CampaignStatus::from($validated['status'])
            : $campaign->status;
        $visibility = isset($validated['visibility'])
            ? CampaignVisibility::from($validated['visibility'])
            : $campaign->visibility;

        $campaign->update([
            'category_id' => $validated['category_id'] ?? $campaign->category_id,
            'status' => $status,
            'visibility' => $visibility,
            'title' => $validated['title'] ?? $campaign->title,
            'story' => $validated['story'] ?? $campaign->story,
        ]);

        $startItem = $campaign->startItem;
        if ($startItem) {
            $startItem->update([
                'title' => $validated['start_item']['title'],
                'description' => $validated['start_item']['description'] ?? null,
            ]);
        }

        $goalItem = $campaign->goalItem;
        if ($goalItem) {
            $goalItem->update([
                'title' => $validated['goal_item']['title'],
                'description' => $validated['goal_item']['description'] ?? null,
            ]);
        }

        return $campaign->load(['items', 'currentItem', 'goalItem']);
    }
}
