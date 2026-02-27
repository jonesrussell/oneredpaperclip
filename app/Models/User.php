<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'reputation_score',
        'verified_at',
        'is_admin',
        'xp',
        'level',
        'current_streak',
        'longest_streak',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'xp' => 'integer',
            'level' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Calculate XP required for a given level.
     * Level n requires roughly 250 * n^1.5 XP.
     */
    public static function xpRequiredForLevel(int $level): int
    {
        if ($level <= 1) {
            return 0;
        }

        return (int) round(250 * pow($level, 1.5));
    }

    /**
     * Get XP required for the next level.
     */
    public function getXpForNextLevelAttribute(): int
    {
        return self::xpRequiredForLevel($this->level + 1);
    }

    /**
     * Get XP progress within current level (0-100%).
     */
    public function getLevelProgressAttribute(): int
    {
        $currentLevelXp = self::xpRequiredForLevel($this->level);
        $nextLevelXp = self::xpRequiredForLevel($this->level + 1);
        $xpInLevel = $this->xp - $currentLevelXp;
        $xpNeeded = $nextLevelXp - $currentLevelXp;

        if ($xpNeeded <= 0) {
            return 100;
        }

        return (int) min(100, round(($xpInLevel / $xpNeeded) * 100));
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->profile_photo_path
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;
    }

    public function challenges(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function offers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Offer::class, 'from_user_id');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function follows(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Follow::class);
    }
}
