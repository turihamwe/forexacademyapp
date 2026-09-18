<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_number',
        'title',
        'content',
        'is_free',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'day_number' => 'integer',
    ];

    public function userProgress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($this->is_free) {
            return true;
        }

        if ($user->subscription_status === User::SUBSCRIPTION_PAID_FULL) {
            return true;
        }

        if ($user->subscription_status === User::SUBSCRIPTION_PAID_DAILY) {
            return $user->progress()
                ->where('completed', true)
                ->count() >= ($this->day_number - 1);
        }

        return false;
    }
}
