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
        Schema::create('grades', function (Blueprint $table) {
            $table->id('grade_id'); // Primary key
            $table->unsignedBigInteger('LRN'); // Learner Reference Number
            $table->unsignedBigInteger('class_id'); // Class ID
            $table->string('grade'); // Grade (e.g., A, B, C or numeric value)
            $table->string('term');
            $table->string('permission'); // Term (e.g., First Semester, Second Semester)
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
