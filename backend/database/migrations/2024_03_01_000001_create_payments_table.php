<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('registration_id')
                ->constrained('event_registrations')
                ->cascadeOnDelete();

            // Our internal, always-unique transaction reference (sent to gateway).
            $table->string('transaction_id')->unique();

            // The gateway's own reference returned on verification (val_id / trxID / payment ref).
            $table->string('gateway_transaction_id')->nullable()->index();

            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 8)->default('BDT');

            // sslcommerz | bkash | nagad
            $table->string('gateway')->index();

            // pending | paid | failed | refunded
            $table->string('status')->default('pending')->index();

            $table->timestamp('payment_date')->nullable();

            // Raw gateway request/response snapshots for auditing & reconciliation.
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['gateway', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
