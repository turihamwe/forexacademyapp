<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public const TYPE_FULL = 'full_100';
    public const TYPE_DAILY = 'daily_4';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'amount',
        'payment_gateway_ref',
        'yo_transaction_ref',
        'network_ref',
        'payer_msisdn',
        'external_reference',
        'type',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionStatusForType(): string
    {
        return $this->type === self::TYPE_FULL
            ? User::SUBSCRIPTION_PAID_FULL
            : User::SUBSCRIPTION_PAID_DAILY;
    }
}
