<?php

use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;
use OpenAI\Responses\Chat\CreateResponse;

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
    OpenAI::fake([
        CreateResponse::fake([
            'choices' => [
                [
                    'message' => [
                        'content' => 'I am trading up from a red paperclip to a house.',
                    ],
                ],
            ],
        ]),
    ]);

    $response = $this->actingAs($this->user)->postJson(route('campaigns.ai-suggest'), [
        'context' => 'story',
        'current_text' => 'Starting with a paperclip.',
        'title_hint' => 'From paperclip to house',
    ]);

    $response->assertOk();
    $response->assertJson(['suggestion' => 'I am trading up from a red paperclip to a house.']);
});
