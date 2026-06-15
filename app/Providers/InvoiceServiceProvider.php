<?php

namespace App\Providers;

use App\Model\Invoice;
use App\Model\Transaction;
use App\Model\User;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     * @return void
     */
    public function boot()
    {
    }

    public static function createInvoiceByTransaction($transaction)
    {
        if (!getSetting('payments.invoices_enabled')) {
            return null;
        }

        $data = [];
        $invoice = null;
        if ($transaction != null) {
            if($transaction->invoice_id != null){
                return null;
            }

            $senderUser = User::query()->where(['id' => $transaction->sender_user_id])->first();

            if ($senderUser != null) {
                $billingDetails = [];
                $billingDetails['senderName'] = getSetting('payments.invoices_sender_name');
                $billingDetails['senderAddress'] = getSetting('payments.invoices_sender_country_name');
                $billingDetails['senderCountry'] = getSetting('payments.invoices_sender_street_address');
                $billingDetails['senderState'] = getSetting('payments.invoices_sender_state_name');
                $billingDetails['senderPostcode'] = getSetting('payments.invoices_sender_city_name');
                $billingDetails['senderCity'] = getSetting('payments.invoices_sender_postcode');
                $billingDetails['senderCompanyNumber'] = getSetting('payments.invoices_sender_company_number');
                $billingDetails['receiverFirstName'] = $senderUser->first_name;
                $billingDetails['receiverLastName'] = $senderUser->last_name;
                $billingDetails['receiverCountryName'] = $senderUser->country;
                $billingDetails['receiverState'] = $senderUser->state;
                $billingDetails['receiverCity'] = $senderUser->city;
                $billingDetails['receiverPostcode'] = $senderUser->postcode;
                $billingDetails['receiverBillingAddress'] = $senderUser->billing_address;
                $data['subtotal'] = $transaction->amount;
                $data['taxesTotalAmount'] = 0.00;
                if ($transaction->taxes != null) {
                    $taxes = $transaction->taxes;
                    $data['taxes'] = $taxes;
                    $data['subtotal'] = $taxes['subtotal'];
                    $data['taxesTotalAmount'] = $taxes['taxesTotalAmount'];
                }
                $data['billingDetails'] = $billingDetails;
                $data['totalAmount'] = $transaction->amount;
                $data['dueDate'] = $transaction->created_at;
                $data['invoicePrefix'] = getSetting('payments.invoices_prefix');

                $latestInvoice = Invoice::orderBy('id', 'DESC')->first();
                if ($latestInvoice != null) {
                    $invoiceId = intval($latestInvoice->invoice_id) + 1;
                } else {
                    $invoiceId = 1;
                }

                $invoice = Invoice::create([
                    'invoice_id' => $invoiceId,
                    'data' => json_encode($data),
                ]);
            }
        }

        return $invoice;
    }

    /**
     * Handles invoice payment description by transaction type.
     *
     * @param $transaction
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public static function getInvoiceDescriptionByTransaction($transaction)
    {
        $description = __('One month subscription to access username profile');

        if ($transaction != null && $transaction->type != null) {
            $transactionType = $transaction->type;
            if ($transactionType === Transaction::ONE_MONTH_SUBSCRIPTION
                || $transactionType === Transaction::THREE_MONTHS_SUBSCRIPTION
                || $transactionType === Transaction::SIX_MONTHS_SUBSCRIPTION
                || $transactionType === Transaction::YEARLY_SUBSCRIPTION) {
                $subscriptionMonthlyInterval = PaymentsServiceProvider::getSubscriptionMonthlyIntervalByTransactionType($transactionType);
                $subscriptionInterval = trans_choice('months', $subscriptionMonthlyInterval, ['number' => $subscriptionMonthlyInterval]);
                $description = __(
                    ':subscriptionInterval subscription to access :username profile',
                    [
                        'subscriptionInterval' => $subscriptionInterval,
                        'username' => $transaction->receiver->name,
                    ]
                );
            }
            else{
                $description = ucfirst(__($transaction->type));
            }
        }

        return $description;
    }
}
