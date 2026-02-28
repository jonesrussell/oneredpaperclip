<?php

use App\Models\User;

test('flash success message is shared via Inertia', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Test flash message'])
        ->get('/challenges');

    $response->assertInertia(fn ($page) => $page
        ->has('flash')
        ->where('flash.success', 'Test flash message')
    );
});
