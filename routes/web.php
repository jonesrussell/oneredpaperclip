<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ItemMediaController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\TradeController;
use App\Models\Campaign;
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

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'featuredCampaigns' => $featuredCampaigns,
    ]);
})->name('home');

Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create')
    ->middleware(['auth', 'verified']);
Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store')
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
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
