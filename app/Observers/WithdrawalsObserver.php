<?php

namespace App\Observers;

use App\Model\Withdrawal;
use App\Providers\EmailsServiceProvider;
use App\Providers\GenericHelperServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Providers\PaymentsServiceProvider;
use App\Providers\SettingsServiceProvider;
use App\Providers\UsersServiceProvider;
use App\Providers\WithdrawalsServiceProvider;
use App\Model\User;
use Illuminate\Support\Facades\App;

class WithdrawalsObserver
{
    /**
     * Listen to the Withdrawal updating event.
     *
     * @param Withdrawal $withdrawal
     * @return void
     */
    public function saving(Withdrawal $withdrawal)
    {
        // Observer logic only appies to admin, user side code is hosted in the controller
        if(!UsersServiceProvider::loggedAsAdmin()) {
            return;
        }

        if ($withdrawal->status === Withdrawal::REQUESTED_STATUS) {
            self::handleWithdrawalCreation($withdrawal);
        }

        if($withdrawal->status === Withdrawal::REJECTED_STATUS) {
            self::handleWithdrawalRejection($withdrawal);
        }

        if($withdrawal->status === Withdrawal::APPROVED_STATUS) {
            self::handleWithdrawalApproval($withdrawal);
        }
    }

    /**
     * Handles the Withdrawal deletion event.
     *
     * @param Withdrawal $withdrawal
     * @return void
     */
    public function deleted(Withdrawal $withdrawal)
    {
        // we only care about admin handling here
        if(!UsersServiceProvider::loggedAsAdmin()) {
            return;
        }

        if(!$withdrawal->processed){
            self::handleWithdrawalRejection($withdrawal, true);
        }
    }

    /**
     * Returns money to the user and send notifications for a rejected/deleted withdrawal.
     * @param $withdrawal
     * @param bool $skipNotficationEntry
     */
    private function handleWithdrawalRejection($withdrawal, bool $skipNotficationEntry = false): void {
        WithdrawalsServiceProvider::creditUserForRejectedWithdrawal($withdrawal);
        $emailSubject = __('Your withdrawal request has been denied.');
        $button = [
            'text' => __('Try again'),
            'url' => route('my.settings', ['type'=>'wallet']),
        ];

        self::processWithdrawalNotifications($withdrawal, $emailSubject, $button, $skipNotficationEntry);
        // mark withdrawal as processed
        $withdrawal->processed = true;
    }

    private function handleWithdrawalApproval($withdrawal): void {
        PaymentsServiceProvider::createTransactionForWithdrawal($withdrawal);

        $emailSubject = __('Your withdrawal request has been approved.');
        $button = [
            'text' => __('My payments'),
            'url' => route('my.settings', ['type'=>'payments']),
        ];

        self::processWithdrawalNotifications($withdrawal, $emailSubject, $button);
        // mark withdrawal as processed
        $withdrawal->processed = true;
        $withdrawal->fee = Withdrawal::calculateFee((float) $withdrawal->amount);
    }

    private function handleWithdrawalCreation($withdrawal) {
        $userWallet = $withdrawal->user->wallet;
        if(!$userWallet) {
            $userWallet = GenericHelperServiceProvider::createUserWallet($withdrawal->user);
        }

        $amount = $withdrawal->amount;
        $withdrawal->fee = Withdrawal::calculateFee($amount);

        $userWallet->update([
            'total' => $userWallet->total - floatval($amount),
        ]);

        // Sending out admin email
        WithdrawalsServiceProvider::processNewWithdrawalEmailNotification();
    }

    /**
     * Creates email / user notifications.
     * @param $withdrawal
     * @param $emailSubject
     * @param $button
     * @param $skipNotficationEntry
     */
    private function processWithdrawalNotifications($withdrawal, $emailSubject, $button, $skipNotficationEntry = false) {
        // Sending out the user notification
        $user = User::find($withdrawal->user_id);
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
                'content' => __('Email withdrawal processed', [
                        'siteName' => getSetting('site.name'),
                        'status' => __($withdrawal->status),
                    ]).($withdrawal->status == 'approved' ? ' '.SettingsServiceProvider::getWebsiteFormattedAmount($withdrawal->amount).(getSetting('payments.withdrawal_allow_fees') ? '(-'.SettingsServiceProvider::getWebsiteCurrencySymbol().($withdrawal->amount * (getSetting('payments.withdrawal_default_fee_percentage') / 100)).' taxes)' : '').' '.__('has been sent to your account.') : ''),
                'button' => $button,
            ]
        );

        // If withdrawal is deleted - do not create notification entry
        if(!$skipNotficationEntry){
            NotificationServiceProvider::createApprovedOrRejectedWithdrawalNotification($withdrawal);
        }
    }
}
