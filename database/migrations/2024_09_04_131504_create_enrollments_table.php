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
            $table->id('enrol_id')->primary();
            $table->foreignId('LRN');
            $table->date('regapproval_date')->nullable();
            $table->date('payment_approval')->nullable();
            $table->string('grade_level');
            $table->string('guardian_name');
            $table->string('last_attended');
            $table->string('public_private');
            $table->date('date_register')->nullable();
            $table->string('strand')->nullable();
            $table->string('school_year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
