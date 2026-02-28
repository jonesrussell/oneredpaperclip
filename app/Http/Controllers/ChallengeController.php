<?php

namespace App\Http\Controllers;

use App\Actions\CreateChallenge;
use App\Actions\SuggestChallengeText;
use App\Actions\UpdateChallenge;
use App\Enums\ChallengeStatus;
use App\Enums\OfferStatus;
use App\Http\Requests\ChallengeAiSuggestRequest;
use App\Http\Requests\StoreChallengeRequest;
use App\Http\Requests\UpdateChallengeRequest;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Follow;
use App\Services\RichTextHtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;

class ChallengeController extends Controller
{
    /**
     * List challenges (explore); filter by category, status; paginate.
     */
    public function index(Request $request): Response
    {
        $query = Challenge::query()
            ->notDraft()
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with(['user', 'currentItem.media', 'goalItem.media'])
            ->latest();

        $challenges = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return Inertia::render('challenges/Index', [
            'challenges' => $challenges,
            'categories' => $categories,
            'meta' => [
                'title' => 'Explore Challenges — '.config('app.name'),
                'description' => 'Browse active trade-up challenges. Find something to trade and help others reach their goals.',
            ],
        ]);
    }

    /**
     * List the authenticated user's challenges.
     */
    public function myChallenges(Request $request): Response
    {
        $challenges = Challenge::query()
            ->where('user_id', $request->user()->id)
            ->with(['user', 'currentItem.media', 'goalItem.media'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('dashboard/challenges/Index', [
            'challenges' => $challenges,
        ]);
    }

    /**
     * Show the form for creating a new challenge.
     */
    public function create(): Response
    {
        $categories = Category::orderBy('name')->get();

        return Inertia::render('challenges/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created challenge.
     */
    public function store(StoreChallengeRequest $request, CreateChallenge $createChallenge): RedirectResponse
    {
        $challenge = $createChallenge($request->validated(), $request->user());

        return redirect()->route('challenges.show', $challenge);
    }

    /**
     * Show the form for editing the challenge (owner only).
     */
    public function edit(Request $request, Challenge $challenge): Response
    {
        $this->authorize('update', $challenge);

        $challenge->load(['startItem', 'goalItem']);
        $categories = Category::orderBy('name')->get();

        return Inertia::render('challenges/Edit', [
            'challenge' => $challenge,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified challenge (owner only).
     */
    public function update(UpdateChallengeRequest $request, Challenge $challenge, UpdateChallenge $updateChallenge): RedirectResponse
    {
        $this->authorize('update', $challenge);

        $updateChallenge($challenge, $request->validated());

        return redirect()->route('challenges.show', $challenge);
    }

    /**
     * Display the specified challenge.
     */
    public function show(Request $request, Challenge $challenge): Response
    {
        if ($challenge->status === ChallengeStatus::Draft) {
            if (! $request->user() || $request->user()->id !== $challenge->user_id) {
                abort(404);
            }
        }

        $challenge->load([
            'items',
            'trades' => fn ($q) => $q->with(['offeredItem.media', 'offer.fromUser'])->orderBy('position'),
            'offers' => fn ($q) => $q->with(['offeredItem.media', 'fromUser'])->where('status', OfferStatus::Pending),
            'comments' => fn ($q) => $q->with('user')->latest()->limit(20),
            'user',
            'category',
            'currentItem.media',
            'goalItem.media',
        ]);

        $isFollowing = $request->user()
            ? Follow::query()
                ->where('user_id', $request->user()->id)
                ->where('followable_type', Challenge::class)
                ->where('followable_id', $challenge->id)
                ->exists()
            : false;

        $sanitizer = app(RichTextHtmlSanitizer::class);
        $challenge->setAttribute('story_safe', $sanitizer->sanitize($challenge->story ?? ''));

        $challenge->trades->transform(function ($trade) {
            $trade->setAttribute('owner_confirmed', (bool) $trade->confirmed_by_owner_at);
            $trade->setAttribute('offerer_confirmed', (bool) $trade->confirmed_by_offerer_at);
            $trade->setAttribute('offerer', $trade->offer?->fromUser?->only('id', 'name'));

            return $trade;
        });

        $description = $challenge->story
            ? \Illuminate\Support\Str::limit(strip_tags($challenge->story), 160)
            : sprintf(
                '%s → %s',
                $challenge->currentItem?->title ?? 'Start',
                $challenge->goalItem?->title ?? 'Goal'
            );

        $challengeTitle = $challenge->title ?? 'Challenge';
        $ogImage = $challenge->currentItem?->image_url ?? config('seo.og_image');

        return Inertia::render('challenges/Show', [
            'challenge' => $challenge,
            'isFollowing' => $isFollowing,
            'meta' => [
                'title' => $challengeTitle.' — '.config('app.name'),
                'description' => $description,
                'og_type' => 'article',
                'image' => $ogImage,
                'schema' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $challengeTitle,
                    'description' => $description,
                    'author' => [
                        '@type' => 'Person',
                        'name' => $challenge->user?->name ?? 'Anonymous',
                    ],
                    'datePublished' => $challenge->created_at?->toIso8601String(),
                    'dateModified' => $challenge->updated_at?->toIso8601String(),
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => config('app.name'),
                        'url' => config('app.url'),
                    ],
                ],
            ],
        ]);
    }

    /**
     * AI-assisted text suggestion for challenge create/edit (start item, goal item, story).
     */
    public function aiSuggest(ChallengeAiSuggestRequest $request, SuggestChallengeText $suggest): JsonResponse
    {
        try {
            $suggestion = $suggest($request->validated());
        } catch (RateLimitedException $e) {
            report($e);

            return response()->json([
                'message' => 'AI service is rate limited. Please wait a moment and try again.',
            ], 429);
        } catch (ProviderOverloadedException $e) {
            report($e);

            return response()->json([
                'message' => 'AI service is temporarily overloaded. Please try again shortly.',
            ], 503);
        } catch (AiException|\RuntimeException $e) {
            report($e);

            return response()->json([
                'message' => 'Unable to generate AI suggestion. Please try again or write your description manually.',
            ], 502);
        }

        return response()->json(['suggestion' => $suggestion]);
    }
}
