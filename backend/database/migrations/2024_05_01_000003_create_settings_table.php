<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            // JSON value so we can store strings, booleans, nested config, etc.
            $table->json('value')->nullable();
            // site | payment | email | sms | theme
            $table->string('group')->default('site')->index();
            // Encrypt sensitive values (gateway secrets, SMTP passwords).
            $table->boolean('is_encrypted')->default(false);
            // Whether the setting is safe to expose on the public settings endpoint.
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
