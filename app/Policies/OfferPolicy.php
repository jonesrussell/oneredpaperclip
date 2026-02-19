<?php

namespace App\Policies;

use App\Enums\OfferStatus;
use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Offer $offer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Offer $offer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Offer $offer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Offer $offer): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Offer $offer): bool
    {
        return false;
    }

    /**
     * Determine whether the user (campaign owner) can accept the offer.
     * Offer must be pending and campaign current item must still match the offer.
     */
    public function accept(User $user, Offer $offer): bool
    {
        if ($offer->status !== OfferStatus::Pending) {
            return false;
        }

        if ($offer->campaign->user_id !== $user->id) {
            return false;
        }

        return $offer->campaign->current_item_id === $offer->for_campaign_item_id;
    }

    /**
     * Determine whether the user (campaign owner) can decline the offer.
     */
    public function decline(User $user, Offer $offer): bool
    {
        if ($offer->status !== OfferStatus::Pending) {
            return false;
        }

        return $offer->campaign->user_id === $user->id;
    }
}
