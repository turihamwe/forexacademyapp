<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';
    public const ROLE_USER = 'user';

    public const SUBSCRIPTION_FREE = 'free';
    public const SUBSCRIPTION_PAID_FULL = 'paid_full';
    public const SUBSCRIPTION_PAID_DAILY = 'paid_daily';

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'password',
        'role',
        'subscription_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function hasPaidAccess(): bool
    {
        return in_array($this->subscription_status, [
            self::SUBSCRIPTION_PAID_FULL,
            self::SUBSCRIPTION_PAID_DAILY,
        ], true);
    }

    public function grantFreeModuleAccess(): void
    {
        $freeModules = Module::where('is_free', true)->pluck('id');

        foreach ($freeModules as $moduleId) {
            $this->progress()->firstOrCreate(
                ['module_id' => $moduleId],
                ['completed' => false]
            );
        }
    }
}
