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
        Schema::create('job_mixes', function (Blueprint $table) {
            $table->id();
            $table->string('mutu_code')->unique();
            $table->decimal('semen_kg', 10, 2)->default(0);
            $table->decimal('pasir_palu_kg', 10, 2)->default(0);
            $table->decimal('pasir_lokal_kg', 10, 2)->default(0);
            $table->decimal('split_1_2_kg', 10, 2)->default(0);
            $table->decimal('split_2_3_kg', 10, 2)->default(0);
            $table->decimal('additive_liter', 10, 2)->default(0);
            $table->decimal('waste_factor', 5, 4)->default(1.0500);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_mixes');
    }
};
