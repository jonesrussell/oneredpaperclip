<?php

use App\Ai\Agents\SuggestCampaignTextAgent;
use App\Models\User;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\RateLimitedException;

uses()->group('campaigns');

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('campaign ai suggest requires authentication', function () {
    $response = $this->postJson(route('campaigns.ai-suggest'), [
        'context' => 'story',
        'current_text' => '',
    ]);

    $response->assertUnauthorized();
});

test('campaign ai suggest validates context', function () {
    $response = $this->actingAs($this->user)->postJson(route('campaigns.ai-suggest'), [
        'context' => 'invalid',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['context']);
});

test('campaign ai suggest returns suggestion when valid', function () {
    SuggestCampaignTextAgent::fake([
        'I am trading up from a red paperclip to a house.',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('campaigns.ai-suggest'), [
        'context' => 'story',
        'current_text' => 'Starting with a paperclip.',
        'title_hint' => 'From paperclip to house',
    ]);

    $response->assertOk();
    $response->assertJson(['suggestion' => 'I am trading up from a red paperclip to a house.']);

    SuggestCampaignTextAgent::assertPrompted(fn ($prompt) => $prompt->contains('Starting with a paperclip.'));
});

test('campaign ai suggest builds correct prompt for start_item context', function () {
    SuggestCampaignTextAgent::fake([
        'A shiny red paperclip, ready for its first trade.',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('campaigns.ai-suggest'), [
        'context' => 'start_item',
        'title_hint' => 'Red Paperclip',
    ]);

    $response->assertOk();

    SuggestCampaignTextAgent::assertPrompted(
        fn ($prompt) => $prompt->contains('start item') && $prompt->contains('Red Paperclip')
    );
});

test('campaign ai suggest builds correct prompt for goal_item context', function () {
    SuggestCampaignTextAgent::fake([
        'A cozy house with a garden.',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('campaigns.ai-suggest'), [
        'context' => 'goal_item',
        'title_hint' => 'Dream House',
    ]);

    $response->assertOk();

    SuggestCampaignTextAgent::assertPrompted(
        fn ($prompt) => $prompt->contains('goal') && $prompt->contains('Dream House')
    );
});

test('campaign ai suggest requests new suggestion when no current text provided', function () {
    SuggestCampaignTextAgent::fake([
        'An exciting journey from humble beginnings.',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('campaigns.ai-suggest'), [
        'context' => 'story',
    ]);

    $response->assertOk();

    SuggestCampaignTextAgent::assertPrompted(
        fn ($prompt) => $prompt->contains('new suggestion')
    );
});

test('campaign ai suggest returns 502 when AI provider fails', function () {
    SuggestCampaignTextAgent::fake(function () {
        throw new AiException('Provider error');
    });

    $response = $this->actingAs($this->user)->postJson(route('campaigns.ai-suggest'), [
        'context' => 'story',
        'current_text' => 'Starting with a paperclip.',
    ]);

    $response->assertStatus(502);
    $response->assertJson(['message' => 'Unable to generate AI suggestion. Please try again or write your description manually.']);
});

test('campaign ai suggest returns 429 when rate limited', function () {
    SuggestCampaignTextAgent::fake(function () {
        throw new RateLimitedException('anthropic', 429);
    });

    $response = $this->actingAs($this->user)->postJson(route('campaigns.ai-suggest'), [
        'context' => 'story',
    ]);

    $response->assertStatus(429);
    $response->assertJson(['message' => 'AI service is rate limited. Please wait a moment and try again.']);
});
