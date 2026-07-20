<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // One attendance record per registration.
            $table->foreignId('registration_id')
                ->unique()
                ->constrained('event_registrations')
                ->cascadeOnDelete();

            // Denormalized for fast per-event attendance queries/analytics.
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            // not_arrived | checked_in | checked_out
            $table->string('status')->default('not_arrived')->index();

            $table->timestamp('checkin_time')->nullable();
            $table->timestamp('checkout_time')->nullable();

            // The admin/scanner who performed the check-in.
            $table->foreignId('checked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
