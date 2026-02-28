<?php

use App\Models\Category;
use App\Models\Challenge;
use App\Models\Item;
use App\Models\User;

uses()->group('challenges');

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('get challenge create page requires auth', function () {
    $response = $this->get(route('challenges.create'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can get challenge create page', function () {
    Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    Category::create(['name' => 'Collectibles', 'slug' => 'collectibles']);

    $response = $this->actingAs($this->user)->get(route('challenges.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('challenges/Create')
        ->has('categories', 2)
    );
});

test('post valid challenge redirects to challenge show', function () {
    $response = $this->actingAs($this->user)->post(route('challenges.store'), [
        'title' => 'Red paperclip to house',
        'story' => 'Starting with one red paperclip.',
        'start_item' => [
            'title' => 'One red paperclip',
            'description' => 'A single red paperclip.',
        ],
        'goal_item' => [
            'title' => 'A house',
            'description' => 'Dream house.',
        ],
    ]);

    $challenge = Challenge::where('user_id', $this->user->id)->latest()->first();
    $response->assertRedirect(route('challenges.show', $challenge));
});

test('guest can get challenge show page', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'Start',
        'description' => null,
    ]);
    $goalItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'goal',
        'title' => 'Goal',
        'description' => null,
    ]);
    $challenge->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);

    $response = $this->get(route('challenges.show', $challenge));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('challenges/Show')
        ->has('challenge')
        ->where('challenge.id', $challenge->id)
    );
});

test('authenticated user can get challenge show page with follow state', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'Start',
        'description' => null,
    ]);
    $goalItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'goal',
        'title' => 'Goal',
        'description' => null,
    ]);
    $challenge->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);

    $response = $this->actingAs($this->user)->get(route('challenges.show', $challenge));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('challenges/Show')
        ->has('challenge')
        ->has('isFollowing')
    );
});

test('guest cannot get dashboard challenges page', function () {
    $response = $this->get(route('dashboard.challenges'));
    $response->assertRedirect(route('login'));
});

test('authenticated user can get dashboard challenges page', function () {
    $response = $this->actingAs($this->user)->get(route('dashboard.challenges'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/challenges/Index')
        ->has('challenges')
        ->has('challenges.data')
    );
});

test('dashboard challenges page shows only current user challenges', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $myChallenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $otherUser = User::factory()->create();
    $otherChallenge = Challenge::create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Other user challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->actingAs($this->user)->get(route('dashboard.challenges'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/challenges/Index')
        ->has('challenges.data', 1)
        ->where('challenges.data.0.id', $myChallenge->id)
    );
});

test('challenges index does not include draft challenges', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $activeChallenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Active challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $draftChallenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'draft',
        'visibility' => 'public',
        'title' => 'Draft challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->get(route('challenges.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('challenges/Index')
        ->has('challenges.data', 1)
        ->where('challenges.data.0.id', $activeChallenge->id)
    );
});

test('guest gets 404 when viewing draft challenge', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'draft',
        'visibility' => 'public',
        'title' => 'Draft challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->get(route('challenges.show', $challenge));

    $response->assertNotFound();
});

test('owner can view their own draft challenge', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'draft',
        'visibility' => 'public',
        'title' => 'My draft',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);

    $response = $this->actingAs($this->user)->get(route('challenges.show', $challenge));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('challenges/Show')
        ->where('challenge.id', $challenge->id)
        ->where('challenge.title', 'My draft')
    );
});

test('get challenge edit page requires auth', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'Start',
        'description' => null,
    ]);
    $goalItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'goal',
        'title' => 'Goal',
        'description' => null,
    ]);
    $challenge->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);

    $response = $this->get(route('challenges.edit', $challenge));

    $response->assertRedirect(route('login'));
});

test('non-owner cannot get challenge edit page', function () {
    $otherUser = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Other challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'Start',
        'description' => null,
    ]);
    $goalItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'goal',
        'title' => 'Goal',
        'description' => null,
    ]);
    $challenge->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);

    $response = $this->actingAs($this->user)->get(route('challenges.edit', $challenge));

    $response->assertForbidden();
});

test('owner can get challenge edit page', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My challenge',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'Start',
        'description' => null,
    ]);
    $goalItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'goal',
        'title' => 'Goal',
        'description' => null,
    ]);
    $challenge->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);

    $response = $this->actingAs($this->user)->get(route('challenges.edit', $challenge));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('challenges/Edit')
        ->has('challenge')
        ->has('categories')
        ->where('challenge.id', $challenge->id)
    );
});

test('owner can update challenge', function () {
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $challenge = Challenge::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'My challenge',
        'story' => 'Old story',
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $startItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'start',
        'title' => 'Start',
        'description' => null,
    ]);
    $goalItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $challenge->id,
        'role' => 'goal',
        'title' => 'Goal',
        'description' => null,
    ]);
    $challenge->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);

    $response = $this->actingAs($this->user)->put(route('challenges.update', $challenge), [
        'title' => 'Updated title',
        'story' => 'Updated story',
        'start_item' => [
            'title' => 'Updated start',
            'description' => 'New start description',
        ],
        'goal_item' => [
            'title' => 'Updated goal',
            'description' => 'New goal description',
        ],
    ]);

    $response->assertRedirect(route('challenges.show', $challenge));

    $challenge->refresh();
    $challenge->load(['startItem', 'goalItem']);
    expect($challenge->title)->toBe('Updated title');
    expect($challenge->story)->toBe('Updated story');
    expect($challenge->startItem->title)->toBe('Updated start');
    expect($challenge->startItem->description)->toBe('New start description');
    expect($challenge->goalItem->title)->toBe('Updated goal');
    expect($challenge->goalItem->description)->toBe('New goal description');
});
