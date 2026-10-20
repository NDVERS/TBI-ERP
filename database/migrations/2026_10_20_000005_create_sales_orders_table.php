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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number')->unique();
            $table->date('order_date');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('location');
            $table->foreignId('job_mix_id')->constrained('job_mixes')->restrictOnDelete();
            $table->decimal('volume_m3', 10, 2);
            $table->decimal('distance_km', 8, 2)->default(0);

            // Server-calculated material estimates (IP Protected - Admin Only)
            $table->decimal('est_semen_kg', 12, 2)->nullable();
            $table->decimal('est_pasir_palu_kg', 12, 2)->nullable();
            $table->decimal('est_pasir_lokal_kg', 12, 2)->nullable();
            $table->decimal('est_split_1_2_kg', 12, 2)->nullable();
            $table->decimal('est_split_2_3_kg', 12, 2)->nullable();
            $table->decimal('est_additive_liter', 12, 2)->nullable();
            $table->decimal('est_solar_liter', 12, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
