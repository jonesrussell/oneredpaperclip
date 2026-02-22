<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateCampaign;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignApiController extends Controller
{
    /**
     * List publicly visible active campaigns (for agents).
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 15), 50);

        $campaigns = Campaign::query()
            ->publicVisibility()
            ->active()
            ->with(['user:id,name', 'currentItem:id,title', 'goalItem:id,title'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Campaign $c) => $this->campaignToApi($c));

        return response()->json(['campaigns' => $campaigns]);
    }

    /**
     * List the authenticated user's campaigns.
     */
    public function mine(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 15), 50);

        $campaigns = Campaign::query()
            ->where('user_id', $request->user()->id)
            ->with(['user:id,name', 'currentItem:id,title', 'goalItem:id,title'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Campaign $c) => $this->campaignToApi($c));

        return response()->json(['campaigns' => $campaigns]);
    }

    /**
     * Show a single campaign by ID (public).
     */
    public function show(Campaign $campaign): JsonResponse
    {
        $campaign->load(['currentItem:id,title,description', 'goalItem:id,title,description', 'user:id,name']);

        $pendingOffersCount = $campaign->offers()->where('status', 'pending')->count();

        return response()->json([
            'campaign' => $this->campaignToApi($campaign) + [
                'description' => $campaign->story,
                'current_item' => $campaign->currentItem ? [
                    'id' => $campaign->currentItem->id,
                    'title' => $campaign->currentItem->title,
                    'description' => $campaign->currentItem->description,
                ] : null,
                'goal_item' => $campaign->goalItem ? [
                    'id' => $campaign->goalItem->id,
                    'title' => $campaign->goalItem->title,
                    'description' => $campaign->goalItem->description,
                ] : null,
                'pending_offers_count' => $pendingOffersCount,
            ],
        ]);
    }

    /**
     * Store a new campaign (authenticated).
     */
    public function store(StoreCampaignRequest $request, CreateCampaign $createCampaign): JsonResponse
    {
        $campaign = $createCampaign($request->validated(), $request->user());

        return response()->json([
            'campaign' => $this->campaignToApi($campaign),
            'message' => 'Campaign created.',
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function campaignToApi(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'status' => $campaign->status->value,
            'visibility' => $campaign->visibility->value,
            'user' => $campaign->relationLoaded('user') && $campaign->user
                ? ['id' => $campaign->user->id, 'name' => $campaign->user->name]
                : null,
            'current_item' => $campaign->relationLoaded('currentItem') && $campaign->currentItem
                ? ['id' => $campaign->currentItem->id, 'title' => $campaign->currentItem->title]
                : null,
            'goal_item' => $campaign->relationLoaded('goalItem') && $campaign->goalItem
                ? ['id' => $campaign->goalItem->id, 'title' => $campaign->goalItem->title]
                : null,
        ];
    }
}
