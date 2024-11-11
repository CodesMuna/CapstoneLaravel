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
        Schema::create('tuition_and_fees', function (Blueprint $table) {
            $table->id('fee_id')->primary();
            $table->string('year_level');
            $table->string('tuition');
            $table->string('general');
            $table->string('esc');
            $table->string('subsidy');
            $table->string('req_downpayment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            
    }
};
