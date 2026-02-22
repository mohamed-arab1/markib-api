<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device',
        'browser',
        'platform',
        'country',
        'city',
        'status',
        'logged_in_at',
        'logged_out_at',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(User $user, string $status = 'success', ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'device' => static::parseDevice($userAgent ?? request()->userAgent()),
            'browser' => static::parseBrowser($userAgent ?? request()->userAgent()),
            'platform' => static::parsePlatform($userAgent ?? request()->userAgent()),
            'status' => $status,
            'logged_in_at' => now(),
        ]);
    }

    protected static function parseDevice(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';
        if (str_contains($userAgent, 'Mobile')) return 'mobile';
        if (str_contains($userAgent, 'Tablet')) return 'tablet';
        return 'desktop';
    }

    protected static function parseBrowser(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        return 'other';
    }

    protected static function parsePlatform(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';
        if (str_contains($userAgent, 'Windows')) return 'Windows';
        if (str_contains($userAgent, 'Mac')) return 'macOS';
        if (str_contains($userAgent, 'Linux')) return 'Linux';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'iOS')) return 'iOS';
        return 'other';
    }
}
