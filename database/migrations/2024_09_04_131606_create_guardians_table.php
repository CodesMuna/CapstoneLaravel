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
        Schema::create('parent_guardians', function (Blueprint $table) {
            $table->id('guardian_id')->primary();
            $table->foreignId('LRN');
            $table->string('fname');
            $table->string('lname');
            $table->string('mname')->nullable();
            $table->string('address');
            $table->string('relationship');
            $table->string('contact_no');
            $table->string('parent_pic')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
