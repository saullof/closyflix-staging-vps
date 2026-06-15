<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPayoutAccount extends Model
{
    public const BANK_TRANSFER = 'bank_transfer';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'method_key',
        'label',
        'is_default',
        'is_active',
        'account_holder_name',
        'iban',
        'swift_bic',
        'bank_name',
        'bank_address',
        'country_id',
        'extra_data',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'extra_data' => 'array',
        'last_used_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function toWithdrawalSnapshot(): array
    {
        return [
            'label' => $this->label,
            'method_key' => $this->method_key,
            'account_holder_name' => $this->account_holder_name,
            'iban' => $this->iban,
            'swift_bic' => $this->swift_bic,
            'bank_name' => $this->bank_name,
            'bank_address' => $this->bank_address,
            'country_id' => $this->country_id,
            'country_name' => optional($this->country)->name,
        ];
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: $this->bank_name ?: __('Bank account');
    }

    public function getMaskedIbanAttribute(): string
    {
        $iban = preg_replace('/\s+/', '', (string) $this->iban);

        if (strlen($iban) <= 8) {
            return $iban;
        }

        return substr($iban, 0, 4).' '.str_repeat('*', max(strlen($iban) - 8, 0)).' '.substr($iban, -4);
    }

    public function getSummaryLinesAttribute(): array
    {
        return array_filter([
            __('Account holder').': '.$this->account_holder_name,
            __('IBAN').': '.$this->iban,
            $this->swift_bic ? __('SWIFT/BIC').': '.$this->swift_bic : null,
            __('Bank').': '.$this->bank_name,
            $this->bank_address ? __('Bank address').': '.$this->bank_address : null,
            $this->country ? __('Country').': '.$this->country->name : null,
        ]);
    }
}
