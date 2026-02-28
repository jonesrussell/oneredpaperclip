<?php

use App\Models\Challenge;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;

uses()->group('api', 'webmcp');

beforeEach(function () {
    $this->user = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $this->challenge = Challenge::create([
        'user_id' => $this->user->id,
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
        'itemable_id' => $this->challenge->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);
    $goalItem = Item::create([
        'itemable_type' => Challenge::class,
        'itemable_id' => $this->challenge->id,
        'role' => 'goal',
        'title' => 'A house',
        'description' => 'Dream house.',
    ]);
    $this->challenge->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);
    $this->challenge = $this->challenge->fresh();
});

test('get api challenges index returns json list of public challenges', function () {
    $response = $this->getJson(route('api.challenges.index'));

    $response->assertOk();
    $response->assertJsonStructure(['challenges']);
    $response->assertJsonPath('challenges.0.id', $this->challenge->id);
    $response->assertJsonPath('challenges.0.title', 'Red paperclip to house');
    $response->assertJsonPath('challenges.0.status', 'active');
});

test('get api challenges index accepts limit query', function () {
    $response = $this->getJson(route('api.challenges.index', ['limit' => 5]));

    $response->assertOk();
    $response->assertJsonStructure(['challenges']);
});

test('get api challenge show returns json challenge with items and pending offers count', function () {
    $response = $this->getJson(route('api.challenges.show', $this->challenge));

    $response->assertOk();
    $response->assertJsonPath('challenge.id', $this->challenge->id);
    $response->assertJsonPath('challenge.title', 'Red paperclip to house');
    $response->assertJsonPath('challenge.current_item.id', $this->challenge->currentItem->id);
    $response->assertJsonPath('challenge.goal_item.id', $this->challenge->goalItem->id);
    $response->assertJsonStructure(['challenge' => ['pending_offers_count']]);
});

test('get api challenge show returns 404 for draft when not owner', function () {
    $this->challenge->update(['status' => 'draft']);

    $response = $this->getJson(route('api.challenges.show', $this->challenge));

    $response->assertNotFound();
});

test('get api challenge show returns draft when owner', function () {
    $this->challenge->update(['status' => 'draft']);

    $response = $this->actingAs($this->user)->getJson(route('api.challenges.show', $this->challenge));

    $response->assertOk();
    $response->assertJsonPath('challenge.id', $this->challenge->id);
    $response->assertJsonPath('challenge.status', 'draft');
});

test('get api challenges mine requires auth', function () {
    $response = $this->getJson(route('api.challenges.mine'));

    $response->assertUnauthorized();
});

test('get api challenges mine returns authenticated user challenges', function () {
    $response = $this->actingAs($this->user)->getJson(route('api.challenges.mine'));

    $response->assertOk();
    $response->assertJsonPath('challenges.0.id', $this->challenge->id);
});

test('post api challenges store requires auth', function () {
    $response = $this->postJson(route('api.challenges.store'), [
        'start_item' => ['title' => 'Start', 'description' => null],
        'goal_item' => ['title' => 'Goal', 'description' => null],
    ]);

    $response->assertUnauthorized();
});

test('post api challenges store creates challenge and returns json', function () {
    $response = $this->actingAs($this->user)->postJson(route('api.challenges.store'), [
        'title' => 'New challenge',
        'start_item' => ['title' => 'A pen', 'description' => 'Blue pen.'],
        'goal_item' => ['title' => 'A book', 'description' => 'Any book.'],
    ]);

    $response->assertCreated();
    $response->assertJsonPath('message', 'Challenge created.');
    $response->assertJsonStructure(['challenge' => ['id', 'title', 'status']]);
    expect(Challenge::where('title', 'New challenge')->exists())->toBeTrue();
});
