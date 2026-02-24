<?php

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Item;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->owner = User::factory()->create();
    $this->other = User::factory()->create();
    $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);
    $this->campaign = Campaign::create([
        'user_id' => $this->owner->id,
        'category_id' => $category->id,
        'status' => 'active',
        'visibility' => 'public',
        'title' => 'Test',
        'story' => null,
        'current_item_id' => null,
        'goal_item_id' => null,
    ]);
    $this->item = Item::create([
        'itemable_type' => Campaign::class,
        'itemable_id' => $this->campaign->id,
        'role' => 'start',
        'title' => 'Start item',
        'description' => null,
    ]);
});

test('guest cannot upload item media', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $response = $this->post(route('items.media.store', $this->item), [
        'image' => $file,
    ]);

    $response->assertRedirect(route('login'));
    expect(Media::query()->where('model_id', $this->item->id)->count())->toBe(0);
});

test('challenge owner can upload item photo', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $response = $this->actingAs($this->owner)->post(route('items.media.store', $this->item), [
        'image' => $file,
    ]);

    $response->assertRedirect();
    $media = Media::query()->where('model_id', $this->item->id)->first();
    expect($media)->not->toBeNull();
    expect($media->model_type)->toBe(Item::class);
    expect($media->disk)->toBe('public');
    Storage::disk('public')->assertExists($media->path);
});

test('non-owner cannot upload item photo', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

    $response = $this->actingAs($this->other)->post(route('items.media.store', $this->item), [
        'image' => $file,
    ]);

    $response->assertForbidden();
    expect(Media::query()->where('model_id', $this->item->id)->count())->toBe(0);
});
