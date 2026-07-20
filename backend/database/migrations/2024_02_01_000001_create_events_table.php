<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('banner')->nullable();          // storage path
            $table->text('description')->nullable();
            $table->string('venue')->nullable();

            // Category: reunion | seminar | workshop | sports | cultural_program | iftar
            $table->string('type')->default('reunion')->index();

            $table->timestamp('event_date')->nullable()->index();
            $table->timestamp('registration_start')->nullable();
            $table->timestamp('registration_end')->nullable();

            $table->decimal('fee', 10, 2)->default(0);
            $table->unsignedInteger('max_capacity')->nullable(); // null = unlimited

            // Lifecycle: draft | published | closed | completed
            $table->string('status')->default('draft')->index();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
