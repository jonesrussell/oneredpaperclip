<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateChallenge;
use App\Enums\ChallengeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChallengeRequest;
use App\Models\Challenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeApiController extends Controller
{
    /**
     * List publicly visible active challenges (for agents).
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 15), 50);

        $challenges = Challenge::query()
            ->publicVisibility()
            ->active()
            ->with(['user:id,name', 'currentItem:id,title', 'goalItem:id,title'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Challenge $c) => $this->challengeToApi($c));

        return response()->json(['challenges' => $challenges]);
    }

    /**
     * List the authenticated user's challenges.
     */
    public function mine(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 15), 50);

        $challenges = Challenge::query()
            ->where('user_id', $request->user()->id)
            ->with(['user:id,name', 'currentItem:id,title', 'goalItem:id,title'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Challenge $c) => $this->challengeToApi($c));

        return response()->json(['challenges' => $challenges]);
    }

    /**
     * Show a single challenge by ID (public; drafts only for owner).
     */
    public function show(Request $request, Challenge $challenge): JsonResponse
    {
        if ($challenge->status === ChallengeStatus::Draft) {
            if (! $request->user() || $request->user()->id !== $challenge->user_id) {
                abort(404);
            }
        }

        $challenge->load(['currentItem:id,title,description', 'goalItem:id,title,description', 'user:id,name']);

        $pendingOffersCount = $challenge->offers()->where('status', 'pending')->count();

        return response()->json([
            'challenge' => $this->challengeToApi($challenge) + [
                'description' => $challenge->story,
                'current_item' => $challenge->currentItem ? [
                    'id' => $challenge->currentItem->id,
                    'title' => $challenge->currentItem->title,
                    'description' => $challenge->currentItem->description,
                ] : null,
                'goal_item' => $challenge->goalItem ? [
                    'id' => $challenge->goalItem->id,
                    'title' => $challenge->goalItem->title,
                    'description' => $challenge->goalItem->description,
                ] : null,
                'pending_offers_count' => $pendingOffersCount,
            ],
        ]);
    }

    /**
     * Store a new challenge (authenticated).
     */
    public function store(StoreChallengeRequest $request, CreateChallenge $createChallenge): JsonResponse
    {
        $challenge = $createChallenge($request->validated(), $request->user());

        return response()->json([
            'challenge' => $this->challengeToApi($challenge),
            'message' => 'Challenge created.',
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function challengeToApi(Challenge $challenge): array
    {
        return [
            'id' => $challenge->id,
            'title' => $challenge->title,
            'status' => $challenge->status->value,
            'visibility' => $challenge->visibility->value,
            'user' => $challenge->relationLoaded('user') && $challenge->user
                ? ['id' => $challenge->user->id, 'name' => $challenge->user->name]
                : null,
            'current_item' => $challenge->relationLoaded('currentItem') && $challenge->currentItem
                ? ['id' => $challenge->currentItem->id, 'title' => $challenge->currentItem->title]
                : null,
            'goal_item' => $challenge->relationLoaded('goalItem') && $challenge->goalItem
                ? ['id' => $challenge->goalItem->id, 'title' => $challenge->goalItem->title]
                : null,
        ];
    }
}
