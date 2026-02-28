<?php

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use App\Models\User;

uses()->group('admin', 'challenges');

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

test('admin can access challenges index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.challenges.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('dashboard/admin/challenges/Index'));
});

test('non-admin cannot access challenges index', function () {
    $response = $this->actingAs($this->user)->get(route('admin.challenges.index'));

    $response->assertForbidden();
});

test('guest cannot access challenges index', function () {
    $response = $this->get(route('admin.challenges.index'));

    $response->assertRedirect(route('login'));
});

test('admin challenges index shows challenges', function () {
    $challenge = Challenge::factory()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.challenges.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/admin/challenges/Index')
        ->has('challenges.data', 1)
        ->has('stats')
        ->has('categories')
    );
});

test('admin challenges index filters by status', function () {
    $activeChallenge = Challenge::factory()->create(['status' => ChallengeStatus::Active]);
    $draftChallenge = Challenge::factory()->draft()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.challenges.index', ['status' => ChallengeStatus::Active->value]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/admin/challenges/Index')
        ->has('challenges.data', 1)
        ->where('challenges.data.0.id', $activeChallenge->id)
    );
});

test('admin can view challenge details', function () {
    $challenge = Challenge::factory()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.challenges.show', $challenge));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/admin/challenges/Show')
        ->has('challenge')
        ->where('challenge.id', $challenge->id)
    );
});

test('admin can unpublish challenge', function () {
    $challenge = Challenge::factory()->create(['status' => ChallengeStatus::Active]);

    $response = $this->actingAs($this->admin)->post(route('admin.challenges.unpublish', $challenge));

    $response->assertRedirect();
    expect($challenge->fresh()->status)->toBe(ChallengeStatus::Draft);
});

test('admin can bulk unpublish challenges', function () {
    $challenges = Challenge::factory()->count(3)->create(['status' => ChallengeStatus::Active]);
    $ids = $challenges->pluck('id')->toArray();

    $response = $this->actingAs($this->admin)->post(route('admin.challenges.bulk-unpublish'), ['ids' => $ids]);

    $response->assertRedirect();
    foreach ($challenges as $challenge) {
        expect($challenge->fresh()->status)->toBe(ChallengeStatus::Draft);
    }
});

test('admin can soft delete challenge', function () {
    $challenge = Challenge::factory()->create();

    $response = $this->actingAs($this->admin)->delete(route('admin.challenges.destroy', $challenge));

    $response->assertRedirect();
    expect(Challenge::find($challenge->id))->toBeNull();
    expect(Challenge::onlyTrashed()->find($challenge->id))->not->toBeNull();
});

test('admin can bulk delete challenges', function () {
    $challenges = Challenge::factory()->count(3)->create();
    $ids = $challenges->pluck('id')->toArray();

    $response = $this->actingAs($this->admin)->post(route('admin.challenges.bulk-delete'), ['ids' => $ids]);

    $response->assertRedirect();
    expect(Challenge::whereIn('id', $ids)->count())->toBe(0);
    expect(Challenge::onlyTrashed()->whereIn('id', $ids)->count())->toBe(3);
});

test('admin can view trashed challenges', function () {
    $challenge = Challenge::factory()->create();
    $challenge->delete();

    $response = $this->actingAs($this->admin)->get(route('admin.challenges.trashed'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/admin/challenges/Trashed')
        ->has('challenges.data', 1)
        ->where('challenges.data.0.id', $challenge->id)
    );
});

test('admin can restore challenge', function () {
    $challenge = Challenge::factory()->create();
    $challenge->delete();

    $response = $this->actingAs($this->admin)->post(route('admin.challenges.restore', $challenge));

    $response->assertRedirect();
    expect(Challenge::find($challenge->id))->not->toBeNull();
});

test('admin can bulk restore challenges', function () {
    $challenges = Challenge::factory()->count(3)->create();
    $ids = $challenges->pluck('id')->toArray();
    Challenge::whereIn('id', $ids)->delete();

    $response = $this->actingAs($this->admin)->post(route('admin.challenges.bulk-restore'), ['ids' => $ids]);

    $response->assertRedirect();
    expect(Challenge::whereIn('id', $ids)->count())->toBe(3);
});

test('admin can force delete challenge', function () {
    $challenge = Challenge::factory()->create();
    $challenge->delete();

    $response = $this->actingAs($this->admin)->delete(route('admin.challenges.force-delete', $challenge));

    $response->assertRedirect();
    expect(Challenge::withTrashed()->find($challenge->id))->toBeNull();
});

test('admin can bulk force delete challenges', function () {
    $challenges = Challenge::factory()->count(3)->create();
    $ids = $challenges->pluck('id')->toArray();
    Challenge::whereIn('id', $ids)->delete();

    $response = $this->actingAs($this->admin)->post(route('admin.challenges.bulk-force-delete'), ['ids' => $ids]);

    $response->assertRedirect();
    expect(Challenge::withTrashed()->whereIn('id', $ids)->count())->toBe(0);
});
