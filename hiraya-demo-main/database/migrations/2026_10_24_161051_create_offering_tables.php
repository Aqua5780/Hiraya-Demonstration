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
        Schema::create('class_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculty_profiles')->nullOnDelete();
            $table->string('room')->nullable();
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->string('status')->default('open');
            $table->timestamps();

            $table->unique(['academic_year_id', 'section_id', 'subject_id'], 'offerings_unique_subject');
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_offering_id')->constrained()->cascadeOnDelete();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();

            $table->index(['day_of_week', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('class_offerings');
    }
};
