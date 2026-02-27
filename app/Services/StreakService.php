<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class StreakService
{
    public const STREAK_WINDOW_HOURS = 48;

    public const STREAK_MILESTONE_7 = 7;

    public const STREAK_MILESTONE_30 = 30;

    public const STREAK_MILESTONE_100 = 100;

    public const XP_MILESTONE_7 = 50;

    public const XP_MILESTONE_30 = 200;

    public const XP_MILESTONE_100 = 500;

    public function __construct(
        private XpService $xpService
    ) {}

    /**
     * Record user activity and update streak.
     *
     * @return array{streak_updated: bool, current_streak: int, is_milestone: bool, milestone: int|null, xp_bonus: int|null}
     */
    public function recordActivity(User $user): array
    {
        $now = Carbon::now();
        $lastActivity = $user->last_activity_at;

        $result = [
            'streak_updated' => false,
            'current_streak' => $user->current_streak,
            'is_milestone' => false,
            'milestone' => null,
            'xp_bonus' => null,
        ];

        if ($lastActivity === null) {
            $user->current_streak = 1;
            $user->longest_streak = 1;
            $user->last_activity_at = $now;
            $user->save();

            $result['streak_updated'] = true;
            $result['current_streak'] = 1;

            return $result;
        }

        $hoursSinceLastActivity = $lastActivity->diffInHours($now);

        if ($hoursSinceLastActivity < 24) {
            $user->last_activity_at = $now;
            $user->save();

            return $result;
        }

        if ($hoursSinceLastActivity <= self::STREAK_WINDOW_HOURS) {
            $user->current_streak++;
            $user->last_activity_at = $now;

            if ($user->current_streak > $user->longest_streak) {
                $user->longest_streak = $user->current_streak;
            }

            $user->save();

            $result['streak_updated'] = true;
            $result['current_streak'] = $user->current_streak;

            $milestone = $this->checkMilestone($user->current_streak);
            if ($milestone !== null) {
                $result['is_milestone'] = true;
                $result['milestone'] = $milestone;
                $result['xp_bonus'] = $this->awardMilestoneXp($user, $milestone);
            }

            return $result;
        }

        $user->current_streak = 1;
        $user->last_activity_at = $now;
        $user->save();

        $result['streak_updated'] = true;
        $result['current_streak'] = 1;

        return $result;
    }

    /**
     * Check if the current streak hits a milestone.
     */
    private function checkMilestone(int $streak): ?int
    {
        return match ($streak) {
            self::STREAK_MILESTONE_7 => self::STREAK_MILESTONE_7,
            self::STREAK_MILESTONE_30 => self::STREAK_MILESTONE_30,
            self::STREAK_MILESTONE_100 => self::STREAK_MILESTONE_100,
            default => null,
        };
    }

    /**
     * Award bonus XP for hitting a streak milestone.
     */
    private function awardMilestoneXp(User $user, int $milestone): int
    {
        $xpBonus = match ($milestone) {
            self::STREAK_MILESTONE_7 => self::XP_MILESTONE_7,
            self::STREAK_MILESTONE_30 => self::XP_MILESTONE_30,
            self::STREAK_MILESTONE_100 => self::XP_MILESTONE_100,
            default => 0,
        };

        if ($xpBonus > 0) {
            $this->xpService->award($user, $xpBonus);
        }

        return $xpBonus;
    }

    /**
     * Check if user's streak is at risk (not active today but still within window).
     */
    public function isStreakAtRisk(User $user): bool
    {
        if ($user->last_activity_at === null || $user->current_streak === 0) {
            return false;
        }

        $hoursSinceLastActivity = $user->last_activity_at->diffInHours(Carbon::now());

        return $hoursSinceLastActivity >= 24 && $hoursSinceLastActivity < self::STREAK_WINDOW_HOURS;
    }

    /**
     * Get the hours remaining before streak expires.
     */
    public function getHoursUntilStreakExpires(User $user): ?int
    {
        if ($user->last_activity_at === null) {
            return null;
        }

        $hoursSinceLastActivity = $user->last_activity_at->diffInHours(Carbon::now());
        $hoursRemaining = self::STREAK_WINDOW_HOURS - $hoursSinceLastActivity;

        return max(0, (int) $hoursRemaining);
    }
}
