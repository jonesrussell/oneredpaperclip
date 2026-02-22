<?php

namespace App\Http\Controllers;

use App\Actions\CreateCampaign;
use App\Enums\OfferStatus;
use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Follow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * List campaigns (explore); filter by category, status; paginate.
     */
    public function index(Request $request): Response
    {
        $query = Campaign::query()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with(['user', 'currentItem', 'goalItem'])
            ->latest();

        $campaigns = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return Inertia::render('campaigns/Index', [
            'campaigns' => $campaigns,
            'categories' => $categories,
        ]);
    }

    /**
     * List the authenticated user's campaigns.
     */
    public function myCampaigns(Request $request): Response
    {
        $campaigns = Campaign::query()
            ->where('user_id', $request->user()->id)
            ->with(['user', 'currentItem', 'goalItem'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('dashboard/campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create(): Response
    {
        $categories = Category::orderBy('name')->get();

        return Inertia::render('campaigns/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request, CreateCampaign $createCampaign): RedirectResponse
    {
        $campaign = $createCampaign($request->validated(), $request->user());

        return redirect()->route('campaigns.show', $campaign);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Request $request, Campaign $campaign): Response
    {
        $campaign->load([
            'items',
            'trades' => fn ($q) => $q->with('offeredItem')->orderBy('position'),
            'offers' => fn ($q) => $q->where('status', OfferStatus::Pending),
            'comments' => fn ($q) => $q->with('user')->latest()->limit(20),
            'user',
            'category',
            'currentItem',
            'goalItem',
        ]);

        $isFollowing = $request->user()
            ? Follow::query()
                ->where('user_id', $request->user()->id)
                ->where('followable_type', Campaign::class)
                ->where('followable_id', $campaign->id)
                ->exists()
            : false;

        return Inertia::render('campaigns/Show', [
            'campaign' => $campaign,
            'isFollowing' => $isFollowing,
        ]);
    }
}
