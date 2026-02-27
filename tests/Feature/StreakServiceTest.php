<?php

use App\Models\User;
use App\Services\StreakService;
use App\Services\XpService;
use Carbon\Carbon;

beforeEach(function () {
    $this->xpService = new XpService;
    $this->streakService = new StreakService($this->xpService);
});

describe('Streak recording', function () {
    it('starts streak at 1 for first activity', function () {
        $user = User::factory()->create([
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_activity_at' => null,
        ]);

        $result = $this->streakService->recordActivity($user);

        expect($result['streak_updated'])->toBeTrue();
        expect($result['current_streak'])->toBe(1);
        expect($user->fresh()->current_streak)->toBe(1);
        expect($user->fresh()->longest_streak)->toBe(1);
    });

    it('does not update streak for activity within same day', function () {
        $user = User::factory()->create([
            'current_streak' => 3,
            'longest_streak' => 5,
            'last_activity_at' => Carbon::now()->subHours(2),
        ]);

        $result = $this->streakService->recordActivity($user);

        expect($result['streak_updated'])->toBeFalse();
        expect($user->fresh()->current_streak)->toBe(3);
    });

    it('increments streak for activity next day', function () {
        $user = User::factory()->create([
            'current_streak' => 3,
            'longest_streak' => 5,
            'last_activity_at' => Carbon::now()->subHours(30),
        ]);

        $result = $this->streakService->recordActivity($user);

        expect($result['streak_updated'])->toBeTrue();
        expect($result['current_streak'])->toBe(4);
        expect($user->fresh()->current_streak)->toBe(4);
    });

    it('resets streak when gap exceeds 48 hours', function () {
        $user = User::factory()->create([
            'current_streak' => 10,
            'longest_streak' => 15,
            'last_activity_at' => Carbon::now()->subHours(50),
        ]);

        $result = $this->streakService->recordActivity($user);

        expect($result['streak_updated'])->toBeTrue();
        expect($result['current_streak'])->toBe(1);
        expect($user->fresh()->current_streak)->toBe(1);
        expect($user->fresh()->longest_streak)->toBe(15);
    });

    it('updates longest streak when current exceeds it', function () {
        $user = User::factory()->create([
            'current_streak' => 5,
            'longest_streak' => 5,
            'last_activity_at' => Carbon::now()->subHours(30),
        ]);

        $result = $this->streakService->recordActivity($user);

        expect($user->fresh()->current_streak)->toBe(6);
        expect($user->fresh()->longest_streak)->toBe(6);
    });
});

describe('Streak milestones', function () {
    it('awards bonus XP at 7 day milestone', function () {
        $user = User::factory()->create([
            'xp' => 0,
            'current_streak' => 6,
            'longest_streak' => 6,
            'last_activity_at' => Carbon::now()->subHours(30),
        ]);

        $result = $this->streakService->recordActivity($user);

        expect($result['is_milestone'])->toBeTrue();
        expect($result['milestone'])->toBe(7);
        expect($result['xp_bonus'])->toBe(StreakService::XP_MILESTONE_7);
        expect($user->fresh()->xp)->toBe(StreakService::XP_MILESTONE_7);
    });

    it('awards bonus XP at 30 day milestone', function () {
        $user = User::factory()->create([
            'xp' => 0,
            'current_streak' => 29,
            'longest_streak' => 29,
            'last_activity_at' => Carbon::now()->subHours(30),
        ]);

        $result = $this->streakService->recordActivity($user);

        expect($result['is_milestone'])->toBeTrue();
        expect($result['milestone'])->toBe(30);
        expect($result['xp_bonus'])->toBe(StreakService::XP_MILESTONE_30);
    });

    it('does not award milestone for non-milestone days', function () {
        $user = User::factory()->create([
            'current_streak' => 5,
            'longest_streak' => 5,
            'last_activity_at' => Carbon::now()->subHours(30),
        ]);

        $result = $this->streakService->recordActivity($user);

        expect($result['is_milestone'])->toBeFalse();
        expect($result['milestone'])->toBeNull();
    });
});

describe('Streak status', function () {
    it('detects streak at risk', function () {
        $user = User::factory()->create([
            'current_streak' => 5,
            'last_activity_at' => Carbon::now()->subHours(30),
        ]);

        expect($this->streakService->isStreakAtRisk($user))->toBeTrue();
    });

    it('does not flag recent activity as at risk', function () {
        $user = User::factory()->create([
            'current_streak' => 5,
            'last_activity_at' => Carbon::now()->subHours(12),
        ]);

        expect($this->streakService->isStreakAtRisk($user))->toBeFalse();
    });

    it('calculates hours until streak expires', function () {
        $user = User::factory()->create([
            'current_streak' => 5,
            'last_activity_at' => Carbon::now()->subHours(24),
        ]);

        $hours = $this->streakService->getHoursUntilStreakExpires($user);

        expect($hours)->toBeGreaterThanOrEqual(23);
        expect($hours)->toBeLessThanOrEqual(24);
    });

    it('returns null hours for user without activity', function () {
        $user = User::factory()->create([
            'current_streak' => 0,
            'last_activity_at' => null,
        ]);

        expect($this->streakService->getHoursUntilStreakExpires($user))->toBeNull();
    });
});
