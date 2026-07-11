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
        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('student_id')->nullable()->unique();
            $table->string('batch')->nullable()->index();       // e.g. "2015"
            $table->string('department')->nullable()->index();  // e.g. "CSE"
            $table->string('session')->nullable()->index();     // e.g. "2011-2012"
            $table->string('profession')->nullable()->index();  // e.g. "Software Engineer"
            $table->string('company')->nullable();
            $table->string('designation')->nullable();
            $table->string('address')->nullable();
            $table->string('profile_photo')->nullable();        // storage path
            $table->text('bio')->nullable();

            $table->timestamps();

            // Composite index to accelerate directory search/filter combos.
            $table->index(['batch', 'department']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_profiles');
    }
};
