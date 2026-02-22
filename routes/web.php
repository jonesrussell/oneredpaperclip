<?php

use App\Http\Controllers\Api\CampaignApiController;
use App\Http\Controllers\Api\OfferApiController;
use App\Http\Controllers\Api\TradeApiController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemMediaController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\TradeController;
use App\Models\Campaign;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    try {
        $featuredCampaigns = Campaign::query()
            ->publicVisibility()
            ->active()
            ->with(['user', 'currentItem', 'goalItem'])
            ->latest()
            ->limit(6)
            ->get();
    } catch (\Throwable $e) {
        report($e);
        $featuredCampaigns = collect();
    }

    $stats = [
        'campaignsCount' => Campaign::query()->publicVisibility()->active()->count(),
        'tradesCount' => Trade::query()->count(),
        'usersCount' => User::query()->count(),
    ];

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'featuredCampaigns' => $featuredCampaigns,
        'stats' => $stats,
    ]);
})->name('home');

Route::get('about', fn () => Inertia::render('About'))->name('about');

Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create')
    ->middleware(['auth', 'verified']);
Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::get('campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit')
    ->middleware(['auth', 'verified']);
Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store')
    ->middleware(['auth', 'verified']);
Route::put('campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update')
    ->middleware(['auth', 'verified']);
Route::post('campaigns/{campaign}/offers', [OfferController::class, 'store'])->name('campaigns.offers.store')
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
    Route::get('dashboard/campaigns', [CampaignController::class, 'myCampaigns'])->name('dashboard.campaigns');
});

// WebMCP / agent API (JSON only; same session auth as web)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('campaigns', [CampaignApiController::class, 'index'])->name('campaigns.index');
    Route::get('campaigns/mine', [CampaignApiController::class, 'mine'])->name('campaigns.mine')
        ->middleware(['auth', 'verified']);
    Route::get('campaigns/{campaign}', [CampaignApiController::class, 'show'])->name('campaigns.show');
    Route::post('campaigns', [CampaignApiController::class, 'store'])->name('campaigns.store')
        ->middleware(['auth', 'verified']);
    Route::post('campaigns/{campaign}/offers', [OfferApiController::class, 'store'])->name('campaigns.offers.store')
        ->middleware(['auth', 'verified']);
    Route::post('offers/{offer}/accept', [OfferApiController::class, 'accept'])->name('offers.accept')
        ->middleware(['auth', 'verified']);
    Route::post('offers/{offer}/decline', [OfferApiController::class, 'decline'])->name('offers.decline')
        ->middleware(['auth', 'verified']);
    Route::post('trades/{trade}/confirm', [TradeApiController::class, 'confirm'])->name('trades.confirm')
        ->middleware(['auth', 'verified']);
});

require __DIR__.'/settings.php';
