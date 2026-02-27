<?php

namespace App\Actions;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use App\Models\Challenge;

class UpdateChallenge
{
    /**
     * Update challenge and its start/goal items.
     *
     * @param  array{title?: string|null, story?: string|null, category_id?: int|null, status?: string|null, visibility?: string|null, start_item: array{title: string, description?: string|null}, goal_item: array{title: string, description?: string|null}}  $validated
     */
    public function __invoke(Challenge $challenge, array $validated): Challenge
    {
        $status = isset($validated['status'])
            ? ChallengeStatus::from($validated['status'])
            : $challenge->status;
        $visibility = isset($validated['visibility'])
            ? ChallengeVisibility::from($validated['visibility'])
            : $challenge->visibility;

        $challenge->update([
            'category_id' => $validated['category_id'] ?? $challenge->category_id,
            'status' => $status,
            'visibility' => $visibility,
            'title' => $validated['title'] ?? $challenge->title,
            'story' => $validated['story'] ?? $challenge->story,
        ]);

        $startItem = $challenge->startItem;
        if ($startItem) {
            $startItem->update([
                'title' => $validated['start_item']['title'],
                'description' => $validated['start_item']['description'] ?? null,
            ]);
        }

        $goalItem = $challenge->goalItem;
        if ($goalItem) {
            $goalItem->update([
                'title' => $validated['goal_item']['title'],
                'description' => $validated['goal_item']['description'] ?? null,
            ]);
        }

        return $challenge->load(['items', 'currentItem', 'goalItem']);
    }
}
