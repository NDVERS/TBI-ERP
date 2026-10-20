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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->date('opname_date');
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->enum('method', ['limas_terpancung', 'kerucut_terpancung', 'drone']);
            $table->json('dimensions_json');
            $table->decimal('calculated_volume_m3', 12, 4);
            $table->decimal('calculated_weight_kg', 15, 2);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
