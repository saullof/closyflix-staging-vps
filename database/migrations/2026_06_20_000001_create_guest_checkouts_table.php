<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuestCheckoutsTable extends Migration
{
    public function up()
    {
        Schema::create('guest_checkouts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('token', 64)->unique();
            $table->string('status')->index();
            $table->unsignedBigInteger('recipient_user_id');
            $table->unsignedBigInteger('claimed_user_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('type')->index();
            $table->string('payment_provider')->index();
            $table->string('currency', 8);
            $table->decimal('amount', 12, 2);
            $table->text('taxes')->nullable();
            $table->string('coupon')->nullable();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('customer_email')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('claimed_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('guest_checkouts');
    }
}
