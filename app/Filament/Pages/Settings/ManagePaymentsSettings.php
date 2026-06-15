<?php

namespace App\Filament\Pages\Settings;

use App\Providers\PaymentsServiceProvider;
use App\Settings\PaymentsSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ManagePaymentsSettings extends SettingsPage
{
    use HasPageShield;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $slug = 'settings/payments';

    protected static string $settings = PaymentsSettings::class;

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Payments Settings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Tabs::make()
                ->persistTabInQueryString('tab')
                ->columnSpanFull()
                ->tabs([

                    Tab::make('General')
                        ->columns(2)
                        ->schema([

                            Placeholder::make('cron_warning')
                                ->hiddenLabel()
                                ->columnSpanFull()
                                ->content(
                                    file_exists(storage_path('logs/cronjobs.log'))
                                        ? ''
                                        : new HtmlString(view('filament.partials.webhooks.crons-warning')->render())
                                ),

                            TextInput::make('currency_code')
                                ->label('Currency code')
                                ->helperText('The ISO currency code (e.g. USD, EUR, GBP) used across the platform.')
                                ->required(),

                            TextInput::make('currency_symbol')
                                ->label('Currency symbol')
                                ->helperText('The symbol shown next to prices (e.g. $, €, £).')
                                ->required(),

                            Select::make('currency_position')
                                ->label('Currency position')
                                ->options([
                                    'left' => 'Left ($99.99)',
                                    'right' => 'Right (99.99$)',
                                ])
                                ->helperText('Choose whether the currency symbol appears before or after the amount.')
                                ->required(),

                            Toggle::make('disable_local_wallet_for_subscriptions')
                                ->label('Disable local wallet for subscriptions')
                                ->helperText('Prevents users from using their internal wallet balance to pay for subscriptions.'),
                        ]),

                    Tab::make('Limits')
                        ->columns(2)
                        ->schema([

                            TextInput::make('default_subscription_price')
                                ->label('Default subscription price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->minValue(1)
                                ->helperText('Default monthly price for new user subscriptions.')
                                ->columnSpanFull()
                                ->required(),

                            TextInput::make('minimum_subscription_price')
                                ->label('Min subscription price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->minValue(1)
                                ->helperText('Lowest amount users can charge for subscriptions.')
                                ->required(),

                            TextInput::make('maximum_subscription_price')
                                ->label('Max subscription price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->helperText('Maximum amount users can charge for subscriptions.')
                                ->required(),

                            TextInput::make('deposit_min_amount')
                                ->label('Min deposit amount')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->minValue(1)
                                ->helperText('Smallest amount users can add to their wallet.')
                                ->required(),

                            TextInput::make('deposit_max_amount')
                                ->label('Max deposit amount')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->helperText('Largest amount users can deposit in a single transaction.')
                                ->required(),

                            TextInput::make('min_tip_value')
                                ->label('Min tip value')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->minValue(1)
                                ->helperText('Minimum value allowed when tipping other users.')
                                ->required(),

                            TextInput::make('max_tip_value')
                                ->label('Max tip value')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->helperText('Maximum value allowed when tipping other users.')
                                ->required(),

                            TextInput::make('min_ppv_post_price')
                                ->label('Min PPV post price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->minValue(1)
                                ->helperText('Minimum price to unlock pay-per-view posts.')
                                ->required(),

                            TextInput::make('max_ppv_post_price')
                                ->label('Max PPV post price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->helperText('Maximum price to unlock pay-per-view posts.')
                                ->required(),

                            TextInput::make('min_ppv_message_price')
                                ->label('Min PPV message price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->minValue(1)
                                ->helperText('Minimum price to unlock pay-per-view messages.')
                                ->required(),

                            TextInput::make('max_ppv_message_price')
                                ->label('Max PPV message price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->helperText('Maximum price to unlock pay-per-view messages.')
                                ->required(),

                            TextInput::make('min_ppv_stream_price')
                                ->label('Min PPV stream price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->minValue(1)
                                ->helperText('Minimum price to access pay-per-view streams.')
                                ->required(),

                            TextInput::make('max_ppv_stream_price')
                                ->label('Max PPV stream price')
                                ->numeric()
                                ->rules(['decimal:0,2'])
                                ->helperText('Maximum price to access pay-per-view streams.')
                                ->required(),
                        ]),

                    Tab::make('Processors')->schema([

                        Tabs::make('Payment Providers')
                            ->persistTabInQueryString('provider')
                            ->contained(false)
                            ->scrollable(false)
                            ->columnSpanFull()
                            ->tabs([
                                Tab::make('Stripe PIX')->schema([
                                    Section::make()->columns(2)->schema([

                                        Placeholder::make('stripe_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.stripe')->render())),

                                        TextInput::make('stripe_pix_public_key')
                                            ->label('PIX public key')
                                            ->helperText('Public key from the separate Stripe account used for PIX payments.'),

                                        TextInput::make('stripe_pix_secret_key')
                                            ->label('PIX secret key')
                                            ->helperText('Private API key from the separate Stripe account used for PIX payments.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('stripe_pix_webhooks_secret')
                                            ->label('PIX webhook secret')
                                            ->helperText('Webhook signing secret from the Stripe PIX account.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('stripe_pix_checkout_disabled')
                                            ->label('Disable PIX on checkout')
                                            ->helperText('Keeps Stripe card payments enabled while hiding Stripe PIX.'),
                                    ]),
                                ]),
                                Tab::make('PayPal')->schema([
                                    Section::make()->columns(2)->schema([

                                        Placeholder::make('stripe_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.paypal')->render())),

                                        TextInput::make('paypal_client_id')
                                            ->label('Client ID')
                                            ->helperText('Found in your PayPal Developer Dashboard under REST API credentials.'),

                                        TextInput::make('paypal_secret')
                                            ->label('Secret key')
                                            ->helperText('Private API key used for authenticating with PayPal’s API.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('paypal_webhook_id')
                                            ->label('Webhook ID')
                                            ->helperText('Webhook ID used for signing webhooks coming from PayPal.')
                                            ->columnSpanFull(),

                                        Toggle::make('paypal_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),

                                        Toggle::make('paypal_recurring_disabled')
                                            ->label('Disable recurring payments')
                                            ->helperText('Stops users from starting subscriptions via PayPal.'),

                                        Toggle::make('paypal_live_mode')
                                            ->label('Live mode')
                                            ->helperText('Enable this to use real transactions. Turn off to use sandbox for testing.'),
                                    ]),
                                ]),
                                Tab::make('NowPayments')->schema([
                                    Section::make()->columns(2)->schema([

                                        Placeholder::make('stripe_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.nowpayments')->render())),

                                        TextInput::make('nowpayments_api_key')
                                            ->label('API Key')
                                            ->helperText('Used to authenticate API requests to NowPayments for crypto processing.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('nowpayments_ipn_secret_key')
                                            ->label('IPN secret key')
                                            ->helperText('Used to validate Instant Payment Notifications (IPNs) from NowPayments.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('nowpayments_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('CCBill')->schema([
                                    Section::make()->columns(2)->schema([

                                        Placeholder::make('stripe_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.ccbill')->render())),

                                        TextInput::make('ccbill_account_number')
                                            ->label('Account number')
                                            ->helperText('Your main CCBill account number used to identify your integration.'),

                                        TextInput::make('ccbill_subaccount_number_recurring')
                                            ->label('Recurring subaccount number')
                                            ->helperText('Used for processing recurring billing via CCBill.'),

                                        TextInput::make('ccbill_subaccount_number_one_time')
                                            ->label('One-Time subaccount number')
                                            ->helperText('Used for one-time payments processed through CCBill.'),

                                        TextInput::make('ccbill_flex_form_id')
                                            ->label('FlexForm ID')
                                            ->helperText('ID of the CCBill FlexForm configured for your payment flow.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('ccbill_salt_key')
                                            ->label('Salt key')
                                            ->helperText('Security key used for validating incoming CCBill responses.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('ccbill_datalink_username')
                                            ->label('Datalink username')
                                            ->helperText('Username for CCBill Datalink API access (used for status syncs, etc.).'),

                                        TextInput::make('ccbill_datalink_password')
                                            ->label('Datalink password')
                                            ->helperText('Password for authenticating Datalink API requests.'),

                                        Toggle::make('ccbill_skip_subaccount_from_cancellations')
                                            ->label('Skip subaccount on cancellations')
                                            ->helperText('Avoids using subaccount information when cancelling subscriptions.'),

                                        Toggle::make('ccbill_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),

                                        Toggle::make('ccbill_recurring_disabled')
                                            ->label('Disable recurring payments')
                                            ->helperText('Prevent users from starting recurring subscriptions via CCBill.'),
                                    ]),
                                ]),
                                Tab::make('Verotel')->schema([
                                    Section::make()->columns(2)->schema([

                                        Placeholder::make('verotel_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.verotel')->render())),

                                        TextInput::make('verotel_merchant_id')
                                            ->label('Merchant ID')
                                            ->helperText('Your Verotel Merchant ID.'),

                                        TextInput::make('verotel_shop_id')
                                            ->label('Shop ID')
                                            ->helperText('A Shop ID is generated after you complete the website setup on their platform.'),

                                        TextInput::make('verotel_signature_key')
                                            ->label('Signature Key')
                                            ->helperText('A Signature Key is generated after you complete the website setup on their platform and is used to sign their hooks and requests.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('verotel_control_center_api_user')
                                            ->label('ControlCenter API username')
                                            ->helperText('You can obtain your username in Control center on a "Setup Control Center API" page'),

                                        TextInput::make('verotel_control_center_api_password')
                                            ->label('ControlCenter API password')
                                            ->helperText('You can obtain your password in Control center on a "Setup Control Center API" page.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('verotel_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),

                                        Toggle::make('verotel_recurring_disabled')
                                            ->label('Disable recurring payments')
                                            ->helperText('Prevent users from starting recurring subscriptions via Verotel.'),
                                    ]),
                                ]),
                                Tab::make('Paystack')->schema([
                                    Section::make()->columns(2)->schema([

                                        Placeholder::make('stripe_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.paystack')->render())),

                                        TextInput::make('paystack_secret_key')
                                            ->label('Secret key')
                                            ->helperText('Used to authenticate API requests with Paystack.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('paystack_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('YooMoney')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('yookassa_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.yookassa')->render())),

                                        TextInput::make('yookassa_shop_id')
                                            ->label('Shop ID')
                                            ->helperText('YooMoney shop identifier used to authenticate API requests.'),

                                        TextInput::make('yookassa_secret_key')
                                            ->label('Secret key')
                                            ->helperText('Private secret key used to authenticate requests to YooMoney.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('yookassa_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('Mollie')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('mollie_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.mollie')->render())),

                                        TextInput::make('mollie_api_key')
                                            ->label('API key')
                                            ->helperText('Private API key used to create Mollie hosted checkout payments.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('mollie_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->columnSpanFull()
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('Flutterwave')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('flutterwave_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.flutterwave')->render())),

                                        TextInput::make('flutterwave_secret_key')
                                            ->label('Secret key')
                                            ->helperText('Private secret key used to create Flutterwave hosted checkout payments.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('flutterwave_webhook_secret_hash')
                                            ->label('Webhook secret hash')
                                            ->helperText('Secret hash configured in your Flutterwave dashboard and used to validate incoming webhooks.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('flutterwave_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->columnSpanFull()
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('CoinGate')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('coingate_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.coingate')->render())),

                                        TextInput::make('coingate_api_token')
                                            ->label('API token')
                                            ->helperText('Access token used to create CoinGate orders and verify order status.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Select::make('coingate_mode')
                                            ->label('Mode')
                                            ->options([
                                                'live' => 'Live',
                                                'sandbox' => 'Sandbox',
                                            ])
                                            ->default('live')
                                            ->helperText('Sandbox tokens work only on the CoinGate sandbox API host.'),

                                        Toggle::make('coingate_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->columnSpanFull()
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('Xendit')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('xendit_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.xendit')->render())),

                                        TextInput::make('xendit_secret_key')
                                            ->label('Secret key')
                                            ->helperText('Private API key used to create Xendit payment links.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('xendit_webhook_token')
                                            ->label('Webhook token')
                                            ->helperText('Used to verify incoming webhooks from Xendit.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('xendit_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('Paddle')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('paddle_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.paddle')->render())),

                                        TextInput::make('paddle_api_key')
                                            ->label('API key')
                                            ->helperText('Private API key used to create Paddle draft transactions for hosted checkout.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('paddle_webhooks_secret')
                                            ->label('Webhook secret')
                                            ->helperText('Endpoint secret key used to verify incoming webhooks from Paddle.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('paddle_hosted_checkout_url')
                                            ->label('Hosted checkout URL')
                                            ->helperText('Full Paddle hosted checkout URL from your dashboard, for example https://pay.paddle.io/checkout/hsc_...')
                                            ->url()
                                            ->columnSpanFull()
                                            ->autocomplete('new-password'),

                                        Toggle::make('paddle_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->columnSpanFull()
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('Crypto.com')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('cryptocom_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.cryptocom')->render())),

                                        TextInput::make('cryptocom_secret_key')
                                            ->label('Secret key')
                                            ->helperText('Private API key used to create Crypto.com Pay payments.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('cryptocom_webhooks_secret')
                                            ->label('Webhook secret')
                                            ->helperText('Signature secret used to verify incoming Crypto.com Pay webhooks.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('cryptocom_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('MercadoPago')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('stripe_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.mercado')->render())),

                                        TextInput::make('mercado_access_token')
                                            ->label('Access token')
                                            ->helperText('Used to authenticate requests to MercadoPago’s API.')
                                            ->password()
                                            ->revealable()
                                            ->columnSpanFull()
                                            ->autocomplete('new-password'),

                                        Toggle::make('mercado_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->columnSpanFull()
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),
                                    ]),
                                ]),
                                Tab::make('RazorPay')->schema([
                                    Section::make()->columns(2)->schema([
                                        Placeholder::make('stripe_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.razorpay')->render())),

                                        TextInput::make('razorpay_api_key')
                                            ->label('API Key')
                                            ->helperText('Used to authenticate requests to RazorPay’s API.'),

                                        TextInput::make('razorpay_api_secret')
                                            ->label('API Secret')
                                            ->helperText('Used to authenticate requests to RazorPay’s API.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('razorpay_webhooks_secret')
                                            ->label('Webhook Secret')
                                            ->columnSpanFull()
                                            ->helperText('Verifies incoming webhook events from RazorPay to ensure authenticity.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('razorpay_checkout_disabled')
                                            ->label('Disable on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),

                                    ]),
                                ]),
                                Tab::make('Offline')->schema([
                                    Section::make()->columns(2)->schema([

                                        Toggle::make('allow_manual_payments')
                                            ->label('Enable manual payments')
                                            ->helperText('Allow users to make manual/offline payments, such as via bank transfer.')->columnSpanFull(),

                                        TextInput::make('offline_payments_owner')
                                            ->label('Account owner')
                                            ->helperText('Name of the account holder where offline payments should be sent.'),

                                        TextInput::make('offline_payments_account_number')
                                            ->label('Account number')
                                            ->helperText('Bank account number for receiving offline payments.'),

                                        TextInput::make('offline_payments_bank_name')
                                            ->label('Bank name')
                                            ->helperText('Name of the bank associated with the provided account.'),

                                        TextInput::make('offline_payments_routing_number')
                                            ->label('Routing number')
                                            ->helperText('Used in some countries to identify the receiving bank.'),

                                        TextInput::make('offline_payments_iban')
                                            ->label('IBAN')
                                            ->helperText('International Bank Account Number (used in EU/UK transfers).'),

                                        TextInput::make('offline_payments_swift')
                                            ->label('SWIFT/BIC Code')
                                            ->helperText('SWIFT or BIC code used for international transfers.'),

                                        Toggle::make('offline_payments_make_notes_field_mandatory')
                                            ->label('Require notes Field')
                                            ->helperText('Force users to write a note when submitting an offline payment.'),

                                        TextInput::make('offline_payments_minimum_attachments_required')
                                            ->label('Minimum attachments')
                                            ->helperText('Minimum number of required files (e.g. receipts or transfer proof).'),

                                        RichEditor::make('offline_payments_custom_message_box')
                                            ->label('Custom instructions')
                                            ->helperText('Show extra instructions to users (e.g. bank hours, contact email).')
                                            ->toolbarButtons([
                                                'bold', 'italic', 'underline', 'strike', 'link',
                                                'bulletList', 'orderedList',
                                            ])
                                            ->formatStateUsing(fn ($state) => is_string($state) ? $state : '')
                                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? $state : '')
                                            ->columnSpanFull(),
                                    ]),
                                ]),
                                Tab::make('Stripe BR')->schema([
                                    Section::make()->columns(2)->schema([

                                        Placeholder::make('stripe_br_webhook_info')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->columnSpanFull()
                                            ->content(new HtmlString(view('filament.partials.webhooks.stripe')->render())),

                                        TextInput::make('stripe_public_key')
                                            ->label('Card public key')
                                            ->helperText('Public key from the Stripe BR account used for card payments.'),

                                        TextInput::make('stripe_secret_key')
                                            ->label('Card secret key')
                                            ->helperText('Private API key from the Stripe BR account used for card payments.')
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        TextInput::make('stripe_webhooks_secret')
                                            ->label('Card webhook secret')
                                            ->helperText('Webhook signing secret from the Stripe BR account.')
                                            ->columnSpanFull()
                                            ->password()
                                            ->revealable()
                                            ->autocomplete('new-password'),

                                        Toggle::make('stripe_checkout_disabled')
                                            ->label('Disable card payments on checkout')
                                            ->helperText('Won\'t be shown on checkout, but still available for deposits page.'),

                                        Toggle::make('stripe_recurring_disabled')
                                            ->label('Disable recurring card payments')
                                            ->helperText('Prevents use of card subscriptions or recurring billing in Stripe.'),

                                        Toggle::make('stripe_oxxo_provider_enabled')
                                            ->label('Enable OXXO')
                                            ->helperText('OXXO is a cash-based payment method in Mexico.'),

                                        Toggle::make('stripe_ideal_provider_enabled')
                                            ->label('Enable iDEAL')
                                            ->helperText('iDEAL is a payment method commonly used in the Netherlands.'),

                                        Toggle::make('stripe_blik_provider_enabled')
                                            ->label('Enable Blik')
                                            ->helperText('Blik is a Polish payment method that supports instant transfers.'),

                                        Toggle::make('stripe_bancontact_provider_enabled')
                                            ->label('Enable Bancontact')
                                            ->helperText('Bancontact is a Belgian payment method used for local transactions.'),

                                        Toggle::make('stripe_eps_provider_enabled')
                                            ->label('Enable EPS')
                                            ->helperText('EPS is a bank transfer payment method used in Austria.'),

                                        Toggle::make('stripe_giropay_provider_enabled')
                                            ->label('Enable Giropay')
                                            ->helperText('Giropay is a widely used online banking payment method in Germany.'),

                                        Toggle::make('stripe_przelewy_provider_enabled')
                                            ->label('Enable Przelewy24')
                                            ->helperText('Przelewy24 is a Polish payment method supporting multiple banks.'),
                                    ]),
                                ]),
                            ]),
                    ]),

                    Tab::make('Invoices')
                        ->columns(2)
                        ->schema([
                            Toggle::make('invoices_enabled')
                                ->label('Enable invoices')
                                ->columnSpanFull()
                                ->helperText('Turn this on to automatically generate downloadable invoices for purchases.'),

                            TextInput::make('invoices_sender_name')
                                ->label('Sender name')
                                ->helperText('The name that will appear as the sender on all invoices.'),

                            TextInput::make('invoices_sender_country_name')
                                ->label('Country')
                                ->helperText('Country name included in the sender address block.'),

                            TextInput::make('invoices_sender_street_address')
                                ->label('Street address')
                                ->helperText('Street and number used as the invoice sender’s location.'),

                            TextInput::make('invoices_sender_state_name')
                                ->label('State/Province')
                                ->helperText('Region or administrative area for the sender’s address.'),

                            TextInput::make('invoices_sender_city_name')
                                ->label('City')
                                ->helperText('City name listed in the invoice sender’s address.'),

                            TextInput::make('invoices_sender_postcode')
                                ->label('Postcode')
                                ->helperText('Postal or ZIP code for the sender address.'),

                            TextInput::make('invoices_sender_company_number')
                                ->label('Company number')
                                ->helperText('Optional. Used for tax or business registry info in some regions.'),

                            TextInput::make('invoices_prefix')
                                ->label('Invoice prefix')
                                ->helperText('Prefix added to invoice numbers (e.g. INV-1001). Helps differentiate invoice types.'),

                        ]),

                    Tab::make('Withdrawals')
                        ->columns(2)
                        ->schema([
                            Select::make('withdrawal_payment_methods')
                                ->label(__('admin.settings_forms.payments.withdrawals.fields.manual_payout_methods'))
                                ->multiple()
                                ->live()
                                ->options(PaymentsServiceProvider::getAvailableWithdrawalManualMethodOptions())
                                ->default(PaymentsServiceProvider::getConfiguredWithdrawalManualMethodKeys())
                                ->formatStateUsing(fn ($state) => PaymentsServiceProvider::normalizeWithdrawalManualMethodKeys($state))
                                ->dehydrateStateUsing(fn ($state) => implode(', ', PaymentsServiceProvider::normalizeWithdrawalManualMethodKeys($state)))
                                ->columnSpanFull()
                                ->helperText(__('admin.settings_forms.payments.withdrawals.helpers.manual_payout_methods')),

                            Toggle::make('withdrawal_allow_fees')
                                ->label('Enable withdrawal fees')
                                ->helperText('If enabled, a fee will be applied to each withdrawal request.'),

                            TextInput::make('withdrawal_default_fee_percentage')
                                ->label('Fee percentage')
                                ->helperText('Percentage deducted from each withdrawal (e.g. 5 for 5%).'),

                            Toggle::make('withdrawal_enable_stripe_connect')
                                ->label('Enable Stripe Connect')
                                ->helperText('Allow users to withdraw via their own Stripe account using Connect.'),

                            TextInput::make('withdrawal_stripe_connect_webhooks_secret')
                                ->label('Stripe Connect webhook secret')
                                ->helperText('Used to verify secure events from Stripe Connect.')
                                ->password()
                                ->revealable()
                                ->autocomplete('new-password'),

                            Toggle::make('withdrawal_allow_only_for_verified')
                                ->label('Disable for non-verified users')
                                ->columnSpanFull()
                                ->helperText('Restricts withdrawals to users who have verified their identity.'),

                            TextInput::make('withdrawal_min_amount')
                                ->label('Minimum withdrawal amount')
                                ->helperText('The smallest balance a user needs to request a payout.'),

                            TextInput::make('withdrawal_max_amount')
                                ->label('Maximum withdrawal amount')
                                ->helperText('The largest payout allowed in a single withdrawal request.'),

                            RichEditor::make('withdrawal_custom_message_box')
                                ->label(__('admin.settings_forms.payments.withdrawals.fields.custom_withdrawal_message'))
                                ->helperText(__('admin.settings_forms.payments.withdrawals.helpers.custom_withdrawal_message'))
                                ->visible(fn ($get) => count(
                                    PaymentsServiceProvider::normalizeWithdrawalManualMethodKeys($get('withdrawal_payment_methods'))
                                ) > 0)
                                ->toolbarButtons([
                                    'bold', 'italic', 'underline', 'strike', 'link',
                                    'bulletList', 'orderedList',
                                ])
                                ->formatStateUsing(fn ($state) => is_string($state) ? $state : '')
                                ->dehydrateStateUsing(fn ($state) => is_string($state) ? $state : '')
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Referrals')
                        ->columns(2)
                        ->schema([
                            Toggle::make('referrals_enabled')
                                ->label('Enable referral system')
                                ->helperText('Allows users to earn rewards by referring others to the platform.')
                                ->columnSpanFull(),

                            Toggle::make('referrals_disable_for_non_verified')
                                ->label('Disable for non-verified users')
                                ->columnSpanFull()
                                ->helperText("If this option is enabled, the referral system will only be available to ID-verified users."),

                            Toggle::make('referrals_auto_follow_the_user')
                                ->label('Auto-follow the referrer')
                                ->columnSpanFull()
                                ->helperText("If this option is enabled, the newly created accounts will auto-follow the user that have referred them."),

                            Select::make('referrals_default_link_page')
                                ->label('Default referral page')
                                ->options([
                                    'profile' => 'User profile page',
                                    'register' => 'Register page',
                                    'home' => 'Homepage',
                                ])
                                ->required()
                                ->helperText("The default page for which the referral link will be created for."),

                            TextInput::make('referrals_apply_for_months')
                                ->label('Referral duration')
                                ->numeric()
                                ->helperText("Represents the number of months since users created their accounts so people who referred them earn a fee from their total earnings."),

                            TextInput::make('referrals_fee_percentage')
                                ->label('Fee percentage')
                                ->numeric()
                                ->helperText("Payout percentage given to users from their referred people total earnings. If set to 0, referred users will generate no income."),

                            TextInput::make('referrals_fee_limit')
                                ->label('Fee limit')
                                ->numeric()
                                ->helperText("Allows users to earn up to the specified limit per referred user."),

                        ]),

                ]),

        ]);
    }
}
