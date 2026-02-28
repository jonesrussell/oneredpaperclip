<?php

use App\Http\Controllers\Api\ChallengeApiController;
use App\Http\Controllers\Api\OfferApiController;
use App\Http\Controllers\Api\TradeApiController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemMediaController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\TradeController;
use App\Models\Challenge;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    try {
        $featuredChallenges = Challenge::query()
            ->publicVisibility()
            ->active()
            ->with(['user', 'currentItem.media', 'goalItem.media'])
            ->latest()
            ->limit(6)
            ->get();
    } catch (\Throwable $e) {
        report($e);
        $featuredChallenges = collect();
    }

    $stats = [
        'challengesCount' => Challenge::query()->publicVisibility()->active()->count(),
        'tradesCount' => Trade::query()->count(),
        'usersCount' => User::query()->count(),
    ];

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'featuredChallenges' => $featuredChallenges,
        'stats' => $stats,
        'meta' => [
            'title' => 'One Red Paperclip — Trade up from one thing to something better',
            'description' => config('seo.description'),
        ],
    ]);
})->name('home');

Route::get('about', fn () => Inertia::render('About', [
    'meta' => [
        'title' => 'About — One Red Paperclip',
        'description' => 'The story behind One Red Paperclip: from Kyle MacDonald\'s red paperclip to a house, and the trade-up platform it inspired.',
    ],
]))->name('about');

Route::get('challenges', [ChallengeController::class, 'index'])->name('challenges.index');
Route::get('challenges/create', [ChallengeController::class, 'create'])->name('challenges.create')
    ->middleware(['auth', 'verified']);
Route::post('challenges/ai-suggest', [ChallengeController::class, 'aiSuggest'])->name('challenges.ai-suggest')
    ->middleware(['auth', 'verified', 'throttle:15,1']);
Route::get('challenges/{challenge}', [ChallengeController::class, 'show'])->name('challenges.show');
Route::get('challenges/{challenge}/edit', [ChallengeController::class, 'edit'])->name('challenges.edit')
    ->middleware(['auth', 'verified']);
Route::post('challenges', [ChallengeController::class, 'store'])->name('challenges.store')
    ->middleware(['auth', 'verified']);
Route::put('challenges/{challenge}', [ChallengeController::class, 'update'])->name('challenges.update')
    ->middleware(['auth', 'verified']);
Route::post('challenges/{challenge}/offers', [OfferController::class, 'store'])->name('challenges.offers.store')
    ->middleware(['auth', 'verified']);
Route::post('offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept')
    ->middleware(['auth', 'verified']);
Route::post('offers/{offer}/decline', [OfferController::class, 'decline'])->name('offers.decline')
    ->middleware(['auth', 'verified']);
Route::post('trades/{trade}/confirm', [TradeController::class, 'confirm'])->name('trades.confirm')
    ->middleware(['auth', 'verified']);
Route::post('items/{item}/media', [ItemMediaController::class, 'store'])->name('items.media.store')
    ->middleware(['auth', 'verified']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('dashboard/challenges', [ChallengeController::class, 'myChallenges'])->name('dashboard.challenges');
});

// WebMCP / agent API (JSON only; same session auth as web)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('challenges', [ChallengeApiController::class, 'index'])->name('challenges.index');
    Route::get('challenges/mine', [ChallengeApiController::class, 'mine'])->name('challenges.mine')
        ->middleware(['auth', 'verified']);
    Route::get('challenges/{challenge}', [ChallengeApiController::class, 'show'])->name('challenges.show');
    Route::post('challenges', [ChallengeApiController::class, 'store'])->name('challenges.store')
        ->middleware(['auth', 'verified']);
    Route::post('challenges/{challenge}/offers', [OfferApiController::class, 'store'])->name('challenges.offers.store')
        ->middleware(['auth', 'verified']);
    Route::post('offers/{offer}/accept', [OfferApiController::class, 'accept'])->name('offers.accept')
        ->middleware(['auth', 'verified']);
    Route::post('offers/{offer}/decline', [OfferApiController::class, 'decline'])->name('offers.decline')
        ->middleware(['auth', 'verified']);
    Route::post('trades/{trade}/confirm', [TradeApiController::class, 'confirm'])->name('trades.confirm')
        ->middleware(['auth', 'verified']);
});

require __DIR__.'/settings.php';
