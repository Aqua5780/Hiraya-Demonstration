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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft');
            $table->decimal('total_units', 5, 1)->default(0);
            $table->decimal('assessed_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });

        Schema::create('enrollment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_offering_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('enrolled');
            $table->timestamps();

            $table->unique(['enrollment_id', 'class_offering_id']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_detail_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculty_profiles')->nullOnDelete();
            $table->decimal('midterm', 5, 2)->nullable();
            $table->decimal('final', 5, 2)->nullable();
            $table->decimal('final_rating', 5, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('encoded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
        Schema::dropIfExists('enrollment_details');
        Schema::dropIfExists('enrollments');
    }
};
