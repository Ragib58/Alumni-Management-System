<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();

            // Sponsors belong to an event (nullable = global/organisation sponsor).
            $table->foreignId('event_id')
                ->nullable()
                ->constrained('events')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->decimal('amount', 12, 2)->default(0);

            // platinum | gold | silver | bronze
            $table->string('sponsor_type')->default('bronze')->index();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['event_id', 'sponsor_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsors');
    }
};
