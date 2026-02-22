<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;

uses()->group('api', 'webmcp');

beforeEach(function () {
    $this->user = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $this->campaign = Campaign::create([
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
        'itemable_type' => Campaign::class,
        'itemable_id' => $this->campaign->id,
        'role' => 'start',
        'title' => 'One red paperclip',
        'description' => 'A single red paperclip.',
    ]);
    $goalItem = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $this->campaign->id,
        'role' => 'goal',
        'title' => 'A house',
        'description' => 'Dream house.',
    ]);
    $this->campaign->update(['current_item_id' => $startItem->id, 'goal_item_id' => $goalItem->id]);
    $this->campaign = $this->campaign->fresh();
});

test('get api campaigns index returns json list of public campaigns', function () {
    $response = $this->getJson(route('api.campaigns.index'));

    $response->assertOk();
    $response->assertJsonStructure(['campaigns']);
    $response->assertJsonPath('campaigns.0.id', $this->campaign->id);
    $response->assertJsonPath('campaigns.0.title', 'Red paperclip to house');
    $response->assertJsonPath('campaigns.0.status', 'active');
});

test('get api campaigns index accepts limit query', function () {
    $response = $this->getJson(route('api.campaigns.index', ['limit' => 5]));

    $response->assertOk();
    $response->assertJsonStructure(['campaigns']);
});

test('get api campaign show returns json campaign with items and pending offers count', function () {
    $response = $this->getJson(route('api.campaigns.show', $this->campaign));

    $response->assertOk();
    $response->assertJsonPath('campaign.id', $this->campaign->id);
    $response->assertJsonPath('campaign.title', 'Red paperclip to house');
    $response->assertJsonPath('campaign.current_item.id', $this->campaign->currentItem->id);
    $response->assertJsonPath('campaign.goal_item.id', $this->campaign->goalItem->id);
    $response->assertJsonStructure(['campaign' => ['pending_offers_count']]);
});

test('get api campaigns mine requires auth', function () {
    $response = $this->getJson(route('api.campaigns.mine'));

    $response->assertUnauthorized();
});

test('get api campaigns mine returns authenticated user campaigns', function () {
    $response = $this->actingAs($this->user)->getJson(route('api.campaigns.mine'));

    $response->assertOk();
    $response->assertJsonPath('campaigns.0.id', $this->campaign->id);
});

test('post api campaigns store requires auth', function () {
    $response = $this->postJson(route('api.campaigns.store'), [
        'start_item' => ['title' => 'Start', 'description' => null],
        'goal_item' => ['title' => 'Goal', 'description' => null],
    ]);

    $response->assertUnauthorized();
});

test('post api campaigns store creates campaign and returns json', function () {
    $response = $this->actingAs($this->user)->postJson(route('api.campaigns.store'), [
        'title' => 'New campaign',
        'start_item' => ['title' => 'A pen', 'description' => 'Blue pen.'],
        'goal_item' => ['title' => 'A book', 'description' => 'Any book.'],
    ]);

    $response->assertCreated();
    $response->assertJsonPath('message', 'Campaign created.');
    $response->assertJsonStructure(['campaign' => ['id', 'title', 'status']]);
    expect(Campaign::where('title', 'New campaign')->exists())->toBeTrue();
});
