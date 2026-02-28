<?php

use App\Models\Challenge;

test('welcome page renders with correct inertia props', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('canRegister')
        ->has('featuredChallenges')
        ->has('stats.challengesCount')
        ->has('stats.tradesCount')
        ->has('stats.usersCount')
    );
});

test('welcome page includes featured challenges when they exist', function () {
    $challenge = Challenge::factory()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('featuredChallenges', 1)
        ->where('featuredChallenges.0.id', $challenge->id)
    );
});

test('welcome page excludes draft challenges from featured', function () {
    Challenge::factory()->draft()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('featuredChallenges', 0)
    );
});

test('welcome page excludes unlisted challenges from featured', function () {
    Challenge::factory()->unlisted()->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Welcome')
        ->has('featuredChallenges', 0)
    );
});
