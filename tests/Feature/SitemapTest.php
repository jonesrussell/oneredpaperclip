<?php

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeVisibility;
use App\Enums\ItemRole;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\User;

uses()->group('sitemap');

test('sitemap returns xml response with correct content type', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/xml');
});

test('sitemap contains static pages', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $response->assertSee(url('/'));
    $response->assertSee(route('about'));
    $response->assertSee(route('challenges.index'));
});

test('sitemap includes public active challenges', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'status' => ChallengeStatus::Active,
        'visibility' => ChallengeVisibility::Public,
        'title' => 'Sitemap Test Challenge',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => ItemRole::Start,
        'title' => 'A paperclip',
    ]);
    $challenge->update(['current_item_id' => $startItem->id]);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $response->assertSee(route('challenges.show', $challenge));
});

test('sitemap excludes draft challenges', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'status' => ChallengeStatus::Draft,
        'visibility' => ChallengeVisibility::Public,
        'title' => 'Draft Challenge',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $response->assertDontSee(route('challenges.show', $challenge));
});

test('sitemap excludes unlisted challenges', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'status' => ChallengeStatus::Active,
        'visibility' => ChallengeVisibility::Unlisted,
        'title' => 'Unlisted Challenge',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    $response->assertDontSee(route('challenges.show', $challenge));
});
