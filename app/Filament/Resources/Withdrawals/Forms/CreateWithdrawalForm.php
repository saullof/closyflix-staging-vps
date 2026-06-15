<?php

namespace App\Filament\Resources\Withdrawals\Forms;

use App\Model\Withdrawal;
use App\Providers\PaymentsServiceProvider;
use App\Providers\SettingsServiceProvider;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class CreateWithdrawalForm
{
    public static function schema($userId = null): array
    {
        return [
            Placeholder::make('payout_summary')
                ->label(__('admin.resources.withdrawal.sections.payout_summary'))
                ->content(fn ($record) => $record
                    ? new HtmlString(self::renderPayoutSummary($record))
                    : new HtmlString('<div class="payout-details-empty-state">'.e(__('admin.resources.withdrawal.helpers.summary_empty')).'</div>'))
                ->columnSpanFull(),

            Placeholder::make('payout_snapshot_summary')
                ->label(__('admin.resources.withdrawal.sections.payout_details'))
                ->content(fn ($record) => $record?->payout_snapshot_summary_html
                    ? new HtmlString($record->payout_snapshot_summary_html)
                    : new HtmlString('<div class="payout-details-empty-state">'.e(__('admin.resources.withdrawal.helpers.payout_details_empty')).'</div>'))
                ->columnSpanFull(),

            Select::make('user_id')
                ->label(__('admin.resources.withdrawal.fields.user_id'))
                ->relationship('user', 'username')
                ->searchable()
                ->default($userId)
                ->required()
                ->preload(true),

            Select::make('status')
                ->label(__('admin.resources.withdrawal.fields.status'))
                ->required()
                ->options(self::getTranslatedStatuses())
                ->default(Withdrawal::REQUESTED_STATUS)
                ->rule(function ($get, $state, $set, $context) {
                    return function ($attribute, $value, $fail) use ($context) {
                        $isCreating = $context === 'create';
                        if ($isCreating && $value !== Withdrawal::REQUESTED_STATUS) {
                            $fail(__('admin.resources.withdrawal.helpers.status_creation_rule'));
                        }
                    };
                }),

            TextInput::make('amount')
                ->label(__('admin.resources.withdrawal.fields.amount'))
                ->numeric()
                ->required()
                ->rule(function ($get, $context) {
                    if ($context === 'edit') {
                        return null;
                    }

                    $userId = $get('user_id');
                    $user = \App\Model\User::find($userId);
                    $walletTotal = (float) data_get($user, 'wallet.total', 0);

                    return function ($attribute, $value, $fail) use ($walletTotal) {
                        if ($value > $walletTotal) {
                            $fail(__('admin.resources.withdrawal.helpers.amount_overflow'));
                        }
                    };
                }),

            TextInput::make('fee')
                ->label(__('admin.resources.withdrawal.fields.fee'))
                ->numeric()
                ->disabled()
                ->helperText(__('admin.resources.withdrawal.helpers.fees_info'))
                ->default(0),

            Textarea::make('message')
                ->label(fn ($record) => $record?->message_label ?: __('admin.resources.withdrawal.fields.message'))
                ->helperText(fn ($record) => $record ? __('admin.resources.withdrawal.helpers.stored_notes') : null)
                ->columnSpanFull(),

            Select::make('payment_method')
                ->label(__('admin.resources.withdrawal.fields.payment_method'))
                ->required()
                ->options(
                    collect(PaymentsServiceProvider::getWithdrawalsAllowedPaymentMethods())
                        ->mapWithKeys(fn ($method) => [$method => $method])
                        ->toArray()
                )
                ->default(PaymentsServiceProvider::getWithdrawalMethodLabel(PaymentsServiceProvider::WITHDRAWAL_METHOD_CUSTOM))
                ->helperText(fn ($record) => $record ? __('admin.resources.withdrawal.helpers.stored_method_reference') : null)
                ->disabled(fn ($record) => filled($record))
                ->dehydrated(fn ($record) => blank($record))
                ->rule(function ($get, $state, $set, $context) {
                    return function ($attribute, $value, $fail) {
                        if ($value === Withdrawal::STRIPE_CONNECT_METHOD) {
                            $fail(__('admin.resources.withdrawal.helpers.stripe_connect_warning'));
                        }
                    };
                }),

            TextInput::make('payment_identifier')
                ->label(fn ($record) => $record?->payment_identifier_label ?: __('admin.resources.withdrawal.fields.payment_identifier'))
                ->helperText(function ($record) {
                    if (!$record) {
                        return null;
                    }

                    if (self::shouldLockPaymentIdentifier($record)) {
                        return __('admin.resources.withdrawal.helpers.stored_payout_reference');
                    }

                    return __('admin.resources.withdrawal.helpers.stored_payout_used');
                })
                ->disabled(fn ($record) => self::shouldLockPaymentIdentifier($record))
                ->dehydrated(fn ($record) => !self::shouldLockPaymentIdentifier($record))
                ->maxLength(191)
                ->default(null),

            TextInput::make('stripe_payout_id')
                ->label(__('admin.resources.withdrawal.fields.stripe_payout_id'))
                ->helperText(__('admin.resources.withdrawal.helpers.stripe_payout_reference'))
                ->disabled()
                ->dehydrated(false)
                ->visible(fn ($record) => $record && PaymentsServiceProvider::isStripeConnectWithdrawalMethod($record->payout_method_key ?: $record->payment_method))
                ->maxLength(191)
                ->default(null),

            TextInput::make('stripe_transfer_id')
                ->label(__('admin.resources.withdrawal.fields.stripe_transfer_id'))
                ->helperText(__('admin.resources.withdrawal.helpers.stripe_transfer_reference'))
                ->disabled()
                ->dehydrated(false)
                ->visible(fn ($record) => $record && PaymentsServiceProvider::isStripeConnectWithdrawalMethod($record->payout_method_key ?: $record->payment_method))
                ->maxLength(191)
                ->default(null),

            Toggle::make('processed')
                ->label(__('admin.resources.withdrawal.fields.processed'))
                ->helperText(__('admin.resources.withdrawal.helpers.processed_flag'))
                ->required()
                ->default(0)
                ->disabled(fn ($record) => $record?->processed)
                ->rule(function ($get, $record) {
                    return function ($attribute, $value, $fail) use ($record) {
                        if ($record && $record->processed) {
                            $fail(__('admin.resources.withdrawal.helpers.processed_warning'));
                        }
                    };
                }),
        ];
    }

    public static function getAvailableStatuses(): array
    {
        return [
            Withdrawal::APPROVED_STATUS,
            Withdrawal::REQUESTED_STATUS,
            Withdrawal::REJECTED_STATUS,
        ];
    }

    public static function getTranslatedStatuses(): array
    {
        return collect(self::getAvailableStatuses())
            ->mapWithKeys(fn ($status) => [
                $status => __('admin.resources.withdrawal.status_labels.'.$status),
            ])->toArray();
    }

    protected static function shouldLockPaymentIdentifier(?Withdrawal $record): bool
    {
        if (!$record) {
            return false;
        }

        $methodKey = PaymentsServiceProvider::getWithdrawalMethodKey($record->payout_method_key ?: $record->payment_method);

        return $methodKey === PaymentsServiceProvider::WITHDRAWAL_METHOD_BANK_TRANSFER
            || $methodKey === PaymentsServiceProvider::WITHDRAWAL_METHOD_CUSTOM
            || empty($record->payout_snapshot);
    }

    protected static function renderPayoutSummary(Withdrawal $record): string
    {
        $items = [
            [
                'label' => __('admin.resources.withdrawal.fields.requested_amount'),
                'value' => SettingsServiceProvider::getWebsiteFormattedAmount(number_format((float) $record->amount, 2, '.', '')),
                'classes' => '',
            ],
            [
                'label' => __('admin.resources.withdrawal.fields.fee'),
                'value' => SettingsServiceProvider::getWebsiteFormattedAmount(number_format((float) $record->fee, 2, '.', '')),
                'classes' => '',
            ],
            [
                'label' => __('admin.resources.withdrawal.fields.net_payout'),
                'value' => SettingsServiceProvider::getWebsiteFormattedAmount(number_format((float) $record->net_amount, 2, '.', '')),
                'classes' => ' is-accent',
            ],
            [
                'label' => __('admin.resources.withdrawal.fields.status'),
                'value' => __('admin.resources.withdrawal.status_labels.'.strtolower((string) $record->status)),
                'classes' => '',
            ],
        ];

        $content = collect($items)
            ->map(fn ($item) => '<div class="payout-summary-item'.$item['classes'].'"><div class="payout-summary-label">'.e($item['label']).'</div><div class="payout-summary-value">'.e($item['value']).'</div></div>')
            ->implode('');

        return '<div class="payout-summary-card"><div class="payout-summary-grid">'.$content.'</div></div>';
    }
}
