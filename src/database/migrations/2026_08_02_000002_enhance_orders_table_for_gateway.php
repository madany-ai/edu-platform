<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('payment_method');
            $table->string('checkout_id')->nullable()->after('payment_gateway');
            $table->text('payment_url')->nullable()->after('checkout_id');
            $table->string('gateway_reference')->nullable()->after('payment_url');
            $table->json('metadata')->nullable()->after('gateway_reference');
            $table->text('failure_reason')->nullable()->after('metadata');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->integer('amount_refunded_cents')->nullable()->after('refunded_at');
            $table->string('idempotency_key')->nullable()->unique()->after('amount_refunded_cents');

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'status']);
            $table->dropColumn([
                'payment_gateway',
                'checkout_id',
                'payment_url',
                'gateway_reference',
                'metadata',
                'failure_reason',
                'refunded_at',
                'amount_refunded_cents',
                'idempotency_key',
            ]);
        });
    }
};
