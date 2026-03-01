<?php

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use App\Enums\ItemRole;
use App\Enums\OfferStatus;
use App\Enums\TradeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses()->group('trades');

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');

    $this->owner = User::factory()->create();
    $this->offerer = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics-'.uniqid()]);
    $challenge = Challenge::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'status' => ChallengeStatus::Active,
        'visibility' => ChallengeVisibility::Public,
        'title' => 'Red paperclip to house',
        'story' => 'Starting with one red paperclip.',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => ItemRole::Start,
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);
    $challenge->update(['current_item_id' => $startItem->id]);
    $this->challenge = $challenge->fresh();

    $offeredItem = Item::create([
        'itemable_type' => Offer::class,
        'itemable_id' => 0,
        'role' => ItemRole::Offered,
        'title' => 'A pen',
        'description' => 'Blue ballpoint.',
    ]);
    $this->offer = Offer::create([
        'challenge_id' => $this->challenge->id,
        'from_user_id' => $this->offerer->id,
        'offered_item_id' => $offeredItem->id,
        'for_challenge_item_id' => $this->challenge->current_item_id,
        'message' => 'I offer my pen.',
        'status' => OfferStatus::Pending,
    ]);
    $offeredItem->update(['itemable_id' => $this->offer->id]);

    $this->actingAs($this->owner)->post(route('offers.accept', $this->offer));
    $this->trade = Trade::where('offer_id', $this->offer->id)->first();
});

test('owner can update trade item title', function () {
    $response = $this->actingAs($this->owner)->patch(route('trades.update', $this->trade), [
        'title' => 'A nicer geode rock',
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success', 'Trade item updated.');
    expect($this->trade->fresh()->offeredItem->title)->toBe('A nicer geode rock');
});

test('owner can update trade item description', function () {
    $response = $this->actingAs($this->owner)->patch(route('trades.update', $this->trade), [
        'title' => 'A pen',
        'description' => 'Updated description for the trade item.',
    ]);

    $response->assertRedirect();
    expect($this->trade->fresh()->offeredItem->description)->toBe('Updated description for the trade item.');
});

test('owner can update trade item image', function () {
    $image = UploadedFile::fake()->image('geode.jpg', 400, 400);

    $response = $this->actingAs($this->owner)->patch(route('trades.update', $this->trade), [
        'title' => 'A geode rock',
        'image' => $image,
    ]);

    $response->assertRedirect();
    $this->trade->refresh();
    expect($this->trade->offeredItem->media()->count())->toBe(1);
    Storage::disk('public')->assertExists($this->trade->offeredItem->media->first()->path);
});

test('offerer cannot update trade', function () {
    $response = $this->actingAs($this->offerer)->patch(route('trades.update', $this->trade), [
        'title' => 'Hacked title',
    ]);

    $response->assertForbidden();
    expect($this->trade->fresh()->offeredItem->title)->toBe('A pen');
});

test('other user cannot update trade', function () {
    $other = User::factory()->create();

    $response = $this->actingAs($other)->patch(route('trades.update', $this->trade), [
        'title' => 'Hacked title',
    ]);

    $response->assertForbidden();
});

test('owner cannot update completed trade', function () {
    $this->trade->update(['status' => TradeStatus::Completed]);

    $response = $this->actingAs($this->owner)->patch(route('trades.update', $this->trade), [
        'title' => 'Updated title',
    ]);

    $response->assertForbidden();
    expect($this->trade->fresh()->offeredItem->title)->toBe('A pen');
});

test('unauthenticated user cannot update trade', function () {
    auth()->logout();

    $response = $this->patch(route('trades.update', $this->trade), [
        'title' => 'Hacked title',
    ]);

    $response->assertRedirect(route('login'));
});
