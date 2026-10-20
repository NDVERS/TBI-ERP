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
        Schema::create('material_receives', function (Blueprint $table) {
            $table->id();
            $table->date('receive_date');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('vehicle_no');
            $table->string('ri_no');
            $table->time('time_in');
            $table->decimal('volume_m3', 10, 2);
            $table->decimal('density_used', 10, 2);
            $table->decimal('total_weight_kg', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_receives');
    }
};
