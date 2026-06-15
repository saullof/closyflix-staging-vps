<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
                $table->string('coupon_code');
                $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
                $table->decimal('discount_percent', 5, 2)->nullable();
                $table->integer('amount_off')->nullable();
                $table->enum('expiration_type', ['never', 'usage', 'date'])->default('never');
                $table->integer('usage_limit')->nullable();
                $table->integer('times_used')->default(0);
                $table->dateTime('expires_at')->nullable();
                $table->integer('duration_in_months')->nullable();
                $table->string('stripe_coupon_id')->nullable();
                $table->string('payment_method')->default('all');
                $table->string('status')->default('active');
                $table->timestamps();

                $table->unique(['creator_id', 'coupon_code']);
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'coupon')) {
                $table->string('coupon')->nullable()->after('taxes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'coupon')) {
                $table->dropColumn('coupon');
            }
        });

        Schema::dropIfExists('coupons');
    }
};