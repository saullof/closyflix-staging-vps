<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateWithdrawalRequest;
use App\Model\UserPayoutAccount;
use App\Model\Withdrawal;
use App\Providers\GenericHelperServiceProvider;
use App\Providers\PaymentsServiceProvider;
use App\Providers\SettingsServiceProvider;
use App\Providers\StripeServiceProvider;
use App\Providers\WithdrawalsServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class WithdrawalsController extends Controller
{
    /**
     * Method used for requesting an withdrawal request from the admin.
     *
     * @param CreateWithdrawalRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestWithdrawal(CreateWithdrawalRequest $request)
    {
        try {
            $amount = (float) $request->request->get('amount');
            $message = $request->request->get('message');
            $identifier = $request->request->get('identifier');
            $pixKeyType = $request->request->get('pix_key_type');
            $pixBeneficiaryName = $request->request->get('pix_beneficiary_name');
            $methodKey = PaymentsServiceProvider::getWithdrawalMethodKey($request->request->get('method'));
            $methodLabel = PaymentsServiceProvider::getWithdrawalMethodLabel($methodKey);
            $payoutAccountId = $request->integer('payout_account_id');

            $user = Auth::user();
            if ($amount != null && $user != null) {
                [$wallet, $totalAmount, $pendingBalance] = DB::transaction(function () use ($user, $amount, $message, $identifier, $methodKey, $methodLabel, $payoutAccountId, $pixKeyType, $pixBeneficiaryName) {
                    $wallet = $user->wallet()->lockForUpdate()->first();

                    if ($wallet == null) {
                        GenericHelperServiceProvider::createUserWallet($user);
                        $wallet = $user->wallet()->lockForUpdate()->first();
                    }

                    if ($wallet == null) {
                        throw ValidationException::withMessages([
                            'amount' => __('Unable to create wallet, please try again'),
                        ]);
                    }

                    if ($amount === (float) PaymentsServiceProvider::getWithdrawalMinimumAmount() && $amount > (float) $wallet->total) {
                        throw ValidationException::withMessages([
                            'amount' => __("You don't have enough credit to withdraw. Minimum amount is: :minAmount", ['minAmount' => PaymentsServiceProvider::getWithdrawalMinimumAmount()]),
                        ]);
                    }

                    if ($amount > (float) $wallet->total) {
                        throw ValidationException::withMessages([
                            'amount' => __('You cannot withdraw this amount, try with a lower one'),
                        ]);
                    }

                    $payoutAccount = null;
                    $paymentIdentifier = $identifier;
                    $payoutSnapshot = null;

                    if (PaymentsServiceProvider::isBankTransferMethod($methodKey)) {
                        $payoutAccount = UserPayoutAccount::query()
                            ->where('user_id', $user->id)
                            ->where('method_key', UserPayoutAccount::BANK_TRANSFER)
                            ->where('is_active', true)
                            ->where('id', $payoutAccountId)
                            ->with('country')
                            ->first();

                        if (!$payoutAccount) {
                            throw ValidationException::withMessages([
                                'payout_account_id' => __('Please select a valid saved payout account.'),
                            ]);
                        }

                        $paymentIdentifier = $payoutAccount->iban;
                        $payoutSnapshot = $payoutAccount->toWithdrawalSnapshot();
                    } elseif (in_array($methodKey, [
                        PaymentsServiceProvider::WITHDRAWAL_METHOD_PAYPAL,
                        PaymentsServiceProvider::WITHDRAWAL_METHOD_PIX,
                        PaymentsServiceProvider::WITHDRAWAL_METHOD_CRYPTO,
                        PaymentsServiceProvider::WITHDRAWAL_METHOD_CUSTOM,
                    ], true)) {
                        $payoutSnapshot = [
                            'method_key' => $methodKey,
                            'method_label' => $methodLabel,
                            'identifier' => (string) $paymentIdentifier,
                            'message' => (string) $message,
                        ];

                        if ($methodKey === PaymentsServiceProvider::WITHDRAWAL_METHOD_PIX) {
                            $pixKeyTypeLabels = [
                                'cpf' => 'CPF',
                                'cnpj' => 'CNPJ',
                                'email' => __('Email'),
                                'phone' => __('Phone'),
                                'random' => __('Random key'),
                            ];

                            $payoutSnapshot['pix_key_type'] = (string) $pixKeyType;
                            $payoutSnapshot['pix_key_type_label'] = $pixKeyTypeLabels[$pixKeyType] ?? $pixKeyType;
                            $payoutSnapshot['pix_beneficiary_name'] = (string) $pixBeneficiaryName;
                            $message = trim(implode(PHP_EOL, array_filter([
                                __('Beneficiary name').': '.$pixBeneficiaryName,
                                __('PIX key type').': '.($pixKeyTypeLabels[$pixKeyType] ?? $pixKeyType),
                                __('PIX key').': '.$paymentIdentifier,
                                $message,
                            ])));
                            $payoutSnapshot['message'] = (string) $message;
                        }
                    }

                    $fee = Withdrawal::calculateFee($amount);

                    Withdrawal::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'status' => Withdrawal::REQUESTED_STATUS,
                        'message' => $message,
                        'payment_method' => $methodLabel,
                        'payment_identifier' => $paymentIdentifier,
                        'payout_account_id' => $payoutAccount?->id,
                        'payout_method_key' => $methodKey,
                        'payout_snapshot' => $payoutSnapshot,
                        'fee' => $fee,
                    ]);

                    $wallet->update([
                        'total' => (float) $wallet->total - $amount,
                    ]);

                    if ($payoutAccount) {
                        $payoutAccount->update(['last_used_at' => now()]);
                    }

                    if (in_array($methodKey, [
                        PaymentsServiceProvider::WITHDRAWAL_METHOD_PAYPAL,
                        PaymentsServiceProvider::WITHDRAWAL_METHOD_PIX,
                        PaymentsServiceProvider::WITHDRAWAL_METHOD_CRYPTO,
                        PaymentsServiceProvider::WITHDRAWAL_METHOD_CUSTOM,
                    ], true)) {
                        $settings = is_array($user->settings)
                            ? $user->settings
                            : (array) json_decode($user->settings ?? '[]', true);

                        Arr::set($settings, 'withdrawal_payout_details.'.$methodKey, [
                            'identifier' => (string) $paymentIdentifier,
                            'message' => (string) $message,
                            'pix_key_type' => (string) $pixKeyType,
                            'pix_beneficiary_name' => (string) $pixBeneficiaryName,
                        ]);

                        $user->forceFill([
                            'settings' => $settings,
                        ])->save();
                    }

                    $wallet->refresh();

                    return [
                        $wallet,
                        number_format((float) $wallet->total, 2, '.', ''),
                        number_format((float) $wallet->pendingBalance, 2, '.', ''),
                    ];
                });

                // Sending out admin email
                WithdrawalsServiceProvider::processNewWithdrawalEmailNotification();

                return response()->json([
                    'success' => true,
                    'message' => __('Successfully requested withdrawal'),
                    'totalAmount' => SettingsServiceProvider::getWebsiteFormattedAmount($totalAmount),
                    'pendingBalance' => SettingsServiceProvider::getWebsiteFormattedAmount($pendingBalance),
                ]);
            }
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            return response()->json([
                'success' => false,
                'message' => collect($errors)->flatten()->first() ?: __('The given data was invalid.'),
                'errors' => $errors,
            ], 422);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }

        return response()->json(['success' => false, 'message' => __('Something went wrong, please try again')], 500);
    }

    public function onboarding() {
        $user = Auth::user();

        try {
            // redirect user to the form where he must add his details for the first time
            $onboardingType = "account_onboarding";
            // check if user have a stripe account created
            if(!$user->stripe_account_id) {
                WithdrawalsServiceProvider::createStripeAccountForUser($user);
            }

            // check if user done onboarding and if so just redirect him to only update his details
            if(WithdrawalsServiceProvider::userDoneStripeOnboarding($user)) {
                $onboardingType = "account_update";
            }

            // create account link (Stripe hosted UI to complete verification / onboarding process)
            $accountLink = StripeServiceProvider::createStripeAccountLink($user->stripe_account_id, $onboardingType);
        } catch (\Exception $exception) {
            Log::channel('withdrawals')->error(
                'StripeConnect onboarding failed being initiated',
                ['error' => $exception->getMessage(), 'userId' => $user->id]
            );
            return back()->with('error', __('Onboarding initiation failed, please retry or contact support'));
        }

        // redirect on Stripe hosted UI
        return Redirect::away($accountLink->url);
    }

    public function approveWithdrawal($withdrawalId) {
        $approvalResponse = WithdrawalsServiceProvider::approve($withdrawalId);
        $statusCode = $approvalResponse['success'] ? 200 : ($approvalResponse['error'] === __('Withdrawal not found') ? 404 : 400);

        return response()->json($approvalResponse, $statusCode);
    }

    public function rejectWithdrawal($withdrawalId) {
        $rejectionResponse = WithdrawalsServiceProvider::reject($withdrawalId);
        $statusCode = $rejectionResponse['success'] ? 200 : ($rejectionResponse['error'] === __('Withdrawal not found') ? 404 : 400);

        return response()->json($rejectionResponse, $statusCode);
    }
}
