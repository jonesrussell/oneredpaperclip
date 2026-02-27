<?php

namespace App\Services;

use App\Models\User;

class XpService
{
    public const XP_CREATE_CHALLENGE = 50;

    public const XP_RECEIVE_OFFER = 10;

    public const XP_COMPLETE_TRADE = 100;

    public const XP_COMPLETE_CHALLENGE = 500;

    public const XP_DAILY_LOGIN = 25;

    /**
     * Award XP to a user and handle level-ups.
     *
     * @return array{xp_gained: int, leveled_up: bool, new_level: int|null}
     */
    public function award(User $user, int $amount, ?int $streakMultiplier = null): array
    {
        if ($streakMultiplier !== null && $streakMultiplier > 1) {
            $amount = (int) round($amount * (1 + ($streakMultiplier - 1) * 0.1));
        }

        $oldLevel = $user->level;
        $user->xp += $amount;

        $newLevel = $this->calculateLevel($user->xp);
        $leveledUp = $newLevel > $oldLevel;

        if ($leveledUp) {
            $user->level = $newLevel;
        }

        $user->save();

        return [
            'xp_gained' => $amount,
            'leveled_up' => $leveledUp,
            'new_level' => $leveledUp ? $newLevel : null,
        ];
    }

    /**
     * Award XP for creating a challenge.
     *
     * @return array{xp_gained: int, leveled_up: bool, new_level: int|null}
     */
    public function awardChallengeCreation(User $user): array
    {
        return $this->award($user, self::XP_CREATE_CHALLENGE, $user->current_streak);
    }

    /**
     * Award XP for receiving an offer.
     *
     * @return array{xp_gained: int, leveled_up: bool, new_level: int|null}
     */
    public function awardOfferReceived(User $user): array
    {
        return $this->award($user, self::XP_RECEIVE_OFFER);
    }

    /**
     * Award XP for completing a trade.
     *
     * @return array{xp_gained: int, leveled_up: bool, new_level: int|null}
     */
    public function awardTradeCompletion(User $user): array
    {
        return $this->award($user, self::XP_COMPLETE_TRADE, $user->current_streak);
    }

    /**
     * Award XP for completing a challenge (reaching the goal).
     *
     * @return array{xp_gained: int, leveled_up: bool, new_level: int|null}
     */
    public function awardChallengeCompletion(User $user): array
    {
        return $this->award($user, self::XP_COMPLETE_CHALLENGE, $user->current_streak);
    }

    /**
     * Award XP for daily login.
     *
     * @return array{xp_gained: int, leveled_up: bool, new_level: int|null}
     */
    public function awardDailyLogin(User $user): array
    {
        return $this->award($user, self::XP_DAILY_LOGIN, $user->current_streak);
    }

    /**
     * Calculate the level for a given XP amount.
     * Uses the inverse of the XP formula: XP = 250 * level^1.5
     */
    public function calculateLevel(int $xp): int
    {
        if ($xp <= 0) {
            return 1;
        }

        $level = 1;
        while (User::xpRequiredForLevel($level + 1) <= $xp) {
            $level++;
        }

        return $level;
    }

    /**
     * Get XP breakdown for a user showing progress to next level.
     *
     * @return array{current_xp: int, level: int, xp_for_current_level: int, xp_for_next_level: int, progress_percent: int}
     */
    public function getXpBreakdown(User $user): array
    {
        return [
            'current_xp' => $user->xp,
            'level' => $user->level,
            'xp_for_current_level' => User::xpRequiredForLevel($user->level),
            'xp_for_next_level' => User::xpRequiredForLevel($user->level + 1),
            'progress_percent' => $user->level_progress,
        ];
    }
}
