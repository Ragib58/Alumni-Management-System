<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // One ticket per registration.
            $table->foreignId('registration_id')
                ->unique()
                ->constrained('event_registrations')
                ->cascadeOnDelete();

            $table->string('ticket_no')->unique();

            // Unique QR token — the opaque value encoded in the QR image.
            $table->string('qr_token', 64)->unique();

            // HMAC signature of the token payload; verified at check-in.
            $table->string('qr_signature');

            // Stored, generated PDF path (public disk) — null until rendered.
            $table->string('pdf_path')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
