<?php

namespace App\Http\Controllers;

use App\Enums\ChallengeStatus;
use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Offer;
use App\Models\Trade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $activeChallengesCount = $user->challenges()
            ->where('status', ChallengeStatus::Active)
            ->count();

        $pendingOffersCount = Offer::query()
            ->where('status', OfferStatus::Pending)
            ->whereHas('challenge', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        $completedTradesCount = Trade::query()
            ->where('status', TradeStatus::Completed)
            ->where(function ($q) use ($user) {
                $q->whereHas('challenge', fn ($c) => $c->where('user_id', $user->id))
                    ->orWhereHas('offer', fn ($o) => $o->where('from_user_id', $user->id));
            })
            ->count();

        return Inertia::render('Dashboard', [
            'activeChallengesCount' => $activeChallengesCount,
            'completedTradesCount' => $completedTradesCount,
            'pendingOffersCount' => $pendingOffersCount,
        ]);
    }
}
