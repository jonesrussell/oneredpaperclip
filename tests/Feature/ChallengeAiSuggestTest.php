<?php

use App\Ai\Agents\SuggestChallengeTextAgent;
use App\Models\User;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\RateLimitedException;

uses()->group('challenges');

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('challenge ai suggest requires authentication', function () {
    $response = $this->postJson(route('challenges.ai-suggest'), [
        'context' => 'story',
        'current_text' => '',
    ]);

    $response->assertUnauthorized();
});

test('challenge ai suggest validates context', function () {
    $response = $this->actingAs($this->user)->postJson(route('challenges.ai-suggest'), [
        'context' => 'invalid',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['context']);
});

test('challenge ai suggest returns suggestion when valid', function () {
    SuggestChallengeTextAgent::fake([
        'I am trading up from a red paperclip to a house.',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('challenges.ai-suggest'), [
        'context' => 'story',
        'current_text' => 'Starting with a paperclip.',
        'title_hint' => 'From paperclip to house',
    ]);

    $response->assertOk();
    $response->assertJson(['suggestion' => 'I am trading up from a red paperclip to a house.']);

    SuggestChallengeTextAgent::assertPrompted(
        fn ($prompt) => $prompt->contains('Starting with a paperclip.')
            && $prompt->contains('max 40 words')
    );
});

test('challenge ai suggest builds correct prompt for start_item context', function () {
    SuggestChallengeTextAgent::fake([
        'A shiny red paperclip, ready for its first trade.',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('challenges.ai-suggest'), [
        'context' => 'start_item',
        'title_hint' => 'Red Paperclip',
    ]);

    $response->assertOk();

    SuggestChallengeTextAgent::assertPrompted(
        fn ($prompt) => $prompt->contains('start item')
            && $prompt->contains('Red Paperclip')
            && $prompt->contains('max 20 words')
    );
});

test('challenge ai suggest builds correct prompt for goal_item context', function () {
    SuggestChallengeTextAgent::fake([
        'A cozy house with a garden.',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('challenges.ai-suggest'), [
        'context' => 'goal_item',
        'title_hint' => 'Dream House',
    ]);

    $response->assertOk();

    SuggestChallengeTextAgent::assertPrompted(
        fn ($prompt) => $prompt->contains('goal')
            && $prompt->contains('Dream House')
            && $prompt->contains('max 20 words')
    );
});

test('challenge ai suggest requests new suggestion when no current text provided', function () {
    SuggestChallengeTextAgent::fake([
        'An exciting journey from humble beginnings.',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('challenges.ai-suggest'), [
        'context' => 'story',
    ]);

    $response->assertOk();

    SuggestChallengeTextAgent::assertPrompted(
        fn ($prompt) => $prompt->contains('new suggestion')
    );
});

test('challenge ai suggest returns 502 when AI provider fails', function () {
    SuggestChallengeTextAgent::fake(function () {
        throw new AiException('Provider error');
    });

    $response = $this->actingAs($this->user)->postJson(route('challenges.ai-suggest'), [
        'context' => 'story',
        'current_text' => 'Starting with a paperclip.',
    ]);

    $response->assertStatus(502);
    $response->assertJson(['message' => 'Unable to generate AI suggestion. Please try again or write your description manually.']);
});

test('challenge ai suggest returns 429 when rate limited', function () {
    SuggestChallengeTextAgent::fake(function () {
        throw new RateLimitedException('anthropic', 429);
    });

    $response = $this->actingAs($this->user)->postJson(route('challenges.ai-suggest'), [
        'context' => 'story',
    ]);

    $response->assertStatus(429);
    $response->assertJson(['message' => 'AI service is rate limited. Please wait a moment and try again.']);
});
