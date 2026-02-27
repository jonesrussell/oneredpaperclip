<?php

use App\Models\User;
use App\Services\XpService;

beforeEach(function () {
    $this->xpService = new XpService;
});

describe('XP calculations', function () {
    it('calculates level 1 for 0 XP', function () {
        expect($this->xpService->calculateLevel(0))->toBe(1);
    });

    it('calculates level 1 for XP below threshold', function () {
        expect($this->xpService->calculateLevel(400))->toBe(1);
    });

    it('calculates level 2 when XP meets threshold', function () {
        expect($this->xpService->calculateLevel(707))->toBe(2);
    });

    it('calculates higher levels correctly', function () {
        expect($this->xpService->calculateLevel(1299))->toBe(3);
        expect($this->xpService->calculateLevel(2000))->toBe(4);
    });

    it('returns XP required for level', function () {
        expect(User::xpRequiredForLevel(1))->toBe(0);
        expect(User::xpRequiredForLevel(2))->toBe(707);
        expect(User::xpRequiredForLevel(3))->toBe(1299);
    });
});

describe('XP awards', function () {
    it('awards XP for campaign creation', function () {
        $user = User::factory()->create(['xp' => 0, 'level' => 1]);

        $result = $this->xpService->awardCampaignCreation($user);

        expect($result['xp_gained'])->toBe(XpService::XP_CREATE_CAMPAIGN);
        expect($user->fresh()->xp)->toBe(XpService::XP_CREATE_CAMPAIGN);
    });

    it('awards XP for receiving an offer', function () {
        $user = User::factory()->create(['xp' => 0, 'level' => 1]);

        $result = $this->xpService->awardOfferReceived($user);

        expect($result['xp_gained'])->toBe(XpService::XP_RECEIVE_OFFER);
        expect($user->fresh()->xp)->toBe(XpService::XP_RECEIVE_OFFER);
    });

    it('awards XP for completing a trade', function () {
        $user = User::factory()->create(['xp' => 0, 'level' => 1]);

        $result = $this->xpService->awardTradeCompletion($user);

        expect($result['xp_gained'])->toBe(XpService::XP_COMPLETE_TRADE);
        expect($user->fresh()->xp)->toBe(XpService::XP_COMPLETE_TRADE);
    });

    it('awards XP for completing a campaign', function () {
        $user = User::factory()->create(['xp' => 0, 'level' => 1]);

        $result = $this->xpService->awardCampaignCompletion($user);

        expect($result['xp_gained'])->toBe(XpService::XP_COMPLETE_CAMPAIGN);
        expect($user->fresh()->xp)->toBe(XpService::XP_COMPLETE_CAMPAIGN);
    });

    it('awards XP for daily login', function () {
        $user = User::factory()->create(['xp' => 0, 'level' => 1]);

        $result = $this->xpService->awardDailyLogin($user);

        expect($result['xp_gained'])->toBe(XpService::XP_DAILY_LOGIN);
    });
});

describe('Level ups', function () {
    it('triggers level up when XP threshold is reached', function () {
        $user = User::factory()->create(['xp' => 650, 'level' => 1]);

        $result = $this->xpService->award($user, 100);

        expect($result['leveled_up'])->toBeTrue();
        expect($result['new_level'])->toBe(2);
        expect($user->fresh()->level)->toBe(2);
    });

    it('does not level up when threshold is not reached', function () {
        $user = User::factory()->create(['xp' => 100, 'level' => 1]);

        $result = $this->xpService->award($user, 50);

        expect($result['leveled_up'])->toBeFalse();
        expect($result['new_level'])->toBeNull();
        expect($user->fresh()->level)->toBe(1);
    });
});

describe('Streak bonus', function () {
    it('applies streak multiplier to XP awards', function () {
        $user = User::factory()->create(['xp' => 0, 'level' => 1, 'current_streak' => 5]);

        $result = $this->xpService->awardCampaignCreation($user);

        $expectedXp = (int) round(XpService::XP_CREATE_CAMPAIGN * 1.4);
        expect($result['xp_gained'])->toBe($expectedXp);
    });

    it('does not apply multiplier for streak of 1', function () {
        $user = User::factory()->create(['xp' => 0, 'level' => 1, 'current_streak' => 1]);

        $result = $this->xpService->awardCampaignCreation($user);

        expect($result['xp_gained'])->toBe(XpService::XP_CREATE_CAMPAIGN);
    });
});

describe('XP breakdown', function () {
    it('returns correct breakdown for user', function () {
        $user = User::factory()->create(['xp' => 800, 'level' => 2]);

        $breakdown = $this->xpService->getXpBreakdown($user);

        expect($breakdown['current_xp'])->toBe(800);
        expect($breakdown['level'])->toBe(2);
        expect($breakdown)->toHaveKey('xp_for_current_level');
        expect($breakdown)->toHaveKey('xp_for_next_level');
        expect($breakdown)->toHaveKey('progress_percent');
    });
});
