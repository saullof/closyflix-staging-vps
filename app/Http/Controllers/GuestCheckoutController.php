<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuestCheckoutRequest;
use App\Model\GuestCheckout;
use App\Model\Transaction;
use App\Services\GuestCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class GuestCheckoutController extends Controller
{
    public function __construct(private GuestCheckoutService $guestCheckoutService)
    {
    }

    public function validateCheckout(GuestCheckoutRequest $request)
    {
        return response()->json([
            'status' => 200,
        ], 200);
    }

    public function initiate(GuestCheckoutRequest $request)
    {
        try {
            $checkout = $this->guestCheckoutService->createFromRequest($request->validated());
            $redirectLink = $this->guestCheckoutService->createStripeSession($checkout);

            return Redirect::away($redirectLink);
        } catch (\Exception $exception) {
            Log::channel('payments')->error('Guest checkout failed: '.$exception->getMessage());

            $recipient = $request->get('recipient_user_id');
            $fallback = $recipient
                ? Redirect::route('profile.checkout', ['username' => optional(\App\Model\User::query()->find($recipient))->username])
                : Redirect::route('home');

            return $fallback->with('error', __('Payment failed.'));
        }
    }

    public function stripeStatus(Request $request)
    {
        $checkout = $this->guestCheckoutService->syncFromStripeSessionId($request->get('session_id'));
        if (!$checkout) {
            return Redirect::route('home')->with('error', __('Payment failed.'));
        }

        if ($checkout->status === GuestCheckout::APPROVED_STATUS) {
            Session::put(GuestCheckoutService::SESSION_KEY, $checkout->token);

            if (Auth::check()) {
                $transaction = $this->guestCheckoutService->claim($checkout, Auth::user());
                if ($transaction) {
                    return Redirect::route('profile', ['username' => $transaction->receiver->username])
                        ->with('success', __('You can now access this user profile.'));
                }
            }

            return Redirect::route('guest.checkout.complete', ['token' => $checkout->token])
                ->with('success', __('Payment succeeded'));
        }

        if ($checkout->status === GuestCheckout::PENDING_STATUS
            && $checkout->payment_provider === Transaction::STRIPE_PIX_PROVIDER) {
            Session::put(GuestCheckoutService::SESSION_KEY, $checkout->token);

            return Redirect::route('guest.checkout.complete', ['token' => $checkout->token])
                ->with('warning', __('Your payment have been successfully initiated but needs to await for approval'));
        }

        return Redirect::route('profile.checkout', ['username' => $checkout->recipient->username])
            ->with('error', __('Payment canceled'));
    }

    public function complete(string $token)
    {
        $checkout = GuestCheckout::query()->where('token', $token)->with('recipient')->firstOrFail();
        Session::put(GuestCheckoutService::SESSION_KEY, $checkout->token);

        if (Auth::check()) {
            if ($checkout->status === GuestCheckout::APPROVED_STATUS) {
                $transaction = $this->guestCheckoutService->claim($checkout, Auth::user());
                if ($transaction) {
                    return Redirect::route('profile', ['username' => $transaction->receiver->username])
                        ->with('success', __('You can now access this user profile.'));
                }
            }

            $checkout->refresh();
            if ($checkout->status === GuestCheckout::CLAIMED_STATUS && (int) $checkout->claimed_user_id === (int) Auth::id()) {
                return Redirect::route('profile', ['username' => $checkout->recipient->username])
                    ->with('success', __('You can now access this user profile.'));
            }
        }

        return view('pages.guest-checkout-complete', [
            'checkout' => $checkout,
        ]);
    }
}
