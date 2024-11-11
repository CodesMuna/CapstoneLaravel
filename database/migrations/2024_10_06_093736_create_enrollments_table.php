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
            $table->id('enrol_id'); // Primary key for this table
            $table->unsignedBigInteger('lrn'); // Define lrm as a regular column
            $table->timestamp('regapproval_date')->nullable();
            $table->timestamp('date_register')->nullable();
            $table->string('payment_approval')->nullable();
            $table->string('year_level');
            $table->string('contact_no')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('last_attended')->nullable();
            $table->string('public_private')->nullable();
            $table->string('strand')->nullable();
            $table->string('school_year');
            $table->timestamps(); // This creates created_at and updated_at columns
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
