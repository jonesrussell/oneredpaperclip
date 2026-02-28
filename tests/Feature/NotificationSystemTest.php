<?php

use App\Models\Challenge;
use App\Models\Offer;
use App\Models\Trade;
use App\Models\User;
use App\Notifications\ChallengeCompletedNotification;
use App\Notifications\OfferAcceptedNotification;
use App\Notifications\OfferDeclinedNotification;
use App\Notifications\OfferReceivedNotification;
use App\Notifications\TradeCompletedNotification;
use App\Notifications\TradePendingConfirmationNotification;
use Illuminate\Support\Facades\Notification;

uses()->group('notifications');

describe('notification preferences', function () {
    it('returns default preferences for new users', function () {
        $user = User::factory()->create();

        $prefs = $user->notification_preferences;

        expect($prefs)->toHaveKeys([
            'offer_received',
            'offer_accepted',
            'offer_declined',
            'trade_pending_confirmation',
            'trade_completed',
            'challenge_completed',
        ]);
        expect($prefs['offer_received'])->toMatchArray(['database' => true, 'email' => true]);
        expect($prefs['offer_declined'])->toMatchArray(['database' => true, 'email' => false]);
    });

    it('respects user preference for database channel', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_received' => ['database' => false, 'email' => true],
            ],
        ]);

        expect($user->wantsNotification('offer_received', 'database'))->toBeFalse();
        expect($user->wantsNotification('offer_received', 'email'))->toBeTrue();
    });

    it('respects user preference for email channel', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_accepted' => ['database' => true, 'email' => false],
            ],
        ]);

        expect($user->wantsNotification('offer_accepted', 'database'))->toBeTrue();
        expect($user->wantsNotification('offer_accepted', 'email'))->toBeFalse();
    });

    it('falls back to defaults for missing preference types', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_received' => ['database' => false, 'email' => false],
            ],
        ]);

        expect($user->wantsNotification('trade_completed', 'database'))->toBeTrue();
        expect($user->wantsNotification('trade_completed', 'email'))->toBeTrue();
    });
});

describe('notification preferences API', function () {
    it('shows notification preferences page', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('notifications.edit'));

        $response->assertSuccessful();
        $response->assertInertia(
            fn ($page) => $page
                ->component('settings/Notifications')
                ->has('preferences')
                ->has('availableTypes')
        );
    });

    it('updates notification preferences', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('notifications.update'), [
            'preferences' => [
                'offer_received' => ['database' => true, 'email' => false],
                'offer_accepted' => ['database' => false, 'email' => true],
                'offer_declined' => ['database' => true, 'email' => true],
                'trade_pending_confirmation' => ['database' => true, 'email' => true],
                'trade_completed' => ['database' => true, 'email' => true],
                'challenge_completed' => ['database' => true, 'email' => true],
            ],
        ]);

        $response->assertRedirect();
        $user->refresh();
        expect($user->notification_preferences['offer_received'])->toMatchArray(['database' => true, 'email' => false]);
        expect($user->notification_preferences['offer_accepted'])->toMatchArray(['database' => false, 'email' => true]);
    });

    it('requires authentication for preferences page', function () {
        $response = $this->get(route('notifications.edit'));

        $response->assertRedirect(route('login'));
    });
});

describe('notifications API', function () {
    it('lists user notifications', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_received' => ['database' => true, 'email' => false],
            ],
        ]);
        $offer = Offer::factory()->create();
        $user->notify(new OfferReceivedNotification($offer));

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'notifications' => [
                '*' => ['id', 'type', 'data', 'read_at', 'created_at'],
            ],
            'unread_count',
        ]);
    });

    it('marks notification as read', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_received' => ['database' => true, 'email' => false],
            ],
        ]);
        $offer = Offer::factory()->create();
        $user->notify(new OfferReceivedNotification($offer));
        $notification = $user->notifications()->first();

        expect($notification->read_at)->toBeNull();

        $response = $this->actingAs($user)->post(route('notifications.mark-read', $notification->id));

        $response->assertSuccessful();
        expect($notification->fresh()->read_at)->not->toBeNull();
    });

    it('marks all notifications as read', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_received' => ['database' => true, 'email' => false],
            ],
        ]);
        $offer1 = Offer::factory()->create();
        $offer2 = Offer::factory()->create();
        $user->notify(new OfferReceivedNotification($offer1));
        $user->notify(new OfferReceivedNotification($offer2));

        expect($user->unreadNotifications()->count())->toBe(2);

        $response = $this->actingAs($user)->post(route('notifications.mark-all-read'));

        $response->assertSuccessful();
        expect($user->unreadNotifications()->count())->toBe(0);
    });

    it('returns unread count', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_received' => ['database' => true, 'email' => false],
            ],
        ]);
        $offer = Offer::factory()->create();
        $user->notify(new OfferReceivedNotification($offer));

        $response = $this->actingAs($user)->get(route('notifications.unread-count'));

        $response->assertSuccessful();
        $response->assertJson(['unread_count' => 1]);
    });
});

describe('notification via methods', function () {
    it('respects user preferences for OfferReceivedNotification', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_received' => ['database' => true, 'email' => false],
            ],
        ]);
        $offer = Offer::factory()->create();
        $notification = new OfferReceivedNotification($offer);

        $channels = $notification->via($user);

        expect($channels)->toContain('database');
        expect($channels)->not->toContain('mail');
    });

    it('respects user preferences for OfferAcceptedNotification', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_accepted' => ['database' => false, 'email' => true],
            ],
        ]);
        $offer = Offer::factory()->create();
        $trade = Trade::factory()->create(['offer_id' => $offer->id]);
        $notification = new OfferAcceptedNotification($offer, $trade);

        $channels = $notification->via($user);

        expect($channels)->not->toContain('database');
        expect($channels)->toContain('mail');
    });

    it('respects user preferences for OfferDeclinedNotification', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'offer_declined' => ['database' => true, 'email' => true],
            ],
        ]);
        $offer = Offer::factory()->create();
        $notification = new OfferDeclinedNotification($offer);

        $channels = $notification->via($user);

        expect($channels)->toContain('database');
        expect($channels)->toContain('mail');
    });

    it('respects user preferences for TradePendingConfirmationNotification', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'trade_pending_confirmation' => ['database' => true, 'email' => false],
            ],
        ]);
        $trade = Trade::factory()->create();
        $confirmedBy = User::factory()->create();
        $notification = new TradePendingConfirmationNotification($trade, $confirmedBy);

        $channels = $notification->via($user);

        expect($channels)->toContain('database');
        expect($channels)->not->toContain('mail');
    });

    it('respects user preferences for TradeCompletedNotification', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'trade_completed' => ['database' => false, 'email' => false],
            ],
        ]);
        $trade = Trade::factory()->create();
        $notification = new TradeCompletedNotification($trade);

        $channels = $notification->via($user);

        expect($channels)->toBeEmpty();
    });

    it('respects user preferences for ChallengeCompletedNotification', function () {
        $user = User::factory()->create([
            'notification_preferences' => [
                'challenge_completed' => ['database' => true, 'email' => true],
            ],
        ]);
        $challenge = Challenge::factory()->create();
        $notification = new ChallengeCompletedNotification($challenge);

        $channels = $notification->via($user);

        expect($channels)->toContain('database');
        expect($channels)->toContain('mail');
    });
});

describe('CreateOffer notification', function () {
    it('notifies challenge owner when offer is created', function () {
        Notification::fake();

        $owner = User::factory()->create();
        $offerer = User::factory()->create();
        $challenge = Challenge::factory()->withItems()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($offerer)->post(route('challenges.offers.store', $challenge), [
            'offered_item' => [
                'title' => 'Test Item',
                'description' => 'A test item',
            ],
            'message' => 'I want to trade!',
        ]);

        Notification::assertSentTo($owner, OfferReceivedNotification::class);
    });
});
