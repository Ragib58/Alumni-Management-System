<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dynamic, per-event registration form fields defined by admins.
     */
    public function up(): void
    {
        Schema::create('event_form_fields', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->string('label');
            // Machine key used in the registration response payload, unique per event.
            $table->string('name');
            // text | number | email | select | checkbox | radio | textarea | file
            $table->string('type');

            // Options for select/checkbox/radio; stored as JSON array of strings.
            $table->json('options')->nullable();

            $table->boolean('is_required')->default(false);
            $table->string('placeholder')->nullable();
            $table->string('help_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['event_id', 'name']);
            $table->index(['event_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_form_fields');
    }
};
