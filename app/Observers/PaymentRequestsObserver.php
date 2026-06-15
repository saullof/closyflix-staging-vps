<?php

namespace App\Observers;

use App\Helpers\PaymentHelper;
use App\Model\PaymentRequest;
use App\Model\Transaction;
use App\Providers\EmailsServiceProvider;
use App\Model\User;
use Illuminate\Support\Facades\App;

class PaymentRequestsObserver
{
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Listen to the PaymentRequest updating event.
     *
     * @param  PaymentRequest $paymentRequest
     * @return void
     */
    public function saving(PaymentRequest $paymentRequest)
    {
        if ($paymentRequest->getOriginal('status') === PaymentRequest::PENDING_STATUS && $paymentRequest->status != 'requested') {
            $amountChanged = $paymentRequest->getOriginal('amount') != $paymentRequest->amount;

            if($amountChanged) {
                $transaction = Transaction::query()->where('id', $paymentRequest->transaction_id)->first();
                if($transaction){
                    $transaction->update(['amount' => $paymentRequest->amount]);
                }
            }

            if(in_array($paymentRequest->status, [PaymentRequest::APPROVED_STATUS, PaymentRequest::REJECTED_STATUS])) {
                if ($paymentRequest->status === PaymentRequest::REJECTED_STATUS) {
                    $emailSubject = __('Your payment request has been denied.');
                    $button = [
                        'text' => __('Try again'),
                        'url' => route('my.settings', ['type'=>'wallet']),
                    ];
                    $transaction = Transaction::query()->where('id', $paymentRequest->transaction_id)->first();
                    if($transaction){
                        $transaction->update(['status' => Transaction::DECLINED_STATUS]);
                    }
                }

                if ($paymentRequest->status === PaymentRequest::APPROVED_STATUS) {
                    $emailSubject = __('Your payment request has been approved.');
                    $button = [
                        'text' => __('My payments'),
                        'url' => route('my.settings', ['type'=>'payments']),
                    ];
                    $transaction = Transaction::query()->where('id', $paymentRequest->transaction_id)->first();
                    if($transaction){
                        $transaction->update(['status' => Transaction::APPROVED_STATUS]);
                        $this->paymentHelper->creditReceiverForTransaction($transaction);
                    }
                }

                // Sending out the user notification
                $user = User::find($paymentRequest->user_id);
                try{
                    App::setLocale($user->settings['locale']);
                }
                catch (\Exception $e){
                    App::setLocale('en');
                }
                EmailsServiceProvider::sendGenericEmail(
                    [
                        'email' => $user->email,
                        'subject' => $emailSubject,
                        'title' => __('Hello, :name,', ['name'=>$user->name]),
                        'content' => __('Email payment request processed', [
                                'siteName' => getSetting('site.name'),
                                'status' => __($paymentRequest->status),
                            ]).($paymentRequest->status == 'approved' ? ' $'.$paymentRequest->amount.' '.__('have been credited to your account.') : ''),
                        'button' => $button,
                    ]
                );
            }
        }
    }

    public function deleting(PaymentRequest $paymentRequest) {
        if($paymentRequest->status === PaymentRequest::PENDING_STATUS) {
            $emailSubject = __('Your payment request has been denied.');
            $button = [
                'text' => __('Try again'),
                'url' => route('my.settings', ['type'=>'wallet']),
            ];
            $transaction = Transaction::query()->where('id', $paymentRequest->transaction_id)->first();
            if($transaction){
                $transaction->update(['status' => Transaction::DECLINED_STATUS]);
            }

            // Sending out the user notification
            $user = User::find($paymentRequest->user_id);
            try{
                App::setLocale($user->settings['locale']);
            }
            catch (\Exception $e){
                App::setLocale('en');
            }
            EmailsServiceProvider::sendGenericEmail(
                [
                    'email' => $user->email,
                    'subject' => $emailSubject,
                    'title' => __('Hello, :name,', ['name'=>$user->name]),
                    'content' => __('Email payment request processed', [
                        'siteName' => getSetting('site.name'),
                        'status' => __('rejected'),
                    ]),
                    'button' => $button,
                ]
            );
        }
    }
}
