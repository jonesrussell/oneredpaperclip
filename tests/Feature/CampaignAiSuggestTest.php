<?php

use App\Ai\Agents\SuggestCampaignTextAgent;
use App\Models\User;

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

    $response->assertSuccessful();
    $response->assertJson(['suggestion' => 'I am trading up from a red paperclip to a house.']);

    SuggestCampaignTextAgent::assertPrompted(fn ($prompt) => $prompt->contains('Starting with a paperclip.'));
});
