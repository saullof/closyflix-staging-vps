<?php

namespace App\Model;

use App\Providers\PaymentsServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class Withdrawal extends Model
{
    public const REQUESTED_STATUS = 'requested';
    public const REJECTED_STATUS = 'rejected';
    public const APPROVED_STATUS = 'approved';
    public const STRIPE_CONNECT_METHOD = 'Stripe Connect';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'amount',
        'fee',
        'message',
        'payment_method',
        'payment_identifier',
        'payout_account_id',
        'payout_method_key',
        'payout_snapshot',
        'stripe_payout_id',
        'stripe_transfer_id',
        'processed',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'fee'    => 'decimal:2',
        'payout_snapshot' => 'array',
    ];
    /*
     * Relationships
     */

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<UserPayoutAccount, $this>
     */
    public function payoutAccount(): BelongsTo
    {
        return $this->belongsTo(UserPayoutAccount::class, 'payout_account_id');
    }

    public function getNetAmountAttribute(): float
    {
        return (float) $this->amount - (float) $this->fee;
    }

    public static function calculateFee(float $amount): float
    {
        if (!getSetting('payments.withdrawal_allow_fees')) {
            return 0.0;
        }

        $percentage = (float) getSetting('payments.withdrawal_default_fee_percentage');

        if ($percentage <= 0) {
            return 0.0;
        }

        return $amount * ($percentage / 100);
    }

    public function getPayoutAccountLabelAttribute(): ?string
    {
        return Arr::get($this->payout_snapshot, 'label');
    }

    public function getPaymentIdentifierLabelAttribute(): string
    {
        return match (PaymentsServiceProvider::getWithdrawalMethodKey($this->payout_method_key ?: $this->payment_method)) {
            PaymentsServiceProvider::WITHDRAWAL_METHOD_BANK_TRANSFER => __('admin.resources.withdrawal.fields.iban'),
            PaymentsServiceProvider::WITHDRAWAL_METHOD_PAYPAL => __('admin.resources.withdrawal.fields.paypal_email'),
            PaymentsServiceProvider::WITHDRAWAL_METHOD_PIX => __('PIX key'),
            PaymentsServiceProvider::WITHDRAWAL_METHOD_CRYPTO => __('admin.resources.withdrawal.fields.wallet_address'),
            PaymentsServiceProvider::WITHDRAWAL_METHOD_CUSTOM => __('admin.resources.withdrawal.fields.payout_destination'),
            PaymentsServiceProvider::WITHDRAWAL_METHOD_STRIPE_CONNECT => __('admin.resources.withdrawal.fields.stripe_account'),
            default => __('admin.resources.withdrawal.fields.payment_identifier'),
        };
    }

    public function getMessageLabelAttribute(): string
    {
        return match (PaymentsServiceProvider::getWithdrawalMethodKey($this->payout_method_key ?: $this->payment_method)) {
            PaymentsServiceProvider::WITHDRAWAL_METHOD_BANK_TRANSFER,
            PaymentsServiceProvider::WITHDRAWAL_METHOD_PAYPAL,
            PaymentsServiceProvider::WITHDRAWAL_METHOD_PIX,
            PaymentsServiceProvider::WITHDRAWAL_METHOD_CRYPTO => __('admin.resources.withdrawal.fields.notes'),
            PaymentsServiceProvider::WITHDRAWAL_METHOD_CUSTOM => __('admin.resources.withdrawal.fields.details_label'),
            default => __('admin.resources.withdrawal.fields.message'),
        };
    }

    public function getPayoutSnapshotItemsAttribute(): array
    {
        $methodKey = PaymentsServiceProvider::getWithdrawalMethodKey($this->payout_method_key ?: $this->payment_method);

        if ($this->payout_snapshot) {
            return match ($methodKey) {
                PaymentsServiceProvider::WITHDRAWAL_METHOD_BANK_TRANSFER => array_values(array_filter([
                    Arr::get($this->payout_snapshot, 'label') ? ['label' => __('admin.resources.withdrawal.fields.account_label'), 'value' => Arr::get($this->payout_snapshot, 'label')] : null,
                    Arr::get($this->payout_snapshot, 'account_holder_name') ? ['label' => __('admin.resources.withdrawal.fields.account_holder'), 'value' => Arr::get($this->payout_snapshot, 'account_holder_name')] : null,
                    Arr::get($this->payout_snapshot, 'iban') ? ['label' => __('admin.resources.withdrawal.fields.iban'), 'value' => Arr::get($this->payout_snapshot, 'iban')] : null,
                    Arr::get($this->payout_snapshot, 'swift_bic') ? ['label' => __('admin.resources.withdrawal.fields.swift_bic'), 'value' => Arr::get($this->payout_snapshot, 'swift_bic')] : null,
                    Arr::get($this->payout_snapshot, 'bank_name') ? ['label' => __('admin.resources.withdrawal.fields.bank'), 'value' => Arr::get($this->payout_snapshot, 'bank_name')] : null,
                    Arr::get($this->payout_snapshot, 'bank_address') ? ['label' => __('admin.resources.withdrawal.fields.bank_address'), 'value' => Arr::get($this->payout_snapshot, 'bank_address')] : null,
                    Arr::get($this->payout_snapshot, 'country_name') ? ['label' => __('admin.resources.withdrawal.fields.country'), 'value' => Arr::get($this->payout_snapshot, 'country_name')] : null,
                ])),
                PaymentsServiceProvider::WITHDRAWAL_METHOD_PAYPAL,
                PaymentsServiceProvider::WITHDRAWAL_METHOD_PIX,
                PaymentsServiceProvider::WITHDRAWAL_METHOD_CRYPTO,
                PaymentsServiceProvider::WITHDRAWAL_METHOD_CUSTOM => array_values(array_filter([
                    Arr::get($this->payout_snapshot, 'method_label') ? ['label' => __('admin.resources.withdrawal.fields.method'), 'value' => Arr::get($this->payout_snapshot, 'method_label')] : null,
                    Arr::get($this->payout_snapshot, 'pix_key_type_label') ? ['label' => __('PIX key type'), 'value' => Arr::get($this->payout_snapshot, 'pix_key_type_label')] : null,
                    Arr::get($this->payout_snapshot, 'pix_beneficiary_name') ? ['label' => __('Beneficiary name'), 'value' => Arr::get($this->payout_snapshot, 'pix_beneficiary_name')] : null,
                    Arr::get($this->payout_snapshot, 'identifier') ? ['label' => $this->payment_identifier_label, 'value' => Arr::get($this->payout_snapshot, 'identifier')] : null,
                    Arr::get($this->payout_snapshot, 'message') ? ['label' => $this->message_label, 'value' => Arr::get($this->payout_snapshot, 'message')] : null,
                ])),
                default => [],
            };
        }

        return array_values(array_filter([
            $this->payment_method ? ['label' => __('admin.resources.withdrawal.fields.method'), 'value' => $this->payment_method] : null,
            $this->payment_identifier ? ['label' => $this->payment_identifier_label, 'value' => $this->payment_identifier] : null,
            $this->message ? ['label' => $this->message_label, 'value' => $this->message] : null,
        ]));
    }

    public function getPayoutSnapshotSummaryAttribute(): ?string
    {
        $lines = collect($this->payout_snapshot_items)
            ->map(fn ($item) => $item['label'].': '.$item['value'])
            ->all();

        return $lines ? implode(PHP_EOL, $lines) : null;
    }

    public function getPayoutSnapshotSummaryHtmlAttribute(): ?string
    {
        if (!$this->payout_snapshot_items) {
            return null;
        }

        $items = collect($this->payout_snapshot_items)
            ->map(fn ($item) => '<div class="payout-details-item"><div class="payout-details-label">'.e($item['label']).'</div><div class="payout-details-value">'.nl2br(e($item['value'])).'</div></div>')
            ->implode('');

        return '<div class="payout-details-card"><div class="payout-details-list">'.$items.'</div></div>';
    }
}
