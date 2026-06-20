<?php

namespace App\Services;

use App\Helpers\PaymentHelper;
use App\Model\Coupon;
use App\Model\GuestCheckout;
use App\Model\Subscription;
use App\Model\Transaction;
use App\Model\User;
use App\Providers\InvoiceServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Providers\PaymentsServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class GuestCheckoutService
{
    public const SESSION_KEY = 'guest_checkout_token';

    public function __construct(private PaymentHelper $paymentHelper)
    {
    }

    public function createFromRequest(array $data): GuestCheckout
    {
        $recipientUser = User::query()->findOrFail($data['recipient_user_id']);
        $provider = $data['provider'];
        $type = $data['transaction_type'];
        $baseAmount = $this->resolveSubscriptionBaseAmount($recipientUser, $type);
        $couponCode = $data['coupon'] ?? null;

        if ($couponCode) {
            $couponData = $this->paymentHelper->getCouponDetails($couponCode, $recipientUser->id, $provider);
            if ($couponData && isset($couponData['discount'])) {
                $discount = $couponData['discount'];
                if ($discount['type'] === 'percent') {
                    $baseAmount -= $baseAmount * ((float) $discount['value'] / 100);
                } elseif ($discount['type'] === 'fixed') {
                    $baseAmount -= (float) $discount['value'];
                }
                $baseAmount = max(0, round($baseAmount, 2));
            } else {
                $couponCode = null;
            }
        }

        $taxQuote = $this->paymentHelper->quoteTaxesForCountry($data['country'] ?? null, $baseAmount);
        $amount = (float) $taxQuote['total'];

        if ($amount <= 0) {
            throw new \InvalidArgumentException(__('Something went wrong with this transaction. Please try again'));
        }

        return GuestCheckout::query()->create([
            'token' => $this->makeToken(),
            'status' => GuestCheckout::INITIATED_STATUS,
            'recipient_user_id' => $recipientUser->id,
            'type' => $type,
            'payment_provider' => $provider,
            'currency' => config('app.site.currency_code'),
            'amount' => $amount,
            'taxes' => $taxQuote,
            'coupon' => $couponCode,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'country' => $data['country'] ?? null,
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'city' => $data['city'] ?? null,
            'expires_at' => Carbon::now()->addDay(),
        ]);
    }

    public function createStripeSession(GuestCheckout $checkout): string
    {
        $stripeClient = $this->buildStripeClient($checkout->payment_provider);
        if ($stripeClient === null) {
            throw new \RuntimeException('Stripe secret key is not configured for '.$checkout->payment_provider.'.');
        }

        $isStripePix = $checkout->payment_provider === Transaction::STRIPE_PIX_PROVIDER;
        $stripeCurrency = strtolower(config('app.site.currency_code'));
        if ($isStripePix && $stripeCurrency !== 'brl') {
            throw new \RuntimeException('Stripe PIX is only available for BRL transactions.');
        }

        if (!$isStripePix) {
            $product = $stripeClient->products->create([
                'name' => $this->getDescription($checkout),
            ]);

            $price = $stripeClient->prices->create([
                'product' => $product->id,
                'unit_amount' => (int) round(((float) $checkout->amount) * 100),
                'currency' => $stripeCurrency,
                'recurring' => [
                    'interval' => 'month',
                    'interval_count' => PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($checkout->type),
                ],
            ]);

            $lineItem = [
                'price' => $price->id,
                'quantity' => 1,
            ];
        } else {
            $lineItem = [
                'price_data' => [
                    'currency' => $stripeCurrency,
                    'product_data' => [
                        'name' => $this->getDescription($checkout),
                        'description' => $this->getDescription($checkout),
                    ],
                    'unit_amount' => (int) round(((float) $checkout->amount) * 100),
                ],
                'quantity' => 1,
            ];
        }

        $sessionData = [
            'payment_method_types' => [$isStripePix ? 'pix' : 'card'],
            'line_items' => [$lineItem],
            'locale' => 'auto',
            'metadata' => [
                'guest_checkout_id' => $checkout->id,
                'guest_checkout_token' => $checkout->token,
                'transactionType' => $checkout->type,
                'recipient_user_id' => $checkout->recipient_user_id,
            ],
            'mode' => $isStripePix ? 'payment' : 'subscription',
            'success_url' => route('guest.checkout.status').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('guest.checkout.status').'?session_id={CHECKOUT_SESSION_ID}',
        ];

        if ($isStripePix) {
            $sessionData['customer_creation'] = 'always';
            $sessionData['payment_method_options'] = [
                'pix' => [
                    'expires_after_seconds' => 3600,
                ],
            ];
        }

        $session = $stripeClient->checkout->sessions->create($sessionData);
        $checkout->stripe_session_id = $session->id;
        $checkout->save();

        return $session->url;
    }

    public function syncFromStripeSessionId(?string $sessionId): ?GuestCheckout
    {
        if (!$sessionId) {
            return null;
        }

        $checkout = GuestCheckout::query()->where('stripe_session_id', $sessionId)->first();
        if (!$checkout) {
            return null;
        }

        try {
            $stripeClient = $this->buildStripeClient($checkout->payment_provider);
            if ($stripeClient === null) {
                return $checkout;
            }

            $stripeSession = $stripeClient->checkout->sessions->retrieve($sessionId);
            $paymentIntentId = is_string($stripeSession->payment_intent ?? null) ? $stripeSession->payment_intent : null;
            $subscriptionId = is_string($stripeSession->subscription ?? null) ? $stripeSession->subscription : null;
            $paymentMarkedAsPaid = ($stripeSession->payment_status ?? null) === 'paid';

            if (!$paymentMarkedAsPaid && $checkout->payment_provider === Transaction::STRIPE_PIX_PROVIDER && $paymentIntentId) {
                $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentIntentId);
                $paymentMarkedAsPaid = ($paymentIntent->status ?? null) === 'succeeded';
            }

            $checkout->stripe_payment_intent_id = $paymentIntentId ?: $checkout->stripe_payment_intent_id;
            $checkout->stripe_subscription_id = $subscriptionId ?: $checkout->stripe_subscription_id;
            $checkout->stripe_customer_id = is_string($stripeSession->customer ?? null)
                ? $stripeSession->customer
                : $checkout->stripe_customer_id;
            $checkout->customer_email = data_get($stripeSession, 'customer_details.email')
                ?? data_get($stripeSession, 'customer_email')
                ?? $checkout->customer_email;

            if ($paymentMarkedAsPaid) {
                if ($checkout->status !== GuestCheckout::CLAIMED_STATUS) {
                    $checkout->status = GuestCheckout::APPROVED_STATUS;
                }
            } elseif ($checkout->payment_provider === Transaction::STRIPE_PIX_PROVIDER) {
                $checkout->status = GuestCheckout::PENDING_STATUS;
            } else {
                $checkout->status = GuestCheckout::CANCELED_STATUS;
            }

            $checkout->save();
        } catch (\Exception $exception) {
            Log::channel('payments')->error('Failed syncing guest checkout: '.$exception->getMessage());
        }

        return $checkout;
    }

    public function claimPendingCheckoutFromSession(User $user): ?Transaction
    {
        $token = Session::get(self::SESSION_KEY);
        if (!$token) {
            return null;
        }

        $checkout = GuestCheckout::query()->where('token', $token)->first();
        if (!$checkout) {
            Session::forget(self::SESSION_KEY);
            return null;
        }

        $transaction = $this->claim($checkout, $user);
        if ($transaction) {
            Session::forget(self::SESSION_KEY);
        }

        return $transaction;
    }

    public function claim(GuestCheckout $checkout, User $user): ?Transaction
    {
        if ($checkout->stripe_session_id && $checkout->status !== GuestCheckout::APPROVED_STATUS) {
            $checkout = $this->syncFromStripeSessionId($checkout->stripe_session_id) ?: $checkout;
        }

        if ($checkout->status === GuestCheckout::CLAIMED_STATUS) {
            return $checkout->claimed_user_id === $user->id ? $checkout->transaction : null;
        }

        if ($checkout->status !== GuestCheckout::APPROVED_STATUS) {
            return null;
        }

        return DB::transaction(function () use ($checkout, $user) {
            $lockedCheckout = GuestCheckout::query()->where('id', $checkout->id)->lockForUpdate()->first();
            if (!$lockedCheckout || $lockedCheckout->status === GuestCheckout::CLAIMED_STATUS) {
                return $lockedCheckout?->transaction;
            }

            $this->fillUserBillingDetails($user, $lockedCheckout);
            $subscription = $this->createOrExtendSubscription($lockedCheckout, $user);
            $transaction = Transaction::query()->create([
                'sender_user_id' => $user->id,
                'recipient_user_id' => $lockedCheckout->recipient_user_id,
                'subscription_id' => $subscription->id,
                'stripe_transaction_id' => $this->resolveStripeTransactionId($lockedCheckout),
                'stripe_session_id' => $lockedCheckout->stripe_session_id,
                'status' => Transaction::APPROVED_STATUS,
                'type' => $lockedCheckout->type,
                'payment_provider' => $lockedCheckout->payment_provider,
                'currency' => $lockedCheckout->currency,
                'amount' => $lockedCheckout->amount,
                'taxes' => $lockedCheckout->taxes,
                'coupon' => $lockedCheckout->coupon,
            ]);

            $this->paymentHelper->creditReceiverForTransaction($transaction);
            $this->markCouponUsed($lockedCheckout);

            try {
                $invoice = InvoiceServiceProvider::createInvoiceByTransaction($transaction);
                if ($invoice) {
                    $transaction->invoice_id = $invoice->id;
                    $transaction->save();
                }
            } catch (\Exception $exception) {
                Log::channel('payments')->error("Failed generating invoice for guest transaction: ".$transaction->id." error: ".$exception->getMessage());
            }

            NotificationServiceProvider::createNewSubscriptionNotification($subscription);

            $lockedCheckout->status = GuestCheckout::CLAIMED_STATUS;
            $lockedCheckout->claimed_user_id = $user->id;
            $lockedCheckout->transaction_id = $transaction->id;
            $lockedCheckout->save();

            return $transaction;
        });
    }

    private function createOrExtendSubscription(GuestCheckout $checkout, User $user): Subscription
    {
        $interval = PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($checkout->type);
        $existingSubscription = Subscription::query()
            ->where('sender_user_id', $user->id)
            ->where('recipient_user_id', $checkout->recipient_user_id)
            ->where('provider', $checkout->payment_provider)
            ->first();

        $baseDate = Carbon::now('UTC');
        if ($existingSubscription && $existingSubscription->expires_at && $existingSubscription->expires_at->gt($baseDate)) {
            $baseDate = $existingSubscription->expires_at->copy();
        }

        $subscription = $existingSubscription ?: new Subscription();
        $subscription->sender_user_id = $user->id;
        $subscription->recipient_user_id = $checkout->recipient_user_id;
        $subscription->provider = $checkout->payment_provider;
        $subscription->type = $checkout->type;
        $subscription->amount = $checkout->amount;
        $subscription->status = Subscription::ACTIVE_STATUS;
        $subscription->expires_at = $baseDate->addMonths($interval);
        if ($checkout->payment_provider === Transaction::STRIPE_PROVIDER && $checkout->stripe_subscription_id) {
            $subscription->stripe_subscription_id = $checkout->stripe_subscription_id;
        }
        $subscription->save();

        return $subscription;
    }

    private function resolveStripeTransactionId(GuestCheckout $checkout): ?string
    {
        if ($checkout->stripe_payment_intent_id) {
            return $checkout->stripe_payment_intent_id;
        }

        if ($checkout->payment_provider !== Transaction::STRIPE_PROVIDER || !$checkout->stripe_subscription_id) {
            return null;
        }

        try {
            $stripeClient = $this->buildStripeClient($checkout->payment_provider);
            $stripeSubscription = $stripeClient?->subscriptions->retrieve($checkout->stripe_subscription_id);
            if ($stripeSubscription && $stripeSubscription->latest_invoice) {
                $invoice = $stripeClient->invoices->retrieve($stripeSubscription->latest_invoice);
                return $invoice->payment_intent ?: null;
            }
        } catch (\Exception $exception) {
            Log::channel('payments')->error('Failed resolving guest checkout Stripe payment intent: '.$exception->getMessage());
        }

        return null;
    }

    private function fillUserBillingDetails(User $user, GuestCheckout $checkout): void
    {
        $data = [];
        foreach (['first_name', 'last_name', 'billing_address', 'country', 'state', 'postcode', 'city'] as $field) {
            if (!$user->{$field} && $checkout->{$field}) {
                $data[$field] = $checkout->{$field};
            }
        }

        if (!empty($data)) {
            $user->update($data);
        }
    }

    private function markCouponUsed(GuestCheckout $checkout): void
    {
        if (!$checkout->coupon) {
            return;
        }

        $coupon = Coupon::query()
            ->valid()
            ->where('coupon_code', $checkout->coupon)
            ->where('creator_id', $checkout->recipient_user_id)
            ->first();

        if ($coupon && $coupon->supportsPaymentProvider($checkout->payment_provider)) {
            $coupon->increment('times_used');
        }
    }

    private function resolveSubscriptionBaseAmount(User $recipientUser, string $type): float
    {
        return match ($type) {
            Transaction::ONE_MONTH_SUBSCRIPTION => (float) $recipientUser->profile_access_price,
            Transaction::THREE_MONTHS_SUBSCRIPTION => (float) ($recipientUser->profile_access_price_3_months * 3),
            Transaction::SIX_MONTHS_SUBSCRIPTION => (float) ($recipientUser->profile_access_price_6_months * 6),
            Transaction::YEARLY_SUBSCRIPTION => (float) ($recipientUser->profile_access_price_12_months * 12),
            default => 0.0,
        };
    }

    private function getDescription(GuestCheckout $checkout): string
    {
        $recipientName = $checkout->recipient?->name ?: 'creator';

        return $recipientName.' for '.\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($checkout->amount);
    }

    private function buildStripeClient(?string $paymentProvider): ?StripeClient
    {
        $secretKey = $paymentProvider === Transaction::STRIPE_PIX_PROVIDER
            ? getSetting('payments.stripe_pix_secret_key')
            : getSetting('payments.stripe_secret_key');

        return $secretKey ? new StripeClient($secretKey) : null;
    }

    private function makeToken(): string
    {
        do {
            $token = Str::random(48);
        } while (GuestCheckout::query()->where('token', $token)->exists());

        return $token;
    }
}
