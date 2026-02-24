<?php

use App\Enums\ItemRole;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Item;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Offer;
use App\Models\Trade;

it('creates a category via factory', function () {
    $category = Category::factory()->create();

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->name)->not->toBeEmpty()
        ->and($category->slug)->not->toBeEmpty();
});

it('creates a campaign via factory', function () {
    $campaign = Campaign::factory()->create();

    expect($campaign)->toBeInstanceOf(Campaign::class)
        ->and($campaign->title)->not->toBeEmpty()
        ->and($campaign->user)->not->toBeNull()
        ->and($campaign->category)->not->toBeNull();
});

it('creates a campaign with draft state', function () {
    $campaign = Campaign::factory()->draft()->create();

    expect($campaign->status->value)->toBe('draft');
});

it('creates items with different roles', function () {
    $start = Item::factory()->create();
    $goal = Item::factory()->goal()->create();
    $offered = Item::factory()->offered()->create();

    expect($start->role)->toBe(ItemRole::Start)
        ->and($goal->role)->toBe(ItemRole::Goal)
        ->and($offered->role)->toBe(ItemRole::Offered);
});

it('creates an offer via factory', function () {
    $offer = Offer::factory()->create();

    expect($offer)->toBeInstanceOf(Offer::class)
        ->and($offer->status->value)->toBe('pending');
});

it('creates a trade via factory', function () {
    $trade = Trade::factory()->create();

    expect($trade)->toBeInstanceOf(Trade::class)
        ->and($trade->status->value)->toBe('pending_confirmation');
});

it('creates a completed trade via factory', function () {
    $trade = Trade::factory()->completed()->create();

    expect($trade->status->value)->toBe('completed')
        ->and($trade->confirmed_by_offerer_at)->not->toBeNull()
        ->and($trade->confirmed_by_owner_at)->not->toBeNull();
});

it('creates a comment via factory', function () {
    $comment = Comment::factory()->create();

    expect($comment)->toBeInstanceOf(Comment::class)
        ->and($comment->body)->not->toBeEmpty();
});

it('creates a follow via factory', function () {
    $follow = Follow::factory()->create();

    expect($follow)->toBeInstanceOf(Follow::class)
        ->and($follow->user)->not->toBeNull();
});

it('creates a media via factory', function () {
    $media = Media::factory()->create();

    expect($media)->toBeInstanceOf(Media::class)
        ->and($media->file_name)->not->toBeEmpty();
});

it('creates a notification via factory', function () {
    $notification = Notification::factory()->create();

    expect($notification)->toBeInstanceOf(Notification::class)
        ->and($notification->read_at)->toBeNull();
});

it('creates a read notification via factory', function () {
    $notification = Notification::factory()->read()->create();

    expect($notification->read_at)->not->toBeNull();
});
