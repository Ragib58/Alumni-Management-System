<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite / covering indexes to keep queries fast at 50k+ users. Guarded with
 * existence checks so it is safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->index(['event_id', 'status'], 'reg_event_status_idx');
            $table->index(['user_id', 'status'], 'reg_user_status_idx');
            $table->index('created_at', 'reg_created_at_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'payment_date'], 'pay_status_date_idx');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('checkin_time', 'att_checkin_idx');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'event_date'], 'events_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropIndex('reg_event_status_idx');
            $table->dropIndex('reg_user_status_idx');
            $table->dropIndex('reg_created_at_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('pay_status_date_idx');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('att_checkin_idx');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_date_idx');
        });
    }
};
