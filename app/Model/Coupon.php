<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $fillable = [
        'coupon_code',
        'discount_type',
        'discount_percent',
        'amount_off',
        'expiration_type',
        'usage_limit',
        'expires_at',
        'creator_id',
        'times_used',
        'duration_in_months',
        'stripe_coupon_id',
        'payment_method',
        'status',
    ];

    protected $attributes = [
        'status' => 'active',
        'payment_method' => 'all',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'discount_percent' => 'float',
        'amount_off' => 'integer',
        'usage_limit' => 'integer',
        'times_used' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function supportsPaymentProvider(?string $provider): bool
    {
        return match ($this->payment_method) {
            'credit_card' => $provider === Transaction::STRIPE_PROVIDER,
            'pix' => $provider === Transaction::STRIPE_PIX_PROVIDER,
            default => true,
        };
    }

    public function getDiscountValueAttribute(): float
    {
        if ($this->discount_type === 'fixed') {
            return ((float) $this->amount_off) / 100;
        }

        return (float) $this->discount_percent;
    }

    public function scopeValid($query)
    {
        return $query
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')->orWhereRaw('times_used < usage_limit');
            });
    }
}