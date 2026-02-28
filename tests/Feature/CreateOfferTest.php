<?php

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses()->group('offers');

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->offerer = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Red paperclip to house',
        'story' => 'Starting with one red paperclip.',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);
    $challenge->update(['current_item_id' => $startItem->id]);
    $this->challenge = $challenge->fresh();
});

test('authenticated user can create offer and sees flash message', function () {
    $response = $this->actingAs($this->offerer)->post(
        route('challenges.offers.store', $this->challenge),
        [
            'offered_item' => [
                'title' => 'A pen',
                'description' => 'Blue ballpoint.',
            ],
            'message' => 'I offer my pen.',
        ]
    );

    $response->assertRedirect()
        ->assertSessionHas('success', 'Offer submitted!');
});

test('offer can include an image for the offered item', function () {
    Storage::fake('public');

    $response = $this->actingAs($this->offerer)->post(
        route('challenges.offers.store', $this->challenge),
        [
            'offered_item' => [
                'title' => 'A pen',
                'description' => 'Blue ballpoint.',
                'image' => UploadedFile::fake()->image('pen.jpg', 640, 480),
            ],
            'message' => 'I offer my pen.',
        ]
    );

    $response->assertRedirect();

    $offer = \App\Models\Offer::where('challenge_id', $this->challenge->id)->latest()->first();
    $item = $offer->offeredItem;

    expect($item->media)->toHaveCount(1);
    Storage::disk('public')->assertExists($item->media->first()->path);
});

test('guest cannot create offer', function () {
    $response = $this->post(
        route('challenges.offers.store', $this->challenge),
        [
            'offered_item' => ['title' => 'A pen'],
        ]
    );

    $response->assertRedirect('/login');
});
