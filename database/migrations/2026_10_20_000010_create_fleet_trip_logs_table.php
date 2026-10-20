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
        Schema::create('fleet_trip_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->decimal('location_km', 8, 2)->default(0);
            $table->decimal('trip_distance_km', 8, 2)->default(0);
            $table->decimal('duration_hours', 6, 2)->default(0);
            $table->decimal('total_fuel_liter', 10, 2)->default(0);
            $table->decimal('est_tire_cost', 15, 2)->default(0);
            $table->decimal('est_sparepart_cost', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_trip_logs');
    }
};
