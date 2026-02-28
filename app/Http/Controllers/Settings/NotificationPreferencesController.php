<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferencesController extends Controller
{
    /**
     * Show the notification preferences page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Notifications', [
            'preferences' => $request->user()->notification_preferences,
            'availableTypes' => $this->getAvailableNotificationTypes(),
        ]);
    }

    /**
     * Update the user's notification preferences.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.database' => ['required', 'boolean'],
            'preferences.*.email' => ['required', 'boolean'],
        ]);

        $allowedTypes = array_keys(User::defaultNotificationPreferences());
        $preferences = collect($validated['preferences'])
            ->only($allowedTypes)
            ->all();

        $request->user()->update([
            'notification_preferences' => $preferences,
        ]);

        return back()->with('status', 'notification-preferences-updated');
    }

    /**
     * Get all available notification types with labels.
     *
     * @return array<string, array{label: string, description: string}>
     */
    private function getAvailableNotificationTypes(): array
    {
        return [
            'offer_received' => [
                'label' => 'New Offer Received',
                'description' => 'When someone makes an offer on your challenge',
            ],
            'offer_accepted' => [
                'label' => 'Offer Accepted',
                'description' => 'When your offer is accepted by a challenge owner',
            ],
            'offer_declined' => [
                'label' => 'Offer Declined',
                'description' => 'When your offer is declined',
            ],
            'trade_pending_confirmation' => [
                'label' => 'Trade Awaiting Confirmation',
                'description' => 'When the other party confirms a trade',
            ],
            'trade_completed' => [
                'label' => 'Trade Completed',
                'description' => 'When a trade is fully completed',
            ],
            'challenge_completed' => [
                'label' => 'Challenge Completed',
                'description' => 'When a challenge you own or follow reaches its goal',
            ],
        ];
    }
}
