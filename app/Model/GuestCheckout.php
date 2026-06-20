<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestCheckout extends Model
{
    public const INITIATED_STATUS = 'initiated';
    public const PENDING_STATUS = 'pending';
    public const APPROVED_STATUS = 'approved';
    public const CANCELED_STATUS = 'canceled';
    public const CLAIMED_STATUS = 'claimed';
    public const DECLINED_STATUS = 'declined';

    protected $fillable = [
        'token',
        'status',
        'recipient_user_id',
        'claimed_user_id',
        'transaction_id',
        'type',
        'payment_provider',
        'currency',
        'amount',
        'taxes',
        'coupon',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'customer_email',
        'first_name',
        'last_name',
        'billing_address',
        'country',
        'state',
        'postcode',
        'city',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'taxes' => 'array',
        'expires_at' => 'datetime',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function claimedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_user_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
