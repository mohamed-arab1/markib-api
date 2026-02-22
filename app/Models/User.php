<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function loginLogs()
    {
        return $this->hasMany(LoginLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin->value;
    }

    public function getRoleEnum(): ?Role
    {
        return isset($this->role) ? Role::tryFrom($this->role) : null;
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->getRoleEnum();
        return $role?->has($permission) ?? false;
    }

    /**
     * المستخدمون الذين يستلمون إشعارات الإدارة (مدير + دعم فني)
     */
    public function scopeStaff($query)
    {
        return $query->whereIn('role', ['admin', 'support']);
    }
}
