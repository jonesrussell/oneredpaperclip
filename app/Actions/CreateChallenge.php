<?php

namespace App\Actions;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\User;
use App\Services\XpService;

class CreateChallenge
{
    public function __construct(
        private XpService $xpService
    ) {}

    /**
     * Create a challenge with start and goal items.
     *
     * @param  array{title?: string|null, story?: string|null, category_id?: int|null, status?: string|null, visibility?: string|null, start_item: array{title: string, description?: string|null}, goal_item: array{title: string, description?: string|null}}  $validated
     */
    public function __invoke(array $validated, User $user): Challenge
    {
        $status = isset($validated['status'])
            ? ChallengeStatus::from($validated['status'])
            : ChallengeStatus::Draft;
        $visibility = isset($validated['visibility'])
            ? ChallengeVisibility::from($validated['visibility'])
            : ChallengeVisibility::Public;

        $challenge = Challenge::create([
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
            'itemable_type' => Challenge::class,
            'itemable_id' => $challenge->id,
            'role' => 'start',
            'title' => $validated['start_item']['title'],
            'description' => $validated['start_item']['description'] ?? null,
        ]);

        $goalItem = Item::create([
            'itemable_type' => Challenge::class,
            'itemable_id' => $challenge->id,
            'role' => 'goal',
            'title' => $validated['goal_item']['title'],
            'description' => $validated['goal_item']['description'] ?? null,
        ]);

        $challenge->update([
            'current_item_id' => $startItem->id,
            'goal_item_id' => $goalItem->id,
        ]);

        $this->xpService->awardChallengeCreation($user);

        return $challenge->load('items');
    }
}
