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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('enrollment_start');
            $table->date('enrollment_end');
            $table->string('status')->default('upcoming');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('level');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('year_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('level');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faculty_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->string('department')->nullable();
            $table->string('employment_status')->default('full_time');
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('year_level_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->foreignId('adviser_id')->nullable()->constrained('faculty_profiles')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['academic_year_id', 'program_id', 'year_level_id', 'name'], 'sections_unique_name');
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('units', 4, 1)->default(3);
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('year_level_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('core');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('faculty_profiles');
        Schema::dropIfExists('year_levels');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('academic_years');
    }
};
