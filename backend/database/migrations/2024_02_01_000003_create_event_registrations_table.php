<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();

            // Human-friendly unique reference, e.g. REG-2024-0001.
            $table->string('registration_no')->unique();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // pending | confirmed | cancelled
            $table->string('status')->default('pending')->index();

            // pending | paid | failed | refunded | free
            $table->string('payment_status')->default('pending')->index();

            $table->decimal('amount', 10, 2)->default(0);

            // Answers to the event's dynamic form fields: { field_name: value }.
            $table->json('form_response')->nullable();

            $table->timestamp('registered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // A user registers an event once.
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
